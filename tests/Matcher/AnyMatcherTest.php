<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\AnyMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

use function MailboxRules\any;

#[CoversClass(AnyMatcher::class)]
final class AnyMatcherTest extends TestCase
{
    public function testAnyMatcherAlwaysReturnsTrue(): void
    {
        $anyMatcher = new AnyMatcher();
        $message = $this->createStub(Message::class);

        $this->assertTrue($anyMatcher->matches($message));
    }

    public function testAnyMatcherMatchesDifferentMessages(): void
    {
        $anyMatcher = new AnyMatcher();

        $message1 = $this->createStub(Message::class);
        $message2 = $this->createStub(Message::class);
        $message3 = $this->createStub(Message::class);

        $this->assertTrue($anyMatcher->matches($message1));
        $this->assertTrue($anyMatcher->matches($message2));
        $this->assertTrue($anyMatcher->matches($message3));
    }

    public function testAnyHelperFunctionReturnsAnyMatcher(): void
    {
        $matcher = any();

        $this->assertInstanceOf(AnyMatcher::class, $matcher);
    }

    public function testAnyHelperFunctionMatcherAlwaysReturnsTrue(): void
    {
        $matcher = any();
        $message = $this->createStub(Message::class);

        $this->assertTrue($matcher->matches($message));
    }
}
