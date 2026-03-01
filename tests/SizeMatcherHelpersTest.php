<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\largerThan;
use function MailboxRules\smallerThan;

#[CoversFunction('MailboxRules\largerThan')]
#[CoversFunction('MailboxRules\smallerThan')]
final class SizeMatcherHelpersTest extends TestCase
{
    public function test_larger_than_returns_matcher(): void
    {
        $matcher = largerThan(1024);

        $this->assertInstanceOf(\MailboxRules\Matcher\Matcher::class, $matcher);
        $this->assertInstanceOf(\MailboxRules\Matcher\LargerThanMatcher::class, $matcher);
    }

    public function test_smaller_than_returns_matcher(): void
    {
        $matcher = smallerThan(1024);

        $this->assertInstanceOf(\MailboxRules\Matcher\Matcher::class, $matcher);
        $this->assertInstanceOf(\MailboxRules\Matcher\SmallerThanMatcher::class, $matcher);
    }

    public function test_larger_than_accepts_human_readable_size(): void
    {
        $matcher = largerThan('1MB');

        $this->assertInstanceOf(\MailboxRules\Matcher\LargerThanMatcher::class, $matcher);
    }

    public function test_smaller_than_accepts_human_readable_size(): void
    {
        $matcher = smallerThan('1MB');

        $this->assertInstanceOf(\MailboxRules\Matcher\SmallerThanMatcher::class, $matcher);
    }
}
