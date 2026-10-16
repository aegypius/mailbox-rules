<?php

declare(strict_types=1);

namespace MailboxRules\Model;

final class StatsCollector
{
    /**
     * @var array<string, array{eval: int, match: int, actions: int, failures: int, time_ms: float}>
     */
    private array $data = [];

    public function reset(): void
    {
        $this->data = [];
    }

    public function recordEvaluation(string $ruleName, bool $matched, int $actions, int $failures, float $matcherTimeMs): void
    {
        if (!isset($this->data[$ruleName])) {
            $this->data[$ruleName] = [
                'eval' => 0,
                'match' => 0,
                'actions' => 0,
                'failures' => 0,
                'time_ms' => 0.0,
            ];
        }

        $this->data[$ruleName]['eval']++;
        $this->data[$ruleName]['time_ms'] += $matcherTimeMs;

        if ($matched) {
            $this->data[$ruleName]['match']++;
            $this->data[$ruleName]['actions'] += $actions;
            $this->data[$ruleName]['failures'] += $failures;
        }
    }

    /**
     * @return list<RuleExecutionStats>
     */
    public function all(): array
    {
        return array_map(
            static fn (string $name, array $data): RuleExecutionStats => new RuleExecutionStats(
                ruleName: $name,
                evaluationCount: $data['eval'],
                matchCount: $data['match'],
                actionCount: $data['actions'],
                failureCount: $data['failures'],
                totalMatcherTimeMs: $data['time_ms'],
            ),
            array_keys($this->data),
            $this->data,
        );
    }
}
