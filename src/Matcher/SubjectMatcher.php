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
    private PatternMatcher $matcher;

    public function __construct(string $pattern)
    {
        $this->matcher = new PatternMatcher($pattern);
    }

    public function matches(Message $message): bool
    {
        $subject = $message->subject();

        return $this->matcher->matches($subject ?? '');
    }
}
