<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matches messages based on the folder/directory they are in.
 *
 * Supports exact matches, wildcards, and regex patterns (case-insensitive).
 */
final readonly class FolderMatcher implements Matcher
{
    private PatternMatcher $patternMatcher;

    public function __construct(public string $pattern)
    {
        $this->patternMatcher = new PatternMatcher($pattern);
    }

    public function matches(Message $message): bool
    {
        return $this->patternMatcher->matches($message->folder()->path());
    }
}
