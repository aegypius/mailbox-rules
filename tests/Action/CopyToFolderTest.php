<?php

declare(strict_types=1);

namespace Tests\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\CopyToFolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CopyToFolder::class)]
final class CopyToFolderTest extends TestCase
{
    public function testCopiesToSpecifiedFolder(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Archive');

        $action = new CopyToFolder('Archive');
        $action($message);
    }

    public function testCopiesWithDifferentFolderName(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Important/Projects');

        $action = new CopyToFolder('Important/Projects');
        $action($message);
    }

    public function testEncodesAccentedCharactersToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Copropri&AOk-t&AOk-');

        $action = new CopyToFolder('Copropriété');
        $action($message);
    }

    public function testEncodesCyrillicCharactersToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('&BBoEPgRABDcEOAQ9BDA-');

        $action = new CopyToFolder('Корзина');
        $action($message);
    }

    public function testEncodesAmpersandToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Inbox &- Archive');

        $action = new CopyToFolder('Inbox & Archive');
        $action($message);
    }
}
