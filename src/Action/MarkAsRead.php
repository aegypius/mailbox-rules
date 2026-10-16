<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;

/**
 * Action that marks a message as read (seen).
 */
final readonly class MarkAsRead implements Action
{
    public function __invoke(MessageInterface $message): void
    {
        $message->markRead();
    }
}
