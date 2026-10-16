<?php

declare(strict_types=1);

namespace Tests\Matcher;

use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\Matcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Matcher::class)]
final class MatcherTest extends TestCase
{
    public function testMatcherCanBeImplemented(): void
    {
        $matcher = new class() implements Matcher {
            use \MailboxRules\Matcher\UnscopedMatcher;

            public function matches(MessageInterface $message): bool
            {
                return true;
            }
        };

        $this->assertInstanceOf(Matcher::class, $matcher);
        $this->assertTrue($matcher->matches($this->createStub(MessageInterface::class)));
    }
}
