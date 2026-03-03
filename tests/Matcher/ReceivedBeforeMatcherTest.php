<?php

declare(strict_types=1);

namespace Tests\Matcher;

use Carbon\Carbon;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\ReceivedBeforeMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ReceivedBeforeMatcher::class)]
final class ReceivedBeforeMatcherTest extends TestCase
{
    public function test_matches_message_received_before_absolute_datetime(): void
    {
        $cutoff = Carbon::parse('2024-01-15 12:00:00');
        $messageDate = Carbon::parse('2024-01-14 10:00:00');

        $message = $this->createStub(Message::class);
        $message->method('date')->willReturn($messageDate);

        $receivedBeforeMatcher = new ReceivedBeforeMatcher($cutoff);

        $this->assertTrue($receivedBeforeMatcher->matches($message));
    }

    public function test_does_not_match_message_received_after_datetime(): void
    {
        $cutoff = Carbon::parse('2024-01-15 12:00:00');
        $messageDate = Carbon::parse('2024-01-16 10:00:00');

        $message = $this->createStub(Message::class);
        $message->method('date')->willReturn($messageDate);

        $receivedBeforeMatcher = new ReceivedBeforeMatcher($cutoff);

        $this->assertFalse($receivedBeforeMatcher->matches($message));
    }

    public function test_does_not_match_message_received_at_exact_datetime(): void
    {
        $cutoff = Carbon::parse('2024-01-15 12:00:00');
        $messageDate = Carbon::parse('2024-01-15 12:00:00');

        $message = $this->createStub(Message::class);
        $message->method('date')->willReturn($messageDate);

        $receivedBeforeMatcher = new ReceivedBeforeMatcher($cutoff);

        $this->assertFalse($receivedBeforeMatcher->matches($message));
    }

    public function test_does_not_match_message_with_null_date(): void
    {
        $cutoff = Carbon::parse('2024-01-15 12:00:00');

        $message = $this->createStub(Message::class);
        $message->method('date')->willReturn(null);

        $receivedBeforeMatcher = new ReceivedBeforeMatcher($cutoff);

        $this->assertFalse($receivedBeforeMatcher->matches($message));
    }

    public function test_accepts_string_datetime(): void
    {
        $messageDate = Carbon::parse('2024-01-14 10:00:00');

        $message = $this->createStub(Message::class);
        $message->method('date')->willReturn($messageDate);

        $receivedBeforeMatcher = new ReceivedBeforeMatcher('2024-01-15 12:00:00');

        $this->assertTrue($receivedBeforeMatcher->matches($message));
    }

    public function test_accepts_relative_datetime_string(): void
    {
        $messageDate = Carbon::now()->subHours(4);

        $message = $this->createStub(Message::class);
        $message->method('date')->willReturn($messageDate);

        $receivedBeforeMatcher = new ReceivedBeforeMatcher('3 hours ago');

        $this->assertTrue($receivedBeforeMatcher->matches($message));
    }

    public function test_does_not_match_recent_message_with_relative_datetime(): void
    {
        $messageDate = Carbon::now()->subHours(2);

        $message = $this->createStub(Message::class);
        $message->method('date')->willReturn($messageDate);

        $receivedBeforeMatcher = new ReceivedBeforeMatcher('3 hours ago');

        $this->assertFalse($receivedBeforeMatcher->matches($message));
    }
}
