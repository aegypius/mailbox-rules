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

        $hasAttachmentMatcher = new HasAttachmentMatcher();

        self::assertTrue($hasAttachmentMatcher->matches($message));
    }

    public function testDoesNotMatchMessageWithoutAttachments(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('hasAttachments')->willReturn(false);

        $hasAttachmentMatcher = new HasAttachmentMatcher();

        self::assertFalse($hasAttachmentMatcher->matches($message));
    }
}
