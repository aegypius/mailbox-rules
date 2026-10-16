<?php

declare(strict_types=1);

namespace Tests\Action;

use MailboxRules\Action\CopyToFolder;
use MailboxRules\Contract\MessageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CopyToFolder::class)]
final class CopyToFolderTest extends TestCase
{
    public function testCopiesToSpecifiedFolder(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Archive');

        $copyToFolder = new CopyToFolder('Archive');
        $copyToFolder($message);
    }

    public function testCopiesWithDifferentFolderName(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Important/Projects');

        $copyToFolder = new CopyToFolder('Important/Projects');
        $copyToFolder($message);
    }

    public function testPassesRawFolderNameWithAccentedCharacters(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Copropriété');

        $copyToFolder = new CopyToFolder('Copropriété');
        $copyToFolder($message);
    }

    public function testPassesRawFolderNameWithCyrillicCharacters(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Корзина');

        $copyToFolder = new CopyToFolder('Корзина');
        $copyToFolder($message);
    }

    public function testPassesRawFolderNameWithAmpersand(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('copy')
            ->with('Inbox & Archive');

        $copyToFolder = new CopyToFolder('Inbox & Archive');
        $copyToFolder($message);
    }
}
