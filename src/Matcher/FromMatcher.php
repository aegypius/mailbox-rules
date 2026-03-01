<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matches messages based on the sender's email address.
 *
 * Supports exact matches, wildcards, and regex patterns (case-insensitive).
 */
final readonly class FromMatcher implements Matcher
{
    private PatternMatcher $patternMatcher;

    public function __construct(string $pattern)
    {
        $this->patternMatcher = new PatternMatcher($pattern);
    }

    public function matches(Message $message): bool
    {
        $from = $message->from();

        // No sender address
        if ($from === null) {
            return false;
        }

        return $this->patternMatcher->matches($from->email());
    }
}
