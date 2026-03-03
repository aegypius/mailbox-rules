<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\SubjectMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(SubjectMatcher::class)]
final class SubjectMatcherTest extends TestCase
{
    #[Test]
    public function it_matches_exact_subject(): void
    {
        $subjectMatcher = new SubjectMatcher('Important Meeting');
        $message = $this->createMessageWithSubject('Important Meeting');

        $this->assertTrue($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_does_not_match_different_subject(): void
    {
        $subjectMatcher = new SubjectMatcher('Important Meeting');
        $message = $this->createMessageWithSubject('Regular Update');

        $this->assertFalse($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_matches_case_insensitively(): void
    {
        $subjectMatcher = new SubjectMatcher('important meeting');
        $message = $this->createMessageWithSubject('IMPORTANT MEETING');

        $this->assertTrue($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_pattern(): void
    {
        $subjectMatcher = new SubjectMatcher('*urgent*');
        $message = $this->createMessageWithSubject('This is urgent please read');

        $this->assertTrue($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_does_not_match_wildcard_when_no_match(): void
    {
        $subjectMatcher = new SubjectMatcher('*urgent*');
        $message = $this->createMessageWithSubject('Regular meeting');

        $this->assertFalse($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_matches_regex_pattern(): void
    {
        $subjectMatcher = new SubjectMatcher('/^RE:.*/i');
        $message = $this->createMessageWithSubject('RE: Your question');

        $this->assertTrue($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_does_not_match_regex_when_no_match(): void
    {
        $subjectMatcher = new SubjectMatcher('/^RE:.*/i');
        $message = $this->createMessageWithSubject('FW: Your question');

        $this->assertFalse($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_handles_empty_subject(): void
    {
        $subjectMatcher = new SubjectMatcher('test');
        $message = $this->createMessageWithSubject('');

        $this->assertFalse($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_matches_empty_pattern_with_empty_subject(): void
    {
        $subjectMatcher = new SubjectMatcher('');
        $message = $this->createMessageWithSubject('');

        $this->assertTrue($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_prefix(): void
    {
        $subjectMatcher = new SubjectMatcher('*Report');
        $message = $this->createMessageWithSubject('Weekly Report');

        $this->assertTrue($subjectMatcher->matches($message));
    }

    #[Test]
    public function it_matches_wildcard_suffix(): void
    {
        $subjectMatcher = new SubjectMatcher('Weekly*');
        $message = $this->createMessageWithSubject('Weekly Report');

        $this->assertTrue($subjectMatcher->matches($message));
    }

    #[Test]
    #[DataProvider('provideMultiplePatterns')]
    public function it_matches_various_patterns(string $pattern, string $subject, bool $expected): void
    {
        $subjectMatcher = new SubjectMatcher($pattern);
        $message = $this->createMessageWithSubject($subject);

        $this->assertSame($expected, $subjectMatcher->matches($message));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function provideMultiplePatterns(): array
    {
        return [
            'exact match' => ['Meeting', 'Meeting', true],
            'exact no match' => ['Meeting', 'Conference', false],
            'wildcard both sides' => ['*important*', 'This is important stuff', true],
            'wildcard no match' => ['*important*', 'Regular message', false],
            'regex digits' => ['/^\d{4}-\d{2}/', '2024-01 Report', true],
            'regex no match' => ['/^\d{4}-\d{2}/', 'Monthly Report', false],
            'case insensitive exact' => ['urgent', 'URGENT', true],
            'case insensitive wildcard' => ['*urgent*', 'VERY URGENT ISSUE', true],
        ];
    }

    private function createMessageWithSubject(string $subject): Message
    {
        $message = $this->createStub(Message::class);
        $message->method('subject')->willReturn($subject);

        return $message;
    }
}
