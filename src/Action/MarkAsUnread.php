<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;

/**
 * Marks a message as unread.
 *
 * This action removes the \Seen flag, making the message appear as unread
 * in the mailbox.
 */
readonly class MarkAsUnread implements Action
{
    public function __invoke(MessageInterface $message): void
    {
        $message->markUnread();
    }
}
