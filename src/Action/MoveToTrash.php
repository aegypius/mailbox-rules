<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

/**
 * @phpstan-import-type ActionConditionClosure from WithCondition
 */
final readonly class MoveToTrash implements Action
{
    use WithCondition;

    public function __construct(
        /** @param ActionConditionClosure $condition */
        \Closure|null $condition = null,
    ) {
        $this->condition = $condition ?? fn (): bool => true;
    }

    public function __invoke(Message $message): void
    {
        if ($this->check($message)) {
            // Logic to move the message to trash
            echo "Message moved to trash.";
        }
    }
}
