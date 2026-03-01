<?php

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\HasAttachmentMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(HasAttachmentMatcher::class)]
final class HasAttachmentMatcherTest extends TestCase
{
    public function testMatchesMessageWithAttachments(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('hasAttachments')->willReturn(true);

        $matcher = new HasAttachmentMatcher();

        self::assertTrue($matcher->matches($message));
    }

    public function testDoesNotMatchMessageWithoutAttachments(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('hasAttachments')->willReturn(false);

        $matcher = new HasAttachmentMatcher();

        self::assertFalse($matcher->matches($message));
    }
}
