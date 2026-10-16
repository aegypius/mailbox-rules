<?php

declare(strict_types=1);

namespace Tests\Action;

use MailboxRules\Action\Delete;
use MailboxRules\Contract\MessageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Delete::class)]
final class DeleteTest extends TestCase
{
    public function testDeletesMessageWithExpungeByDefault(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('delete')
            ->with(true);

        $delete = new Delete();
        $delete($message);
    }

    public function testDeletesMessageWithoutExpungeWhenSpecified(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('delete')
            ->with(false);

        $delete = new Delete(expunge: false);
        $delete($message);
    }

    public function testDeletesMessageWithExpungeWhenExplicitlyEnabled(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects(self::once())
            ->method('delete')
            ->with(true);

        $delete = new Delete(expunge: true);
        $delete($message);
    }
}
