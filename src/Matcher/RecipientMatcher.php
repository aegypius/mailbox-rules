<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matches messages based on any recipient (To, CC, or BCC).
 *
 * Matches if ANY recipient in To, CC, or BCC fields matches the pattern.
 * Supports exact matches, wildcards, and regex patterns (case-insensitive).
 */
final readonly class RecipientMatcher implements Matcher
{
    private PatternMatcher $patternMatcher;

    public function __construct(string $pattern)
    {
        $this->patternMatcher = new PatternMatcher($pattern);
    }

    public function matches(Message $message): bool
    {
        // Check To recipients
        foreach ($message->to() as $address) {
            if ($this->patternMatcher->matches($address->email())) {
                return true;
            }
        }

        // Check CC recipients
        foreach ($message->cc() as $address) {
            if ($this->patternMatcher->matches($address->email())) {
                return true;
            }
        }

        return array_any($message->bcc(), fn ($recipient): bool => $this->patternMatcher->matches($recipient->email()));
    }
}
