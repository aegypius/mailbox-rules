<?php

declare(strict_types=1);

namespace Tests\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\Unflag;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Unflag::class)]
class UnflagTest extends TestCase
{
    public function testRemovesFlagFromMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects($this->once())
            ->method('unmarkFlagged');

        $unflag = new Unflag();
        $unflag($message);
    }
}
