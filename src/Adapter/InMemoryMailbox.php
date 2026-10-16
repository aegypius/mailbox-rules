<?php

declare(strict_types=1);

namespace MailboxRules\Adapter;

use Carbon\CarbonInterface;
use DirectoryTree\ImapEngine\Address;
use MailboxRules\Contract\MailboxInterface;

final class InMemoryMailbox implements MailboxInterface
{
    /**
     * @var array<string, list<InMemoryMessage>>
     */
    private array $messages = [
        'INBOX' => [],
    ];

    /**
     * @var array<string, list<string>>
     */
    private array $flags = [
        'INBOX' => [],
    ];

    private int $nextUid = 1;

    public function __construct(string ...$folders)
    {
        foreach ($folders as $folder) {
            $this->addFolder($folder);
        }
    }

    public function addFolder(string $path, string ...$flags): void
    {
        $this->messages[$path] ??= [];
        $this->flags[$path] = array_values($flags);
    }

    public function deliver(
        string $folder,
        ?string $subject = null,
        ?Address $from = null,
        ?string $text = null,
        ?int $size = null,
        ?CarbonInterface $date = null,
    ): InMemoryMessage {
        $message = new InMemoryMessage($this, $folder, $subject, $from, $text, $size, $date);
        $this->place($message, $folder);

        return $message;
    }

    public function folders(): array
    {
        return array_keys($this->messages);
    }

    public function messages(string $folderPath, ?int $afterUid = null, ?int $limit = null): array
    {
        $messages = $this->messages[$folderPath]
            ?? throw new \InvalidArgumentException(sprintf('Folder not found: %s', $folderPath));

        $messages = array_values(array_filter(
            $messages,
            static fn (InMemoryMessage $m): bool => $afterUid === null || $m->uid() > $afterUid,
        ));

        return $limit === null ? $messages : array_slice($messages, 0, $limit);
    }

    public function trash(): ?string
    {
        foreach ($this->flags as $path => $flags) {
            if (in_array('\\Trash', $flags, true)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * @internal for InMemoryMessage
     */
    public function relocate(InMemoryMessage $message, string $folder): void
    {
        $this->remove($message);
        $this->place($message, $folder);
    }

    /**
     * @internal for InMemoryMessage
     */
    public function remove(InMemoryMessage $message): void
    {
        $path = $message->folderPath();
        $this->messages[$path] = array_values(array_filter(
            $this->messages[$path],
            static fn (InMemoryMessage $m): bool => $m !== $message,
        ));
    }

    /**
     * @internal for InMemoryMessage
     */
    public function place(InMemoryMessage $message, string $folder): void
    {
        $current = '';
        foreach (isset($this->messages[$folder]) ? [] : explode('/', $folder) as $part) {
            $current = $current === '' ? $part : $current . '/' . $part;
            $this->messages[$current] ??= [];
            $this->flags[$current] ??= [];
        }

        $message->assign($folder, $this->nextUid++);
        $this->messages[$folder][] = $message;
    }
}
