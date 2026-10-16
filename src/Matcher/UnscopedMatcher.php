<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

/**
 * For Matchers that do not constrain the Folder scope.
 */
trait UnscopedMatcher
{
    public function folderScope(array $folders): ?array
    {
        return null;
    }
}
