<?php

declare(strict_types=1);

namespace Tests\Service;

use MailboxRules\Action\Flag;
use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Contract\MailboxInterface;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\MailboxFactoryInterface;
use MailboxRules\Model\RuleExecutionStats;
use MailboxRules\Service\MailboxProcessor;
use MailboxRules\ValueObject\Dsn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function MailboxRules\any;
use function MailboxRules\mailbox;
use function MailboxRules\rule;

#[CoversClass(MailboxProcessor::class)]
final class MailboxProcessorTest extends TestCase
{
    /**
     * @param array<string, InMemoryMailbox> $boxes keyed by DSN user
     */
    private function processor(array $boxes): MailboxProcessor
    {
        $factory = new class($boxes) implements MailboxFactoryInterface {
            /**
             * @var array<string, InMemoryMailbox>
             */
            public static array $boxes = [];

            /**
             * @param array<string, InMemoryMailbox> $boxes
             */
            public function __construct(array $boxes)
            {
                self::$boxes = $boxes;
            }

            public static function createMailbox(Dsn $dsn): MailboxInterface
            {
                return self::$boxes[$dsn->user];
            }
        };

        return new MailboxProcessor($factory);
    }

    private function flagRule(): \MailboxRules\Model\Rule
    {
        return rule('Flag', any(), static fn () => [new Flag()]);
    }

    #[Test]
    public function apply_returns_stats_across_every_mailbox(): void
    {
        $one = new InMemoryMailbox();
        $two = new InMemoryMailbox();
        $one->deliver('INBOX');
        $one->deliver('INBOX');
        $two->deliver('INBOX');

        $stats = $this->processor([
            'u1' => $one,
            'u2' => $two,
        ])->apply([
            mailbox('imap://u1:p@localhost:993/INBOX', [$this->flagRule()]),
            mailbox('imap://u2:p@localhost:993/INBOX', [$this->flagRule()]),
        ]);

        self::assertCount(1, $stats);
        self::assertSame('Flag', $stats[0]->ruleName);
        self::assertSame(3, $stats[0]->evaluationCount);
        self::assertSame(3, $stats[0]->matchCount);
        self::assertSame(3, $stats[0]->actionCount);
    }

    #[Test]
    public function preview_runs_nothing_and_groups_results_by_mailbox(): void
    {
        $box = new InMemoryMailbox();
        $message = $box->deliver('INBOX');

        $previews = $this->processor([
            'u1' => $box,
        ])->preview([
            mailbox('imap://u1:p@localhost:993/INBOX', [$this->flagRule()], 'Main'),
        ]);

        self::assertCount(1, $previews);
        self::assertSame('Main', $previews[0]->mailbox);
        self::assertCount(1, $previews[0]->results);
        self::assertFalse($message->isFlagged());
    }

    #[Test]
    public function watch_covers_every_mailbox_in_one_loop(): void
    {
        $first = new InMemoryMailbox();
        $second = new InMemoryMailbox();
        $a = $first->deliver('INBOX');
        $b = $second->deliver('INBOX');

        $this->processor([
            'u1' => $first,
            'u2' => $second,
        ])->watch([
            mailbox('imap://u1:p@localhost:993/INBOX', [$this->flagRule()]),
            mailbox('imap://u2:p@localhost:993/INBOX', [$this->flagRule()]),
        ], interval: 0, onPass: static function (): void {
        }, passes: 2);

        self::assertTrue($a->isFlagged());
        self::assertTrue($b->isFlagged());
    }

    #[Test]
    public function watch_reports_only_passes_that_matched_something(): void
    {
        $box = new InMemoryMailbox();
        $box->deliver('INBOX', subject: 'old');
        $reports = [];

        $this->processor([
            'u1' => $box,
        ])->watch(
            [mailbox('imap://u1:p@localhost:993/INBOX', [$this->flagRule()])],
            interval: 0,
            onPass: static function (array $stats) use (&$reports): void {
                $reports[] = $stats;
            },
            passes: 3,
        );

        self::assertCount(1, $reports);
        self::assertSame(1, $reports[0][0]->matchCount);
    }

    #[Test]
    public function watch_stats_cover_one_pass_only(): void
    {
        $box = new InMemoryMailbox();
        $box->deliver('INBOX', subject: 'old');
        $matches = [];
        $delivered = false;
        $rule = rule('Late', any(), static function (MessageInterface $m) use ($box, &$delivered): array {
            if (!$delivered) {
                $delivered = true;
                $box->deliver('INBOX', subject: 'new');
            }

            return [new Flag()];
        });

        $this->processor([
            'u1' => $box,
        ])->watch(
            [mailbox('imap://u1:p@localhost:993/INBOX', [$rule])],
            interval: 0,
            onPass: static function (array $stats) use (&$matches): void {
                $matches[] = array_sum(array_map(static fn (RuleExecutionStats $s): int => $s->matchCount, $stats));
            },
            passes: 3,
        );

        self::assertSame([1, 1], $matches);
    }
}
