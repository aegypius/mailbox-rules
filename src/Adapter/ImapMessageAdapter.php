<?php

declare(strict_types=1);

namespace MailboxRules\Adapter;

use Carbon\CarbonInterface;
use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Attachment;
use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\MailboxInterface;
use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\Support\Str;
use MailboxRules\Contract\AttachmentInterface;
use MailboxRules\Contract\MessageInterface;

final readonly class ImapMessageAdapter implements MessageInterface
{
    public function __construct(private Message $message)
    {
    }

    public function from(): ?Address
    {
        return $this->message->from();
    }

    /**
     * @return array<Address>
     */
    public function to(): array
    {
        return $this->message->to();
    }

    /**
     * @return array<Address>
     */
    public function cc(): array
    {
        return $this->message->cc();
    }

    /**
     * @return array<Address>
     */
    public function bcc(): array
    {
        return $this->message->bcc();
    }

    public function subject(): ?string
    {
        return $this->message->subject();
    }

    public function date(): ?CarbonInterface
    {
        return $this->message->date();
    }

    public function size(): ?int
    {
        return $this->message->size();
    }

    public function text(): ?string
    {
        return $this->message->text();
    }

    public function html(): ?string
    {
        return $this->message->html();
    }

    public function hasAttachments(): bool
    {
        return $this->message->hasAttachments();
    }

    /**
     * @return array<AttachmentInterface>
     */
    public function attachments(): array
    {
        return array_map(
            static fn (Attachment $attachment): AttachmentInterface => new ImapAttachmentAdapter($attachment),
            $this->message->attachments()
        );
    }

    public function folderPath(): string
    {
        return $this->message->folder()->path();
    }

    public function move(string $folder, bool $expunge = false): void
    {
        $this->ensureFolderExists($this->message->folder()->mailbox(), $folder);
        $this->message->move(Str::toImapUtf7($folder), $expunge);
    }

    public function copy(string $folder): void
    {
        $this->ensureFolderExists($this->message->folder()->mailbox(), $folder);
        $this->message->copy(Str::toImapUtf7($folder));
    }

    public function markFlagged(): void
    {
        $this->message->markFlagged();
    }

    public function unmarkFlagged(): void
    {
        $this->message->unmarkFlagged();
    }

    public function markRead(): void
    {
        $this->message->markRead();
    }

    public function markUnread(): void
    {
        $this->message->markUnread();
    }

    public function delete(bool $expunge = false): void
    {
        $this->message->delete($expunge);
    }

    private function ensureFolderExists(MailboxInterface $mailbox, string $folderPath): void
    {
        $folders = $mailbox->folders();

        if ($folders->find($folderPath) instanceof FolderInterface) {
            return;
        }

        $parts = explode('/', $folderPath);
        $currentPath = '';

        foreach ($parts as $part) {
            $currentPath = $currentPath === '' ? $part : $currentPath . '/' . $part;

            if (!$folders->find($currentPath) instanceof FolderInterface) {
                $folders->create($currentPath);
            }
        }
    }
}
