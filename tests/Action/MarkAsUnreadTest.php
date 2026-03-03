<?php

declare(strict_types=1);

namespace Tests\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\MarkAsUnread;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MarkAsUnread::class)]
class MarkAsUnreadTest extends TestCase
{
    public function testMarksMessageAsUnread(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('markUnread');

        $markAsUnread = new MarkAsUnread();
        $markAsUnread($message);
    }
}
