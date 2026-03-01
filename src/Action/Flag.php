<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

/**
 * Action that marks a message with the flagged flag.
 */
final readonly class Flag implements Action
{
    public function __invoke(Message $message): void
    {
        $message->markFlagged();
    }
}
