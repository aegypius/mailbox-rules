<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matches messages based on CC recipient email addresses.
 *
 * Matches if ANY CC recipient matches the pattern.
 * Supports exact matches, wildcards, and regex patterns (case-insensitive).
 */
final readonly class CcMatcher implements Matcher
{
    private PatternMatcher $patternMatcher;

    public function __construct(string $pattern)
    {
        $this->patternMatcher = new PatternMatcher($pattern);
    }

    public function matches(Message $message): bool
    {
        $recipients = $message->cc();

        if (empty($recipients)) {
            return false;
        }

        foreach ($recipients as $recipient) {
            if ($this->patternMatcher->matches($recipient->email())) {
                return true;
            }
        }

        return false;
    }
}
