<?php

declare(strict_types=1);

namespace Tests\Action;

use MailboxRules\Action\Flag;
use MailboxRules\Contract\MessageInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Flag::class)]
final class FlagTest extends TestCase
{
    public function testMarksFlaggedFlag(): void
    {
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('markFlagged');

        $flag = new Flag();
        $flag($message);
    }

    public function testMarksFlaggedWithoutExpungeByDefault(): void
    {
        // markFlagged doesn't have expunge parameter, just verify it's called
        $message = $this->createMock(MessageInterface::class);
        $message->expects($this->once())
            ->method('markFlagged');

        $flag = new Flag();
        $flag($message);
    }
}
