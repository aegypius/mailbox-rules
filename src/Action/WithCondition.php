<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;

/**
 * @phpstan-type ActionConditionClosure \Closure(Message $message): bool
 */
trait WithCondition
{
    /**
     * @var ActionConditionClosure
     */
    private \Closure $condition;

    /**
     * @param ActionConditionClosure $condition
     */
    public function when(\Closure $condition): self
    {
        return new self($condition);
    }

    private function check(Message $message): bool
    {
        return !($this->condition)($message);
    }
}
