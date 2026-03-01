<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\FromMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FromMatcher::class)]
final class FromMatcherTest extends TestCase
{
    #[Test]
    public function it_implements_matcher_interface(): void
    {
        $matcher = new FromMatcher('test@example.com');
        $this->assertInstanceOf(\MailboxRules\Matcher\Matcher::class, $matcher);
    }

    #[Test]
    public function it_matches_exact_email(): void
    {
        $matcher = new FromMatcher('sender@example.com');
        $message = $this->createMessageWithFrom('sender@example.com');

        $this->assertTrue($matcher->matches($message));
    }

    #[Test]
    public function it_does_not_match_different_email(): void
    {
        $matcher = new FromMatcher('sender@example.com');
        $message = $this->createMessageWithFrom('other@example.com');

        $this->assertFalse($matcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_domain(): void
    {
        $matcher = new FromMatcher('*@chaosium.com');
        $message = $this->createMessageWithFrom('newsletter@chaosium.com');

        $this->assertTrue($matcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_local_part(): void
    {
        $matcher = new FromMatcher('newsletter-*@example.com');
        $message = $this->createMessageWithFrom('newsletter-2024@example.com');

        $this->assertTrue($matcher->matches($message));
    }

    #[Test]
    public function it_matches_regex_pattern(): void
    {
        $matcher = new FromMatcher('/^newsletter-\d+@example\.com$/i');
        $message = $this->createMessageWithFrom('newsletter-2024@example.com');

        $this->assertTrue($matcher->matches($message));
    }

    #[Test]
    public function it_handles_case_insensitive_exact_match(): void
    {
        $matcher = new FromMatcher('Sender@Example.COM');
        $message = $this->createMessageWithFrom('sender@example.com');

        $this->assertTrue($matcher->matches($message));
    }

    #[Test]
    public function it_returns_false_when_from_is_null(): void
    {
        $matcher = new FromMatcher('test@example.com');
        $message = $this->createStub(Message::class);
        $message->method('from')->willReturn(null);

        $this->assertFalse($matcher->matches($message));
    }

    #[Test]
    #[DataProvider('provideMultiplePatterns')]
    public function it_matches_various_patterns(string $pattern, string $email, bool $expected): void
    {
        $matcher = new FromMatcher($pattern);
        $message = $this->createMessageWithFrom($email);

        $this->assertSame($expected, $matcher->matches($message));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function provideMultiplePatterns(): array
    {
        return [
            'exact match' => ['test@example.com', 'test@example.com', true],
            'exact no match' => ['test@example.com', 'other@example.com', false],
            'wildcard domain match' => ['*@company.com', 'sales@company.com', true],
            'wildcard domain no match' => ['*@company.com', 'sales@other.com', false],
            'wildcard local match' => ['newsletter-*@site.com', 'newsletter-123@site.com', true],
            'wildcard both parts' => ['*@*.com', 'any@domain.com', true],
            'regex match' => ['/^support@.*/i', 'support@anywhere.com', true],
            'regex no match' => ['/^support@.*/i', 'sales@anywhere.com', false],
        ];
    }

    private function createMessageWithFrom(string $email): Message
    {
        $message = $this->createStub(Message::class);
        $address = $this->createStub(Address::class);
        $address->method('email')->willReturn($email);
        $message->method('from')->willReturn($address);

        return $message;
    }
}
