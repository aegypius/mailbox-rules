<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

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
     * @param Message $message The email message to test
     * @return bool True if the message matches, false otherwise
     */
    public function matches(Message $message): bool;
}
