<?php

declare(strict_types=1);

namespace Tests\Matcher;

use MailboxRules\Matcher\PatternMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Contracts\Service\ResetInterface;

#[CoversClass(PatternMatcher::class)]
final class PatternMatcherCacheTest extends TestCase
{
    protected function setUp(): void
    {
        (new PatternMatcher(''))->reset();
    }

    #[Test]
    public function it_implements_reset_interface(): void
    {
        $this->assertInstanceOf(ResetInterface::class, new PatternMatcher(''));
    }

    #[Test]
    public function two_instances_with_same_pattern_share_cached_regex(): void
    {
        $a = new PatternMatcher('*@example.com');
        $b = new PatternMatcher('*@example.com');

        $this->assertSame($a->matches('user@example.com'), $b->matches('user@example.com'));
        $this->assertSame($a->matches('user@other.com'), $b->matches('user@other.com'));

        // Only one cache entry for the same pattern
        $this->assertSame(1, PatternMatcher::getCacheSize());
    }

    #[Test]
    public function different_patterns_produce_different_cached_entries(): void
    {
        new PatternMatcher('*@example.com');
        new PatternMatcher('*@other.com');
        new PatternMatcher('/^admin/i');

        $this->assertSame(3, PatternMatcher::getCacheSize());
    }

    #[Test]
    public function cache_hit_produces_same_match_result_as_fresh_compilation(): void
    {
        $pattern = '*@example.com';

        $first = new PatternMatcher($pattern);
        $resultFirst = $first->matches('user@example.com');

        $second = new PatternMatcher($pattern);
        $resultSecond = $second->matches('user@example.com');

        $this->assertSame($resultFirst, $resultSecond);
        $this->assertTrue($resultSecond);
    }

    #[Test]
    public function reset_clears_the_cache(): void
    {
        new PatternMatcher('*@example.com');
        new PatternMatcher('*@other.com');
        $this->assertSame(2, PatternMatcher::getCacheSize());

        (new PatternMatcher(''))->reset();

        $this->assertSame(0, PatternMatcher::getCacheSize());
    }
}
