<?php

declare(strict_types=1);

namespace MailboxRules\Contract;

use Carbon\CarbonInterface;
use MailboxRules\ValueObject\EmailAddress;

interface MessageInterface
{
    public function from(): ?EmailAddress;

    /**
     * @return array<EmailAddress>
     */
    public function to(): array;

    /**
     * @return array<EmailAddress>
     */
    public function cc(): array;

    /**
     * @return array<EmailAddress>
     */
    public function bcc(): array;

    public function subject(): ?string;

    public function date(): ?CarbonInterface;

    public function size(): ?int;

    public function text(): ?string;

    public function html(): ?string;

    public function hasAttachments(): bool;

    /**
     * @return array<AttachmentInterface>
     */
    public function attachments(): array;

    public function uid(): int;

    public function folderPath(): string;

    public function move(string $folder, bool $expunge = false): void;

    public function copy(string $folder): void;

    public function markFlagged(): void;

    public function unmarkFlagged(): void;

    public function markRead(): void;

    public function markUnread(): void;

    public function delete(bool $expunge = false): void;
}
