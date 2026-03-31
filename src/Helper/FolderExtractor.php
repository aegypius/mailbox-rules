<?php

declare(strict_types=1);

namespace MailboxRules\Helper;

use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyOfMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Matcher\Matcher;
use MailboxRules\Matcher\NotMatcher;

final class FolderExtractor
{
    /**
     * Extract all folder paths from a matcher.
     *
     * For AnyOfMatcher, returns all folders from each branch.
     * For other matchers, returns the first folder found (or null/empty array).
     *
     * @return list<string|null> List of folder paths (null means inbox)
     */
    public static function extractAllFolderPaths(?Matcher $matcher): array
    {
        if ($matcher === null) {
            return [null];
        }

        if ($matcher instanceof FolderMatcher) {
            return [$matcher->pattern];
        }

        if ($matcher instanceof AnyOfMatcher) {
            // For AnyOf: extract folders from EACH branch
            $reflection = new \ReflectionClass($matcher);
            $property = $reflection->getProperty('matchers');
            /** @var array<Matcher> $matchers */
            $matchers = $property->getValue($matcher);

            $allFolders = [];
            foreach ($matchers as $childMatcher) {
                $folders = self::extractAllFolderPaths($childMatcher);
                $allFolders = [...$allFolders, ...$folders];
            }

            return $allFolders !== [] ? $allFolders : [null];
        }

        if ($matcher instanceof AllOfMatcher) {
            // For AllOf: extract first folder found (all conditions must match same message)
            $reflection = new \ReflectionClass($matcher);
            $property = $reflection->getProperty('matchers');
            /** @var array<Matcher> $matchers */
            $matchers = $property->getValue($matcher);

            foreach ($matchers as $childMatcher) {
                $folderPath = self::extractFolderPath($childMatcher);
                if ($folderPath !== null) {
                    return [$folderPath];
                }
            }
        }

        if ($matcher instanceof NotMatcher) {
            // Don't extract from NOT matchers (they invert the logic)
            return [null];
        }

        return [null];
    }

    /**
     * Extract folder path from a matcher (returns null if no FolderMatcher found).
     *
     * For composite matchers, extracts the first FolderMatcher found.
     *
     * @return string|null The folder path, or null if no FolderMatcher
     */
    public static function extractFolderPath(?Matcher $matcher): ?string
    {
        if ($matcher === null) {
            return null;
        }

        if ($matcher instanceof FolderMatcher) {
            return $matcher->pattern;
        }

        if ($matcher instanceof AllOfMatcher || $matcher instanceof AnyOfMatcher) {
            // Get matchers array via reflection
            $reflection = new \ReflectionClass($matcher);
            $property = $reflection->getProperty('matchers');
            /** @var array<Matcher> $matchers */
            $matchers = $property->getValue($matcher);

            foreach ($matchers as $childMatcher) {
                $folderPath = self::extractFolderPath($childMatcher);
                if ($folderPath !== null) {
                    return $folderPath;
                }
            }
        }

        if ($matcher instanceof NotMatcher) {
            // Don't extract from NOT matchers (they invert the logic)
            return null;
        }

        return null;
    }
}
