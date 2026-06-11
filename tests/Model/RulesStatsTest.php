<?php

declare(strict_types=1);

namespace Tests\Model;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\MessageQuery;
use MailboxRules\Action\LogAction;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\Matcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use MailboxRules\Model\RuleExecutionStats;
use MailboxRules\Model\StatsCollector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
#[CoversClass(StatsCollector::class)]
#[CoversClass(RuleExecutionStats::class)]
final class RulesStatsTest extends TestCase
{
    private function makeMailboxWithMessages(Message ...$messages): Mailbox
    {
        $query = $this->createStub(MessageQuery::class);
        $query->method('withHeaders')->willReturn($query);
        $query->method('get')->willReturn(new MessageCollection($messages));

        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($folder);

        return $mailbox;
    }

    #[Test]
    public function stats_returns_empty_array_before_any_apply(): void
    {
        $mailbox = $this->makeMailboxWithMessages();
        $rules = new Rules($mailbox, []);

        self::assertSame([], $rules->stats());
    }

    #[Test]
    public function stats_tracks_rule_evaluation_count(): void
    {
        $msg1 = $this->createStub(Message::class);
        $msg2 = $this->createStub(Message::class);
        $mailbox = $this->makeMailboxWithMessages($msg1, $msg2);

        $rule = new Rule('Count Test', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction());
        $rules = new Rules($mailbox, [$rule]);
        $rules->apply();

        $stats = $rules->stats();
        self::assertCount(1, $stats);
        self::assertSame('Count Test', $stats[0]->ruleName);
        self::assertSame(2, $stats[0]->evaluationCount);
    }

    #[Test]
    public function stats_tracks_match_count(): void
    {
        $msg1 = $this->createStub(Message::class);
        $msg2 = $this->createStub(Message::class);
        $mailbox = $this->makeMailboxWithMessages($msg1, $msg2);

        $neverMatcher = $this->createStub(Matcher::class);
        $neverMatcher->method('matches')->willReturn(false);

        $alwaysMatcher = new AnyMatcher();

        $ruleA = new Rule('Never Matches', $neverMatcher, static fn (MessageInterface $m): iterable => yield new LogAction());
        $ruleB = new Rule('Always Matches', $alwaysMatcher, static fn (MessageInterface $m): iterable => yield new LogAction());

        $rules = new Rules($mailbox, [$ruleA, $ruleB]);
        $rules->apply();

        $stats = $rules->stats();
        self::assertCount(2, $stats);

        $neverStats = $this->findStatsByRule($stats, 'Never Matches');
        self::assertSame(0, $neverStats->matchCount);

        $alwaysStats = $this->findStatsByRule($stats, 'Always Matches');
        self::assertSame(2, $alwaysStats->matchCount);
    }

    #[Test]
    public function stats_tracks_action_count(): void
    {
        $message = $this->createStub(Message::class);
        $mailbox = $this->makeMailboxWithMessages($message);

        $rule = new Rule(
            'Multi Action',
            new AnyMatcher(),
            static function (MessageInterface $m): iterable {
                yield new LogAction();
                yield new LogAction();
                yield new LogAction();
            }
        );

        $rules = new Rules($mailbox, [$rule]);
        $rules->apply();

        $stats = $rules->stats();
        self::assertSame(3, $stats[0]->actionCount);
    }

    #[Test]
    public function stats_resets_between_apply_calls(): void
    {
        $message = $this->createStub(Message::class);

        $query = $this->createStub(MessageQuery::class);
        $query->method('withHeaders')->willReturn($query);
        $query->method('get')->willReturn(new MessageCollection([$message]));

        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($folder);

        $rule = new Rule('Test', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction());
        $collector = new StatsCollector();
        $rules = new Rules($mailbox, [$rule], statsCollector: $collector);

        $rules->apply();
        $firstStats = $rules->stats();
        self::assertSame(1, $firstStats[0]->evaluationCount);

        $rules->apply();
        $secondStats = $rules->stats();
        self::assertSame(1, $secondStats[0]->evaluationCount, 'Stats should reset between apply() calls');
    }

    #[Test]
    public function stats_tracks_matcher_time_as_non_negative_float(): void
    {
        $message = $this->createStub(Message::class);
        $mailbox = $this->makeMailboxWithMessages($message);

        $rule = new Rule('Timed Rule', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction());
        $rules = new Rules($mailbox, [$rule]);
        $rules->apply();

        $stats = $rules->stats();
        self::assertGreaterThanOrEqual(0.0, $stats[0]->totalMatcherTimeMs);
    }

    #[Test]
    public function stats_tracks_failure_count_when_action_throws(): void
    {
        $message = $this->createStub(Message::class);
        $mailbox = $this->makeMailboxWithMessages($message);

        $rule = new Rule(
            'Failing Rule',
            new AnyMatcher(),
            static function (MessageInterface $m): iterable {
                yield new class() implements \MailboxRules\Action {
                    public function __invoke(): void
                    {
                        throw new \RuntimeException('Action failed');
                    }
                };
            }
        );

        $rules = new Rules($mailbox, [$rule]);

        try {
            $rules->apply();
        } catch (\RuntimeException) {
        }

        $stats = $rules->stats();
        self::assertSame(1, $stats[0]->failureCount);
    }

    #[Test]
    public function stats_returns_one_entry_per_rule(): void
    {
        $message = $this->createStub(Message::class);
        $mailbox = $this->makeMailboxWithMessages($message);

        $rules = new Rules($mailbox, [
            new Rule('Rule A', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction()),
            new Rule('Rule B', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction()),
            new Rule('Rule C', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction()),
        ]);
        $rules->apply();

        self::assertCount(3, $rules->stats());
    }

    #[Test]
    public function stats_collector_can_be_injected(): void
    {
        $mailbox = $this->makeMailboxWithMessages();
        $collector = new StatsCollector();

        $rules = new Rules($mailbox, [], statsCollector: $collector);
        $rules->apply();

        self::assertSame($rules->stats(), $collector->all());
    }

    /**
     * @param list<RuleExecutionStats> $stats
     */
    private function findStatsByRule(array $stats, string $ruleName): RuleExecutionStats
    {
        foreach ($stats as $stat) {
            if ($stat->ruleName === $ruleName) {
                return $stat;
            }
        }

        self::fail("No stats found for rule: {$ruleName}");
    }
}
