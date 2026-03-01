<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

/**
 * Action that moves a message to the Trash folder.
 *
 * This action provides recoverable deletion by moving messages to a trash folder.
 * The default folder name is "Trash", but can be customized.
 *
 * Common trash folder names: "Trash", "Deleted Items", "Deleted Messages", "[Gmail]/Trash"
 */
final readonly class MoveToTrash implements Action
{
    public function __construct(
        private string $trashFolder = 'Trash',
        private bool $expunge = false,
    ) {
    }

    public function __invoke(Message $message): void
    {
        $message->move($this->trashFolder, $this->expunge);
    }
}
