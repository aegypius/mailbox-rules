<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

/**
 * Action that moves a message to a specified folder.
 */
final readonly class MoveToFolder implements Action
{
    public function __construct(
        private string $folder,
        private bool $expunge = false,
    ) {
    }

    public function __invoke(Message $message): void
    {
        $message->move($this->folder, $this->expunge);
    }
}
