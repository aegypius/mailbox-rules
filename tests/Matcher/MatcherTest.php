<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\Matcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matcher::class)]
final class MatcherTest extends TestCase
{
    public function testMatcherCanBeImplemented(): void
    {
        $matcher = new class() implements Matcher {
            public function matches(Message $message): bool
            {
                return true;
            }
        };

        $this->assertInstanceOf(Matcher::class, $matcher);
        $this->assertTrue($matcher->matches($this->createStub(Message::class)));
    }
}
