<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use MailboxRules\Contract\MailboxInterface;
use MailboxRules\Contract\MessageInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zenstruck\Callback;
use Zenstruck\Callback\Parameter;

final readonly class Rules
{
    private Traversal $traversal;

    /**
     * @param iterable<Rule> $rules
     */
    public function __construct(
        private MailboxInterface $mailbox,
        iterable $rules,
        private LoggerInterface $logger = new NullLogger(),
        private StatsCollector $statsCollector = new StatsCollector(),
    ) {
        $this->traversal = new Traversal($mailbox, $rules, $logger);
    }

    /**
     * @return list<RuleExecutionStats>
     */
    public function stats(): array
    {
        return $this->statsCollector->all();
    }

    /**
     * @return list<Parameter>
     */
    private function useParameters(MessageInterface $message): array
    {
        return [
            Parameter::typed(MessageInterface::class, $message),
            Parameter::typed(LoggerInterface::class, $this->logger),
            Parameter::typed(MailboxInterface::class, $this->mailbox),
        ];
    }

    /**
     * Apply specific rules to a message.
     *
     * @param list<Rule> $rules
     */
    private function doApplyRules(MessageInterface $message, array $rules): void
    {
        foreach ($rules as $rule) {
            $startTimeNs = hrtime(true);
            $actionCount = 0;
            $failureCount = 0;

            $actionsList = $rule($message);
            $matched = $actionsList !== [];

            foreach ($actionsList as $action) {
                // Cast to callable for PHPStan - all Actions implement __invoke
                /** @var callable $callableAction */
                $callableAction = $action;
                try {
                    Callback::createFor($callableAction)->invokeAll(
                        Parameter::union(...$this->useParameters($message))
                    );
                    $actionCount++;
                } catch (\Exception $actionException) {
                    $failureCount++;
                    $this->recordStats($rule->name, $matched, $actionCount, $failureCount, $startTimeNs);
                    throw $actionException;
                }
            }

            if ($matched) {
                $this->logger->info('Rule "{rule}" matched message: {subject}', [
                    'rule' => $rule->name,
                    'subject' => $message->subject() ?? '(no subject)',
                ]);
            }

            $this->recordStats($rule->name, $matched, $actionCount, $failureCount, $startTimeNs);
        }
    }

    private function recordStats(string $ruleName, bool $matched, int $actionCount, int $failureCount, int $startTimeNs): void
    {
        $this->statsCollector->recordEvaluation(
            $ruleName,
            $matched,
            $actionCount,
            $failureCount,
            (hrtime(true) - $startTimeNs) / 1_000_000,
        );
    }

    public function apply(): void
    {
        $this->statsCollector->reset();
        $this->traversal->pass($this->applyStep(...));
        $this->logger->debug('Done processing messages');
    }

    /**
     * One Watch pass: the first covers everything like apply(), later ones only new messages.
     * Stats cover this pass only.
     */
    public function poll(): void
    {
        $this->statsCollector->reset();
        $this->traversal->poll($this->applyStep(...));
    }

    /**
     * @param list<Rule> $rules
     */
    private function applyStep(MessageInterface $message, array $rules): void
    {
        $this->doApplyRules($message, $rules);
    }

    /**
     * Previews what actions would be executed for each message without actually executing them.
     *
     * @return list<PreviewResult> List of preview results showing messages, rules, and actions
     */
    public function preview(int $limit = 10): array
    {
        $this->statsCollector->reset();
        $results = [];

        $this->traversal->pass(function (MessageInterface $message, array $rules) use (&$results): void {
            foreach ($rules as $rule) {
                $startTimeNs = hrtime(true);
                $actions = $rule($message);
                $this->recordStats($rule->name, $actions !== [], 0, 0, $startTimeNs);

                if ($actions !== []) {
                    $results[] = new PreviewResult(message: $message, ruleName: $rule->name, actions: $actions);
                }
            }
        }, $limit > 0 ? $limit : null);

        return $results;
    }
}
