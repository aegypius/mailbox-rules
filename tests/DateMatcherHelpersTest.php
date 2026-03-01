<?php

declare(strict_types=1);

namespace Tests;

use Carbon\Carbon;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\receivedAfter;
use function MailboxRules\receivedBefore;

#[CoversFunction('MailboxRules\receivedAfter')]
#[CoversFunction('MailboxRules\receivedBefore')]
final class DateMatcherHelpersTest extends TestCase
{
    public function test_received_after_returns_matcher(): void
    {
        $matcher = receivedAfter('2024-01-15 12:00:00');

        $this->assertInstanceOf(\MailboxRules\Matcher\Matcher::class, $matcher);
        $this->assertInstanceOf(\MailboxRules\Matcher\ReceivedAfterMatcher::class, $matcher);
    }

    public function test_received_before_returns_matcher(): void
    {
        $matcher = receivedBefore('2024-01-15 12:00:00');

        $this->assertInstanceOf(\MailboxRules\Matcher\Matcher::class, $matcher);
        $this->assertInstanceOf(\MailboxRules\Matcher\ReceivedBeforeMatcher::class, $matcher);
    }

    public function test_received_after_accepts_carbon_instance(): void
    {
        $date = Carbon::parse('2024-01-15 12:00:00');
        $matcher = receivedAfter($date);

        $this->assertInstanceOf(\MailboxRules\Matcher\ReceivedAfterMatcher::class, $matcher);
    }

    public function test_received_before_accepts_carbon_instance(): void
    {
        $date = Carbon::parse('2024-01-15 12:00:00');
        $matcher = receivedBefore($date);

        $this->assertInstanceOf(\MailboxRules\Matcher\ReceivedBeforeMatcher::class, $matcher);
    }
}
