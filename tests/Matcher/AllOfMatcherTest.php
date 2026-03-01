<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\Matcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AllOfMatcher::class)]
final class AllOfMatcherTest extends TestCase
{
    #[Test]
    public function it_matches_when_all_matchers_match(): void
    {
        $matcher1 = $this->createMatcherThatReturns(true);
        $matcher2 = $this->createMatcherThatReturns(true);
        $matcher3 = $this->createMatcherThatReturns(true);

        $allOf = new AllOfMatcher($matcher1, $matcher2, $matcher3);
        $message = $this->createStub(Message::class);

        $this->assertTrue($allOf->matches($message));
    }

    #[Test]
    public function it_does_not_match_when_one_matcher_fails(): void
    {
        $matcher1 = $this->createMatcherThatReturns(true);
        $matcher2 = $this->createMatcherThatReturns(false);
        $matcher3 = $this->createMatcherThatReturns(true);

        $allOf = new AllOfMatcher($matcher1, $matcher2, $matcher3);
        $message = $this->createStub(Message::class);

        $this->assertFalse($allOf->matches($message));
    }

    #[Test]
    public function it_does_not_match_when_all_matchers_fail(): void
    {
        $matcher1 = $this->createMatcherThatReturns(false);
        $matcher2 = $this->createMatcherThatReturns(false);
        $matcher3 = $this->createMatcherThatReturns(false);

        $allOf = new AllOfMatcher($matcher1, $matcher2, $matcher3);
        $message = $this->createStub(Message::class);

        $this->assertFalse($allOf->matches($message));
    }

    #[Test]
    public function it_matches_with_single_matcher(): void
    {
        $matcher = $this->createMatcherThatReturns(true);

        $allOf = new AllOfMatcher($matcher);
        $message = $this->createStub(Message::class);

        $this->assertTrue($allOf->matches($message));
    }

    #[Test]
    public function it_does_not_match_with_single_failing_matcher(): void
    {
        $matcher = $this->createMatcherThatReturns(false);

        $allOf = new AllOfMatcher($matcher);
        $message = $this->createStub(Message::class);

        $this->assertFalse($allOf->matches($message));
    }

    #[Test]
    public function it_short_circuits_on_first_failure(): void
    {
        $matcher1 = $this->createMatcherThatReturns(true);
        $matcher2 = $this->createMatcherThatReturns(false);
        $matcher3 = $this->createMock(Matcher::class);
        $matcher3->expects($this->never())->method('matches');

        $allOf = new AllOfMatcher($matcher1, $matcher2, $matcher3);
        $message = $this->createStub(Message::class);

        $this->assertFalse($allOf->matches($message));
    }

    #[Test]
    public function it_works_with_real_matchers(): void
    {
        $fromAddress = $this->createStub(Address::class);
        $fromAddress->method('email')->willReturn('sender@example.com');

        $toAddress = $this->createStub(Address::class);
        $toAddress->method('email')->willReturn('recipient@example.com');

        $message = $this->createStub(Message::class);
        $message->method('from')->willReturn($fromAddress);
        $message->method('to')->willReturn([$toAddress]);
        $message->method('subject')->willReturn('Important Meeting');

        $fromMatcher = $this->createMatcherThatChecks(fn (Message $m) => $m->from()?->email() === 'sender@example.com');
        $toMatcher = $this->createMatcherThatChecks(fn (Message $m) => !empty($m->to()) && $m->to()[0]->email() === 'recipient@example.com');
        $subjectMatcher = $this->createMatcherThatChecks(fn (Message $m) => $m->subject() === 'Important Meeting');

        $allOf = new AllOfMatcher($fromMatcher, $toMatcher, $subjectMatcher);

        $this->assertTrue($allOf->matches($message));
    }

    #[Test]
    public function it_does_not_match_when_one_real_matcher_fails(): void
    {
        $fromAddress = $this->createStub(Address::class);
        $fromAddress->method('email')->willReturn('sender@example.com');

        $toAddress = $this->createStub(Address::class);
        $toAddress->method('email')->willReturn('recipient@example.com');

        $message = $this->createStub(Message::class);
        $message->method('from')->willReturn($fromAddress);
        $message->method('to')->willReturn([$toAddress]);
        $message->method('subject')->willReturn('Regular Update');

        $fromMatcher = $this->createMatcherThatChecks(fn (Message $m) => $m->from()?->email() === 'sender@example.com');
        $toMatcher = $this->createMatcherThatChecks(fn (Message $m) => !empty($m->to()) && $m->to()[0]->email() === 'recipient@example.com');
        $subjectMatcher = $this->createMatcherThatChecks(fn (Message $m) => $m->subject() === 'Important Meeting');

        $allOf = new AllOfMatcher($fromMatcher, $toMatcher, $subjectMatcher);

        $this->assertFalse($allOf->matches($message));
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
}
