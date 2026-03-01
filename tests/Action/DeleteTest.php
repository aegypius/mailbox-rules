<?php

declare(strict_types=1);

namespace Tests\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\Delete;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Delete::class)]
final class DeleteTest extends TestCase
{
    public function testDeletesMessageWithExpungeByDefault(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('delete')
            ->with(true);

        $action = new Delete();
        $action($message);
    }

    public function testDeletesMessageWithoutExpungeWhenSpecified(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('delete')
            ->with(false);

        $action = new Delete(expunge: false);
        $action($message);
    }

    public function testDeletesMessageWithExpungeWhenExplicitlyEnabled(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('delete')
            ->with(true);

        $action = new Delete(expunge: true);
        $action($message);
    }
}
