<?php

declare(strict_types=1);

namespace Tests\Action;

use MailboxRules\Action\MoveToFolder;
use MailboxRules\Contract\MessageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoveToFolder::class)]
final class MoveToFolderTest extends TestCase
{
    public function testMovesMessageToSpecifiedFolder(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Archive');

        $moveToFolder = new MoveToFolder('Archive');
        $moveToFolder($message);
    }

    public function testMovesMessageWithExpunge(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Trash', true);

        $moveToFolder = new MoveToFolder('Trash', expunge: true);
        $moveToFolder($message);
    }

    public function testMovesMessageWithoutExpungeByDefault(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Important', false);

        $moveToFolder = new MoveToFolder('Important');
        $moveToFolder($message);
    }

    public function testMovesToNestedFolder(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Projects/ClientA');

        $moveToFolder = new MoveToFolder('Projects/ClientA');
        $moveToFolder($message);
    }

    public function testPassesRawFolderNameWithAccentedCharacters(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Copropriété', false);

        $moveToFolder = new MoveToFolder('Copropriété');
        $moveToFolder($message);
    }

    public function testPassesRawFolderNameWithCyrillicCharacters(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Корзина', false);

        $moveToFolder = new MoveToFolder('Корзина');
        $moveToFolder($message);
    }

    public function testPassesRawFolderNameWithAmpersand(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('move')
            ->with('Inbox & Archive', false);

        $moveToFolder = new MoveToFolder('Inbox & Archive');
        $moveToFolder($message);
    }
}
