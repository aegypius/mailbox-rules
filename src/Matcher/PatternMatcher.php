<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

/**
 * Utility class for pattern matching against strings.
 *
 * Supports three pattern types:
 * - Exact match: "user@example.com" (case-insensitive)
 * - Wildcard: "*@example.com", "user@*", "*test*" (case-insensitive)
 * - Regex: "/pattern/flags" (supports all PHP regex patterns)
 *
 * Used internally by matchers that need to compare email addresses,
 * subjects, or other message fields against patterns.
 */
final readonly class PatternMatcher
{
    private string $regex;

    public function __construct(string $pattern)
    {
        $this->regex = $this->compilePattern($pattern);
    }

    /**
     * Check if the given value matches the pattern.
     *
     * @param string $value The value to test
     * @return bool True if the value matches the pattern
     */
    public function matches(string $value): bool
    {
        return (bool) preg_match($this->regex, $value);
    }

    /**
     * Compile pattern into a regex.
     *
     * Detects pattern type and converts to regex:
     * - Regex patterns (start/end with /): used as-is
     * - Wildcard patterns (contains *): converted to regex
     * - Exact patterns: converted to case-insensitive regex
     */
    private function compilePattern(string $pattern): string
    {
        // Empty pattern only matches empty string
        if ($pattern === '') {
            return '/^$/';
        }

        // Regex pattern (starts and ends with /)
        if (str_starts_with($pattern, '/') && str_contains(substr($pattern, 1), '/')) {
            return $pattern;
        }

        // Wildcard pattern (contains *)
        if (str_contains($pattern, '*')) {
            $escaped = preg_quote($pattern, '/');
            $regex = str_replace('\\*', '.*', $escaped);
            return '/^' . $regex . '$/i';
        }

        // Exact match (case-insensitive)
        return '/^' . preg_quote($pattern, '/') . '$/i';
    }
}
