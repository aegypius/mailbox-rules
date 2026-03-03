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

        $delete = new Delete();
        $delete($message);
    }

    public function testDeletesMessageWithoutExpungeWhenSpecified(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('delete')
            ->with(false);

        $delete = new Delete(expunge: false);
        $delete($message);
    }

    public function testDeletesMessageWithExpungeWhenExplicitlyEnabled(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())
            ->method('delete')
            ->with(true);

        $delete = new Delete(expunge: true);
        $delete($message);
    }
}
