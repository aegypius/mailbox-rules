<?php

declare(strict_types=1);

namespace MailboxRules\ValueObject;

use DirectoryTree\ImapEngine\MessageQueryInterface;

/**
 * Represents a folder scope for applying rules.
 *
 * Encapsulates:
 * - The folder path being queried (null = INBOX)
 * - The IMAP query to fetch messages
 * - Rules that should be applied in this scope
 */
final readonly class FolderScope
{
    /**
     * @param string|null $folderPath The folder path (null = INBOX)
     * @param MessageQueryInterface $query The IMAP query for this folder
     * @param list<\MailboxRules\Model\Rule> $rules Rules to apply in this scope
     */
    public function __construct(
        public ?string $folderPath,
        public MessageQueryInterface $query,
        public array $rules
    ) {
    }
}
