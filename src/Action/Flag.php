<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;

/**
 * Action that marks a message with the flagged flag.
 */
final readonly class Flag implements Action
{
    public function __invoke(MessageInterface $message): void
    {
        $message->markFlagged();
    }
}
