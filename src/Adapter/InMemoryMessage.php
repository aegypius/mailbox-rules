<?php

declare(strict_types=1);

namespace MailboxRules\Adapter;

use Carbon\CarbonInterface;
use DirectoryTree\ImapEngine\Address;
use MailboxRules\Contract\MessageInterface;

final class InMemoryMessage implements MessageInterface
{
    private int $uid = 0;

    private bool $flagged = false;

    private bool $read = false;

    private bool $deleted = false;

    public function __construct(
        private readonly InMemoryMailbox $mailbox,
        private string $folder,
        private readonly ?string $subject = null,
        private readonly ?Address $from = null,
        private readonly ?string $text = null,
        private readonly ?int $size = null,
        private readonly ?CarbonInterface $date = null,
    ) {
    }

    /**
     * @internal for InMemoryMailbox
     */
    public function assign(string $folder, int $uid): void
    {
        $this->folder = $folder;
        $this->uid = $uid;
    }

    public function isFlagged(): bool
    {
        return $this->flagged;
    }

    public function isRead(): bool
    {
        return $this->read;
    }

    public function isDeleted(): bool
    {
        return $this->deleted;
    }

    public function from(): ?Address
    {
        return $this->from;
    }

    public function to(): array
    {
        return [];
    }

    public function cc(): array
    {
        return [];
    }

    public function bcc(): array
    {
        return [];
    }

    public function subject(): ?string
    {
        return $this->subject;
    }

    public function date(): ?CarbonInterface
    {
        return $this->date;
    }

    public function size(): ?int
    {
        return $this->size;
    }

    public function text(): ?string
    {
        return $this->text;
    }

    public function html(): ?string
    {
        return null;
    }

    public function hasAttachments(): bool
    {
        return false;
    }

    public function attachments(): array
    {
        return [];
    }

    public function uid(): int
    {
        return $this->uid;
    }

    public function folderPath(): string
    {
        return $this->folder;
    }

    public function move(string $folder, bool $expunge = false): void
    {
        $this->mailbox->relocate($this, $folder);
    }

    public function copy(string $folder): void
    {
        $this->mailbox->place(
            new self($this->mailbox, $folder, $this->subject, $this->from, $this->text, $this->size, $this->date),
            $folder,
        );
    }

    public function markFlagged(): void
    {
        $this->flagged = true;
    }

    public function unmarkFlagged(): void
    {
        $this->flagged = false;
    }

    public function markRead(): void
    {
        $this->read = true;
    }

    public function markUnread(): void
    {
        $this->read = false;
    }

    public function delete(bool $expunge = false): void
    {
        $this->deleted = true;
        if ($expunge) {
            $this->mailbox->remove($this);
        }
    }
}
