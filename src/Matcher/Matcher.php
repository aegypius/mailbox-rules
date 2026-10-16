<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use MailboxRules\Contract\MessageInterface;

/**
 * Matcher interface for testing email message properties.
 *
 * Matchers are predicates that evaluate whether a message matches
 * specific criteria (sender, subject, body content, etc.).
 */
interface Matcher
{
    /**
     * Test if the given message matches this matcher's criteria.
     *
     * @param MessageInterface $message The email message to test
     * @return bool True if the message matches, false otherwise
     */
    public function matches(MessageInterface $message): bool;

    /**
     * Folders this Matcher confines a Rule to, out of the Mailbox's current Folder list.
     *
     * @param list<string> $folders
     * @return list<string>|null null when the Matcher does not constrain the Folder scope
     */
    public function folderScope(array $folders): ?array;
}
