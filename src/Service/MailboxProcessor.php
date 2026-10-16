<?php

declare(strict_types=1);

namespace MailboxRules\Service;

use MailboxRules\MailboxFactory;
use MailboxRules\MailboxFactoryInterface;
use MailboxRules\Model\MailboxPreview;
use MailboxRules\Model\RuleExecutionStats;
use MailboxRules\Model\Rules;
use MailboxRules\ValueObject\MailboxConfiguration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/** Single entry point for Apply, Preview and Watch across Mailboxes; the only place a Rules is built. */
final readonly class MailboxProcessor
{
    private MailboxFactoryInterface $mailboxFactory;

    public function __construct(
        MailboxFactoryInterface|null $mailboxFactory = null,
        private LoggerInterface $logger = new NullLogger(),
    ) {
        $this->mailboxFactory = $mailboxFactory ?? new MailboxFactory();
    }

    /**
     * @param iterable<MailboxConfiguration> $configs
     * @return list<RuleExecutionStats> merged by rule name across Mailboxes
     */
    public function apply(iterable $configs): array
    {
        $stats = [];
        foreach ($configs as $config) {
            $this->announce('Processing mailbox: {mailbox}', $config);
            $rules = $this->rulesFor($config);
            $rules->apply();
            $stats[] = $rules->stats();
        }

        return self::merge(...$stats);
    }

    /**
     * Preview records no stats: no Action runs, so counts would only repeat the listing.
     *
     * @param iterable<MailboxConfiguration> $configs
     * @return list<MailboxPreview>
     */
    public function preview(iterable $configs, int $limit = 10): array
    {
        $previews = [];
        foreach ($configs as $config) {
            $this->announce('Previewing mailbox: {mailbox}', $config);
            $previews[] = new MailboxPreview($config->name, $this->rulesFor($config)->preview($limit));
        }

        return $previews;
    }

    /**
     * Poll every Mailbox in one loop, so none blocks the others.
     *
     * @param iterable<MailboxConfiguration> $configs
     * @param callable(list<RuleExecutionStats>): void $onPass called only after a pass that matched something
     * @param int|null $passes Stop after this many passes; null runs forever.
     */
    public function watch(iterable $configs, int $interval, callable $onPass, ?int $passes = null): void
    {
        $watched = [];
        foreach ($configs as $config) {
            $this->announce('Watching mailbox: {mailbox}', $config);
            $watched[] = $this->rulesFor($config);
        }

        for ($pass = 0; $passes === null || $pass < $passes; $pass++) {
            if ($pass > 0) {
                sleep($interval);
            }

            $stats = [];
            foreach ($watched as $rules) {
                $rules->poll();
                $stats[] = $rules->stats();
            }

            $merged = self::merge(...$stats);
            if (array_sum(array_map(static fn (RuleExecutionStats $s): int => $s->matchCount, $merged)) > 0) {
                $onPass($merged);
            }
        }
    }

    private function rulesFor(MailboxConfiguration $config): Rules
    {
        return new Rules($this->mailboxFactory::createMailbox($config->dsn), $config->rules, $this->logger);
    }

    private function announce(string $message, MailboxConfiguration $config): void
    {
        if ($config->name !== null) {
            $this->logger->info($message, [
                'mailbox' => $config->name,
            ]);
        }
    }

    /**
     * @param list<RuleExecutionStats> ...$lists
     * @return list<RuleExecutionStats>
     */
    private static function merge(array ...$lists): array
    {
        $byName = [];
        foreach ($lists as $list) {
            foreach ($list as $s) {
                $p = $byName[$s->ruleName] ?? null;
                $byName[$s->ruleName] = new RuleExecutionStats(
                    $s->ruleName,
                    ($p->evaluationCount ?? 0) + $s->evaluationCount,
                    ($p->matchCount ?? 0) + $s->matchCount,
                    ($p->actionCount ?? 0) + $s->actionCount,
                    ($p->failureCount ?? 0) + $s->failureCount,
                    ($p->totalMatcherTimeMs ?? 0.0) + $s->totalMatcherTimeMs,
                );
            }
        }

        return array_values($byName);
    }
}
