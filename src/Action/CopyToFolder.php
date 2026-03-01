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
        $message->copy(Str::toImapUtf7($this->folder));
    }
}
