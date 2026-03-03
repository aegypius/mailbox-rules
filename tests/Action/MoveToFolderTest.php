<?php

declare(strict_types=1);

namespace Tests\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\MoveToFolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoveToFolder::class)]
final class MoveToFolderTest extends TestCase
{
    public function testMovesMessageToSpecifiedFolder(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Archive')
            ->willReturn(12345);

        $moveToFolder = new MoveToFolder('Archive');
        $moveToFolder($message);
    }

    public function testMovesMessageWithExpunge(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Trash', true)
            ->willReturn(67890);

        $moveToFolder = new MoveToFolder('Trash', expunge: true);
        $moveToFolder($message);
    }

    public function testMovesMessageWithoutExpungeByDefault(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Important', false)
            ->willReturn(11111);

        $moveToFolder = new MoveToFolder('Important');
        $moveToFolder($message);
    }

    public function testMovesToNestedFolder(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Projects/ClientA')
            ->willReturn(22222);

        $moveToFolder = new MoveToFolder('Projects/ClientA');
        $moveToFolder($message);
    }

    public function testHandlesNullReturnFromMove(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Archive')
            ->willReturn(null);

        $moveToFolder = new MoveToFolder('Archive');
        $moveToFolder($message);

        // No exception thrown - action completes successfully
    }

    public function testEncodesAccentedCharactersToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Copropri&AOk-t&AOk-', false)
            ->willReturn(12345);

        $moveToFolder = new MoveToFolder('Copropriété');
        $moveToFolder($message);
    }

    public function testEncodesCyrillicCharactersToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('&BBoEPgRABDcEOAQ9BDA-', false)
            ->willReturn(67890);

        $moveToFolder = new MoveToFolder('Корзина');
        $moveToFolder($message);
    }

    public function testEncodesAmpersandToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Inbox &- Archive', false)
            ->willReturn(11111);

        $moveToFolder = new MoveToFolder('Inbox & Archive');
        $moveToFolder($message);
    }
}
