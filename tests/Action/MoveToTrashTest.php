<?php

declare(strict_types=1);

namespace Tests\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\MoveToTrash;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoveToTrash::class)]
final class MoveToTrashTest extends TestCase
{
    public function testMovesToDefaultTrashFolder(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('move')
            ->with('Trash', false);

        $moveToTrash = new MoveToTrash();
        $moveToTrash($message);
    }

    public function testMovesToCustomTrashFolder(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('move')
            ->with('[Gmail]/Trash', false);

        $moveToTrash = new MoveToTrash(trashFolder: '[Gmail]/Trash');
        $moveToTrash($message);
    }

    public function testMovesToTrashWithExpunge(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('move')
            ->with('Deleted Items', true);

        $moveToTrash = new MoveToTrash(trashFolder: 'Deleted Items', expunge: true);
        $moveToTrash($message);
    }
}
