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
    private PatternMatcher $matcher;

    public function __construct(string $pattern)
    {
        $this->matcher = new PatternMatcher($pattern);
    }

    public function matches(Message $message): bool
    {
        // Check plain text body
        $text = $message->text();
        if ($text !== null && $this->matcher->matches($text)) {
            return true;
        }

        // Check HTML body
        $html = $message->html();
        if ($html !== null && $this->matcher->matches($html)) {
            return true;
        }

        return false;
    }
}
