<?php

declare(strict_types=1);

namespace MailboxRules\Adapter;

use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Contract\MailboxInterface;

final readonly class ImapMailbox implements MailboxInterface
{
    public function __construct(private Mailbox $mailbox)
    {
    }

    public function folders(): array
    {
        return array_values(array_map(
            static fn (FolderInterface $folder): string => $folder->path(),
            $this->connected()->folders()->get()->all(),
        ));
    }

    public function messages(string $folderPath, ?int $afterUid = null): array
    {
        $folder = $this->connected()->folders()->find($folderPath)
            ?? throw new \InvalidArgumentException(sprintf('Folder not found: %s', $folderPath));

        $adapters = [];
        foreach ($folder->messages()->withHeaders()->get() as $message) {
            assert($message instanceof Message);
            if ($afterUid === null || $message->uid() > $afterUid) {
                $adapters[] = new ImapMessageAdapter($message);
            }
        }

        return $adapters;
    }

    public function trash(): ?string
    {
        foreach ($this->connected()->folders()->get() as $folder) {
            if (in_array('\\trash', array_map('strtolower', $folder->flags()), true)) {
                return $folder->path();
            }
        }

        return null;
    }

    private function connected(): Mailbox
    {
        if (!$this->mailbox->connected()) {
            $this->mailbox->connect();
        }

        return $this->mailbox;
    }
}
