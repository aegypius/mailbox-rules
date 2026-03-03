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

        $copyToFolder = new CopyToFolder('Archive');
        $copyToFolder($message);
    }

    public function testCopiesWithDifferentFolderName(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Important/Projects');

        $copyToFolder = new CopyToFolder('Important/Projects');
        $copyToFolder($message);
    }

    public function testEncodesAccentedCharactersToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Copropri&AOk-t&AOk-');

        $copyToFolder = new CopyToFolder('Copropriété');
        $copyToFolder($message);
    }

    public function testEncodesCyrillicCharactersToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('&BBoEPgRABDcEOAQ9BDA-');

        $copyToFolder = new CopyToFolder('Корзина');
        $copyToFolder($message);
    }

    public function testEncodesAmpersandToModifiedUtf7(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Inbox &- Archive');

        $copyToFolder = new CopyToFolder('Inbox & Archive');
        $copyToFolder($message);
    }
}
