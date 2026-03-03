<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matches messages based on subject line patterns.
 *
 * Supports exact matches, wildcards, and regex patterns (case-insensitive).
 */
final readonly class SubjectMatcher implements Matcher
{
    private PatternMatcher $patternMatcher;

    public function __construct(string $pattern)
    {
        $this->patternMatcher = new PatternMatcher($pattern);
    }

    public function matches(Message $message): bool
    {
        $subject = $message->subject();

        return $this->patternMatcher->matches($subject ?? '');
    }
}
