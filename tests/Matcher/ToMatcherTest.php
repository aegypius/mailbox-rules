<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\ToMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToMatcher::class)]
final class ToMatcherTest extends TestCase
{
    #[Test]
    public function it_implements_matcher_interface(): void
    {
        $toMatcher = new ToMatcher('test@example.com');
        $this->assertInstanceOf(\MailboxRules\Matcher\Matcher::class, $toMatcher);
    }

    #[Test]
    public function it_matches_when_pattern_matches_any_recipient(): void
    {
        $toMatcher = new ToMatcher('recipient@example.com');
        $message = $this->createMessageWithTo(['other@example.com', 'recipient@example.com']);

        $this->assertTrue($toMatcher->matches($message));
    }

    #[Test]
    public function it_does_not_match_when_pattern_matches_no_recipients(): void
    {
        $toMatcher = new ToMatcher('target@example.com');
        $message = $this->createMessageWithTo(['other1@example.com', 'other2@example.com']);

        $this->assertFalse($toMatcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_domain_against_any_recipient(): void
    {
        $toMatcher = new ToMatcher('*@company.com');
        $message = $this->createMessageWithTo(['sales@company.com', 'info@other.com']);

        $this->assertTrue($toMatcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_local_part(): void
    {
        $toMatcher = new ToMatcher('newsletter-*@example.com');
        $message = $this->createMessageWithTo(['newsletter-2024@example.com']);

        $this->assertTrue($toMatcher->matches($message));
    }

    #[Test]
    public function it_matches_regex_pattern(): void
    {
        $toMatcher = new ToMatcher('/^support@.*/i');
        $message = $this->createMessageWithTo(['support@anywhere.com', 'sales@company.com']);

        $this->assertTrue($toMatcher->matches($message));
    }

    #[Test]
    public function it_handles_case_insensitive_exact_match(): void
    {
        $toMatcher = new ToMatcher('Recipient@Example.COM');
        $message = $this->createMessageWithTo(['recipient@example.com']);

        $this->assertTrue($toMatcher->matches($message));
    }

    #[Test]
    public function it_returns_false_when_to_is_empty(): void
    {
        $toMatcher = new ToMatcher('test@example.com');
        $message = $this->createMessageWithTo([]);

        $this->assertFalse($toMatcher->matches($message));
    }

    #[Test]
    public function it_matches_first_recipient_in_list(): void
    {
        $toMatcher = new ToMatcher('first@example.com');
        $message = $this->createMessageWithTo(['first@example.com', 'second@example.com']);

        $this->assertTrue($toMatcher->matches($message));
    }

    #[Test]
    public function it_matches_last_recipient_in_list(): void
    {
        $toMatcher = new ToMatcher('last@example.com');
        $message = $this->createMessageWithTo(['first@example.com', 'last@example.com']);

        $this->assertTrue($toMatcher->matches($message));
    }

    /**
     * @param array<string> $recipients
     */
    #[Test]
    #[DataProvider('provideMultiplePatterns')]
    public function it_matches_various_patterns(string $pattern, array $recipients, bool $expected): void
    {
        $toMatcher = new ToMatcher($pattern);
        $message = $this->createMessageWithTo($recipients);

        $this->assertSame($expected, $toMatcher->matches($message));
    }

    /**
     * @return array<string, array{string, array<string>, bool}>
     */
    public static function provideMultiplePatterns(): array
    {
        return [
            'exact match single' => ['test@example.com', ['test@example.com'], true],
            'exact match in list' => ['test@example.com', ['other@example.com', 'test@example.com'], true],
            'exact no match' => ['test@example.com', ['other@example.com'], false],
            'wildcard domain match' => ['*@company.com', ['sales@company.com', 'info@other.com'], true],
            'wildcard domain no match' => ['*@company.com', ['sales@other.com'], false],
            'wildcard local match' => ['newsletter-*@site.com', ['newsletter-123@site.com'], true],
            'wildcard both parts' => ['*@*.com', ['any@domain.com'], true],
            'regex match' => ['/^support@.*/i', ['support@anywhere.com'], true],
            'regex no match' => ['/^support@.*/i', ['sales@anywhere.com'], false],
            'empty recipients' => ['test@example.com', [], false],
        ];
    }

    /**
     * @param array<string> $emails
     */
    private function createMessageWithTo(array $emails): Message
    {
        $message = $this->createStub(Message::class);
        $addresses = array_map(function (string $email): \PHPUnit\Framework\MockObject\Stub {
            $address = $this->createStub(Address::class);
            $address->method('email')->willReturn($email);
            return $address;
        }, $emails);
        $message->method('to')->willReturn($addresses);

        return $message;
    }
}
