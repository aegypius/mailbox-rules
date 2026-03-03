<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matcher that checks if message body content matches a pattern.
 *
 * Searches both plain text and HTML body content.
 * Returns true if the pattern matches either format.
 *
 * Supports:
 * - Exact text matching: "invoice attached"
 * - Wildcards: "*meeting*", "order *"
 * - Regular expressions: "/\d{6}/"
 * - Case-insensitive matching
 *
 * @see PatternMatcher for pattern syntax details
 */
final readonly class BodyMatcher implements Matcher
{
    private PatternMatcher $patternMatcher;

    public function __construct(string $pattern)
    {
        $this->patternMatcher = new PatternMatcher($pattern);
    }

    public function matches(Message $message): bool
    {
        // Check plain text body
        $text = $message->text();
        if ($text !== null && $this->patternMatcher->matches($text)) {
            return true;
        }

        // Check HTML body
        $html = $message->html();
        return $html !== null && $this->patternMatcher->matches($html);
    }
}
