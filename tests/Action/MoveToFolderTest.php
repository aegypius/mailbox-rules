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

        $action = new MoveToFolder('Archive');
        $action($message);
    }

    public function testMovesMessageWithExpunge(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Trash', true)
            ->willReturn(67890);

        $action = new MoveToFolder('Trash', expunge: true);
        $action($message);
    }

    public function testMovesMessageWithoutExpungeByDefault(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Important', false)
            ->willReturn(11111);

        $action = new MoveToFolder('Important');
        $action($message);
    }

    public function testMovesToNestedFolder(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Projects/ClientA')
            ->willReturn(22222);

        $action = new MoveToFolder('Projects/ClientA');
        $action($message);
    }

    public function testHandlesNullReturnFromMove(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Archive')
            ->willReturn(null);

        $action = new MoveToFolder('Archive');
        $action($message);

        // No exception thrown - action completes successfully
    }
}
