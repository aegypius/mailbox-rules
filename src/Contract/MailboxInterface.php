<?php

declare(strict_types=1);

namespace MailboxRules\Contract;

/** The seam between Rules and a mail account. */
interface MailboxInterface
{
    /**
     * @return list<string> Folder paths, INBOX included.
     */
    public function folders(): array;

    /**
     * @param int|null $afterUid Only messages with a UID strictly above it; null for all.
     * @param int|null $limit At most this many messages, oldest first; null for all.
     * @return list<MessageInterface>
     * @throws \InvalidArgumentException when the Folder does not exist
     */
    public function messages(string $folderPath, ?int $afterUid = null, ?int $limit = null): array;

    /**
     * Path of the special-use \Trash Folder, null when the server designates none.
     */
    public function trash(): ?string;
}
