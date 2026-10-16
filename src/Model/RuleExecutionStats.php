<?php

declare(strict_types=1);

namespace MailboxRules\Model;

final readonly class RuleExecutionStats
{
    public function __construct(
        public string $ruleName,
        public int $evaluationCount,
        public int $matchCount,
        public int $actionCount,
        public int $failureCount,
        public float $totalMatcherTimeMs,
    ) {
    }

    public function plus(self $other): self
    {
        return new self(
            $this->ruleName,
            $this->evaluationCount + $other->evaluationCount,
            $this->matchCount + $other->matchCount,
            $this->actionCount + $other->actionCount,
            $this->failureCount + $other->failureCount,
            $this->totalMatcherTimeMs + $other->totalMatcherTimeMs,
        );
    }
}
