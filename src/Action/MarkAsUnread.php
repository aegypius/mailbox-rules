<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

/**
 * Marks a message as unread.
 *
 * This action removes the \Seen flag, making the message appear as unread
 * in the mailbox.
 */
readonly class MarkAsUnread implements Action
{
    public function __invoke(Message $message): void
    {
        $message->markUnread();
    }
}
