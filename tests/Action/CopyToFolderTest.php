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
}
