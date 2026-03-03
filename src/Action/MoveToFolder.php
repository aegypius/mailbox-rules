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

        // Ensure the target folder exists, create recursively if needed
        $this->ensureFolderExists($mailbox, $this->folder);

        // Message::move() requires UTF-7 encoded folder name (uses Str::literal(), not encoding)
        $folderName = Str::toImapUtf7($this->folder);
        $message->move($folderName, $this->expunge);
    }

    /**
     * Ensures a folder exists, creating parent folders recursively if necessary.
     *
     * @param string $folderPath Path like "Parent/Child/GrandChild"
     */
    private function ensureFolderExists(\DirectoryTree\ImapEngine\MailboxInterface $mailbox, string $folderPath): void
    {
        $folders = $mailbox->folders();

        // Check if folder already exists
        if ($folders->find($folderPath) instanceof \DirectoryTree\ImapEngine\FolderInterface) {
            return;
        }

        // Split path into parts (e.g., "A/B/C" -> ["A", "B", "C"])
        $parts = explode('/', $folderPath);
        $currentPath = '';

        foreach ($parts as $part) {
            $currentPath = $currentPath === '' ? $part : $currentPath . '/' . $part;

            // Create folder if it doesn't exist
            // Note: folders()->find() and folders()->create() handle UTF-7 encoding internally
            if (!$folders->find($currentPath) instanceof \DirectoryTree\ImapEngine\FolderInterface) {
                $folders->create($currentPath);
            }
        }
    }
}
