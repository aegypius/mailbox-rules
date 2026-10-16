<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use MailboxRules\Contract\MessageInterface;

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

    public function folderScope(array $folders): array
    {
        return array_values(array_filter($folders, $this->patternMatcher->matches(...)));
    }

    public function matches(MessageInterface $message): bool
    {
        return $this->patternMatcher->matches($message->folderPath());
    }
}
