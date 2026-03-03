<?php

declare(strict_types=1);

namespace Tests\Matcher;

use MailboxRules\Matcher\PatternMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(PatternMatcher::class)]
final class PatternMatcherTest extends TestCase
{
    #[DataProvider('exactMatchProvider')]
    public function testExactMatch(string $pattern, string $value, bool $expected): void
    {
        $patternMatcher = new PatternMatcher($pattern);

        $this->assertSame($expected, $patternMatcher->matches($value));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function exactMatchProvider(): array
    {
        return [
            'exact match' => ['user@example.com', 'user@example.com', true],
            'case insensitive match' => ['User@Example.COM', 'user@example.com', true],
            'no match different user' => ['user@example.com', 'admin@example.com', false],
            'no match different domain' => ['user@example.com', 'user@test.com', false],
        ];
    }

    #[DataProvider('wildcardMatchProvider')]
    public function testWildcardMatch(string $pattern, string $value, bool $expected): void
    {
        $patternMatcher = new PatternMatcher($pattern);

        $this->assertSame($expected, $patternMatcher->matches($value));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function wildcardMatchProvider(): array
    {
        return [
            'wildcard domain match' => ['*@example.com', 'user@example.com', true],
            'wildcard domain no match' => ['*@example.com', 'user@test.com', false],
            'wildcard user match' => ['admin@*', 'admin@example.com', true],
            'wildcard user different domain' => ['admin@*', 'admin@test.com', true],
            'wildcard user no match' => ['admin@*', 'user@example.com', false],
            'wildcard contains match' => ['*test*', 'user-test-account', true],
            'wildcard contains no match' => ['*test*', 'user-account', false],
            'wildcard start match' => ['test*', 'testing', true],
            'wildcard start no match' => ['test*', 'atest', false],
            'wildcard end match' => ['*test', 'mytest', true],
            'wildcard end no match' => ['*test', 'testa', false],
        ];
    }

    #[DataProvider('regexMatchProvider')]
    public function testRegexMatch(string $pattern, string $value, bool $expected): void
    {
        $patternMatcher = new PatternMatcher($pattern);

        $this->assertSame($expected, $patternMatcher->matches($value));
    }

    /**
     * @return array<string, array{string, string, bool}>
     */
    public static function regexMatchProvider(): array
    {
        return [
            'regex case insensitive' => ['/user@example\.com/i', 'USER@EXAMPLE.COM', true],
            'regex pattern match' => ['/^admin.*@example\.com$/', 'admin123@example.com', true],
            'regex pattern no match' => ['/^admin.*@example\.com$/', 'user@example.com', false],
            'regex with alternation' => ['/(alice|bob)@example\.com/', 'alice@example.com', true],
            'regex alternation no match' => ['/(alice|bob)@example\.com/', 'charlie@example.com', false],
        ];
    }

    public function testEmptyPattern(): void
    {
        $patternMatcher = new PatternMatcher('');

        $this->assertTrue($patternMatcher->matches(''));
        $this->assertFalse($patternMatcher->matches('anything'));
    }

    public function testEmptyValue(): void
    {
        $patternMatcher = new PatternMatcher('user@example.com');

        $this->assertFalse($patternMatcher->matches(''));
    }
}
