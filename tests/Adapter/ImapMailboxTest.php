<?php

declare(strict_types=1);

namespace Tests\Adapter;

use DirectoryTree\ImapEngine\Collections\FolderCollection;
use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\FolderRepository;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\MessageQuery;
use MailboxRules\Adapter\ImapMailbox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImapMailbox::class)]
final class ImapMailboxTest extends TestCase
{
    /**
     * @param array<string, list<string>> $flagsByPath
     */
    private function mailbox(array $flagsByPath, ?Folder $folderToFind = null): Mailbox
    {
        $folders = [];
        foreach ($flagsByPath as $path => $flags) {
            $folder = $this->createStub(Folder::class);
            $folder->method('path')->willReturn($path);
            $folder->method('flags')->willReturn($flags);
            $folders[] = $folder;
        }

        $repository = $this->createStub(FolderRepository::class);
        $repository->method('get')->willReturn(new FolderCollection($folders));
        $repository->method('find')->willReturn($folderToFind);

        $mailbox = $this->createMock(Mailbox::class);
        $mailbox->method('connected')->willReturn(false);
        $mailbox->expects($this->atLeastOnce())->method('connect');
        $mailbox->method('folders')->willReturn($repository);

        return $mailbox;
    }

    #[Test]
    public function lists_folder_paths(): void
    {
        $port = new ImapMailbox($this->mailbox([
            'INBOX' => [],
            'Work' => ['\\HasNoChildren'],
        ]));

        self::assertSame(['INBOX', 'Work'], $port->folders());
    }

    #[Test]
    public function finds_the_special_use_trash_folder(): void
    {
        $port = new ImapMailbox($this->mailbox([
            'INBOX' => [],
            '[Gmail]/Trash' => ['\\HasNoChildren', '\\Trash'],
        ]));

        self::assertSame('[Gmail]/Trash', $port->trash());
    }

    #[Test]
    public function has_no_trash_without_the_special_use_flag(): void
    {
        $port = new ImapMailbox($this->mailbox([
            'INBOX' => [],
            'Trash' => [],
        ]));

        self::assertNull($port->trash());
    }

    #[Test]
    public function fetches_messages_above_the_uid_floor(): void
    {
        $calls = [];
        $messages = [];
        foreach ([3, 7, 9] as $uid) {
            $message = $this->createStub(Message::class);
            $message->method('uid')->willReturn($uid);
            $messages[] = $message;
        }

        $query = $this->createStub(MessageQuery::class);
        $query->method('withHeaders')->willReturn($query);
        $query->method('get')->willReturn(new MessageCollection($messages));
        $query->method('__call')->willReturnCallback(function (string $method, array $args) use ($query, &$calls) {
            $calls[] = [$method, $args];

            return $query;
        });
        $query->method('limit')->willReturnCallback(function (int $limit) use ($query, &$calls) {
            $calls[] = ['limit', [$limit]];

            return $query;
        });
        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $port = new ImapMailbox($this->mailbox([
            'INBOX' => [],
        ], $folder));

        self::assertSame([7, 9], array_map(static fn ($m): int => $m->uid(), $port->messages('INBOX', 3)));
        self::assertSame([['uid', [4, INF]]], $calls);
        self::assertCount(3, $port->messages('INBOX'));

        $calls = [];
        $port->messages('INBOX', limit: 2);
        self::assertSame([['limit', [2]]], $calls);
    }

    #[Test]
    public function fetching_an_unknown_folder_fails(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        (new ImapMailbox($this->mailbox([
            'INBOX' => [],
        ])))->messages('Nope');
    }
}
