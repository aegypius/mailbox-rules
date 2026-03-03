<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\SmallerThanMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(SmallerThanMatcher::class)]
final class SmallerThanMatcherTest extends TestCase
{
    public function test_matches_message_smaller_than_bytes(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(512);

        $smallerThanMatcher = new SmallerThanMatcher(1024);

        $this->assertTrue($smallerThanMatcher->matches($message));
    }

    public function test_does_not_match_message_larger_than_bytes(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(2048);

        $smallerThanMatcher = new SmallerThanMatcher(1024);

        $this->assertFalse($smallerThanMatcher->matches($message));
    }

    public function test_does_not_match_message_equal_to_bytes(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(1024);

        $smallerThanMatcher = new SmallerThanMatcher(1024);

        $this->assertFalse($smallerThanMatcher->matches($message));
    }

    public function test_does_not_match_message_with_null_size(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(null);

        $smallerThanMatcher = new SmallerThanMatcher(1024);

        $this->assertFalse($smallerThanMatcher->matches($message));
    }

    public function test_accepts_human_readable_size_kb(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(512);

        $smallerThanMatcher = new SmallerThanMatcher('1KB');

        $this->assertTrue($smallerThanMatcher->matches($message));
    }

    public function test_accepts_human_readable_size_mb(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(524_288); // 512KB

        $smallerThanMatcher = new SmallerThanMatcher('1MB');

        $this->assertTrue($smallerThanMatcher->matches($message));
    }

    public function test_accepts_human_readable_size_gb(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(536_870_912); // 512MB

        $smallerThanMatcher = new SmallerThanMatcher('1GB');

        $this->assertTrue($smallerThanMatcher->matches($message));
    }

    public function test_accepts_lowercase_units(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(512);

        $smallerThanMatcher = new SmallerThanMatcher('1kb');

        $this->assertTrue($smallerThanMatcher->matches($message));
    }

    public function test_accepts_size_with_space(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(512);

        $smallerThanMatcher = new SmallerThanMatcher('1 KB');

        $this->assertTrue($smallerThanMatcher->matches($message));
    }
}
