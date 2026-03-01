<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\LargerThanMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(LargerThanMatcher::class)]
final class LargerThanMatcherTest extends TestCase
{
    public function test_matches_message_larger_than_bytes(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(2048);

        $matcher = new LargerThanMatcher(1024);

        $this->assertTrue($matcher->matches($message));
    }

    public function test_does_not_match_message_smaller_than_bytes(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(512);

        $matcher = new LargerThanMatcher(1024);

        $this->assertFalse($matcher->matches($message));
    }

    public function test_does_not_match_message_equal_to_bytes(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(1024);

        $matcher = new LargerThanMatcher(1024);

        $this->assertFalse($matcher->matches($message));
    }

    public function test_does_not_match_message_with_null_size(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(null);

        $matcher = new LargerThanMatcher(1024);

        $this->assertFalse($matcher->matches($message));
    }

    public function test_accepts_human_readable_size_kb(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(2048);

        $matcher = new LargerThanMatcher('1KB');

        $this->assertTrue($matcher->matches($message));
    }

    public function test_accepts_human_readable_size_mb(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(2_097_152); // 2MB

        $matcher = new LargerThanMatcher('1MB');

        $this->assertTrue($matcher->matches($message));
    }

    public function test_accepts_human_readable_size_gb(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(2_147_483_648); // 2GB

        $matcher = new LargerThanMatcher('1GB');

        $this->assertTrue($matcher->matches($message));
    }

    public function test_accepts_lowercase_units(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(2048);

        $matcher = new LargerThanMatcher('1kb');

        $this->assertTrue($matcher->matches($message));
    }

    public function test_accepts_size_with_space(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(2048);

        $matcher = new LargerThanMatcher('1 KB');

        $this->assertTrue($matcher->matches($message));
    }
}
