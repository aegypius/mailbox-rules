<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\Matcher;
use MailboxRules\Matcher\NotMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(NotMatcher::class)]
final class NotMatcherTest extends TestCase
{
    public function testInvertsMatcherReturningTrue(): void
    {
        $message = $this->createStub(Message::class);
        $matcher = $this->createMatcherThatReturns(true);

        $notMatcher = new NotMatcher($matcher);

        $this->assertFalse($notMatcher->matches($message));
    }

    public function testInvertsMatcherReturningFalse(): void
    {
        $message = $this->createStub(Message::class);
        $matcher = $this->createMatcherThatReturns(false);

        $notMatcher = new NotMatcher($matcher);

        $this->assertTrue($notMatcher->matches($message));
    }

    public function testInvertsRealMatcherTrue(): void
    {
        $message = $this->createStub(Message::class);

        $innerMatcher = $this->createMatcherThatChecks(fn (): bool => true);

        $notMatcher = new NotMatcher($innerMatcher);

        $this->assertFalse($notMatcher->matches($message));
    }

    public function testInvertsRealMatcherFalse(): void
    {
        $message = $this->createStub(Message::class);

        $innerMatcher = $this->createMatcherThatChecks(fn (): bool => false);

        $notMatcher = new NotMatcher($innerMatcher);

        $this->assertTrue($notMatcher->matches($message));
    }

    public function testDoubleNegation(): void
    {
        $message = $this->createStub(Message::class);
        $matcher = $this->createMatcherThatReturns(true);

        $notNotMatcher = new NotMatcher(new NotMatcher($matcher));

        $this->assertTrue($notNotMatcher->matches($message));
    }

    public function testIntegrationWithFromMatcher(): void
    {
        $message = $this->createMessageFrom('user@example.com');

        $fromMatcher = $this->createMatcherThatChecks(fn (Message $message): bool => $message->from()?->email() === 'user@example.com');

        $notMatcher = new NotMatcher($fromMatcher);

        $this->assertFalse($notMatcher->matches($message));
    }

    public function testIntegrationWithFromMatcherNegative(): void
    {
        $message = $this->createMessageFrom('other@example.com');

        $fromMatcher = $this->createMatcherThatChecks(fn (Message $message): bool => $message->from()?->email() === 'user@example.com');

        $notMatcher = new NotMatcher($fromMatcher);

        $this->assertTrue($notMatcher->matches($message));
    }

    private function createMatcherThatReturns(bool $result): Matcher
    {
        $matcher = $this->createStub(Matcher::class);
        $matcher->method('matches')->willReturn($result);

        return $matcher;
    }

    private function createMatcherThatChecks(callable $callback): Matcher
    {
        $matcher = $this->createStub(Matcher::class);
        $matcher->method('matches')->willReturnCallback($callback);

        return $matcher;
    }

    private function createMessageFrom(string $email): Message
    {
        $message = $this->createStub(Message::class);
        $address = $this->createStub(\DirectoryTree\ImapEngine\Address::class);
        $address->method('email')->willReturn($email);
        $message->method('from')->willReturn($address);

        return $message;
    }
}
