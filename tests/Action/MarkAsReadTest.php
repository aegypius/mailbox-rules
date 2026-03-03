<?php

declare(strict_types=1);

namespace Tests\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\MarkAsRead;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarkAsRead::class)]
final class MarkAsReadTest extends TestCase
{
    public function testMarksMessageAsRead(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('markRead');

        $markAsRead = new MarkAsRead();
        $markAsRead($message);
    }

    public function testMarksMessageAsReadAlternativeName(): void
    {
        // markRead() is an alias for markSeen()
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('markRead');

        $markAsRead = new MarkAsRead();
        $markAsRead($message);
    }
}
