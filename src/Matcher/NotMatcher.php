<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matcher that inverts the result of another matcher (logical NOT).
 *
 * Returns true when the wrapped matcher returns false, and vice versa.
 */
final readonly class NotMatcher implements Matcher
{
    public function __construct(
        private Matcher $matcher,
    ) {
    }

    public function matches(Message $message): bool
    {
        return !$this->matcher->matches($message);
    }
}
