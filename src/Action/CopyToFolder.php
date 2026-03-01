<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\Support\Str;
use MailboxRules\Action;

/**
 * Action that copies a message to a specified folder.
 *
 * Unlike MoveToFolder, this action duplicates the message,
 * leaving the original message in the source folder.
 *
 * Requires IMAP UIDPLUS capability on the mail server.
 */
final readonly class CopyToFolder implements Action
{
    public function __construct(
        private string $folder,
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

        // Message::copy() requires UTF-7 encoded folder name (uses Str::literal(), not encoding)
        $folderName = Str::toImapUtf7($this->folder);
        $message->copy($folderName);
    }
}
