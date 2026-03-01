<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matcher that matches all messages unconditionally.
 *
 * This matcher is used as a catch-all for rules that should apply to every
 * message in a mailbox, regardless of sender, subject, or any other criteria.
 * It is typically created using the any() helper function.
 *
 * @see any()
 */
final readonly class AnyMatcher implements Matcher
{
    /**
     * Always returns true, matching any message.
     *
     * @param Message $message The message to evaluate (unused)
     * @return bool Always true
     */
    public function matches(Message $message): bool
    {
        return true;
    }
}
