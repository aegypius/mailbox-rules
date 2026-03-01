<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

/**
 * Action that permanently deletes a message.
 *
 * This action marks the message as deleted and expunges it immediately,
 * permanently removing it from the mailbox.
 *
 * WARNING: This action is irreversible. Use MoveToTrash for recoverable deletion.
 */
final readonly class Delete implements Action
{
    public function __construct(
        private bool $expunge = true,
    ) {
    }

    public function __invoke(Message $message): void
    {
        $message->delete($this->expunge);
    }
}
