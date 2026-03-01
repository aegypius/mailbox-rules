<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\Support\Str;
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
        $mailbox = $message->folder()->mailbox();

        // Ensure the target folder exists, create if needed
        // Note: folders()->find() and folders()->create() handle UTF-7 encoding internally
        if ($mailbox->folders()->find($this->folder) === null) {
            $mailbox->folders()->create($this->folder);
        }

        // Message::move() requires UTF-7 encoded folder name (uses Str::literal(), not encoding)
        $folderName = Str::toImapUtf7($this->folder);
        $message->move($folderName, $this->expunge);
    }
}
