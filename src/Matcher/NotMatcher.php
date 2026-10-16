<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use MailboxRules\Contract\MessageInterface;

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

    public function folderScope(array $folders): ?array
    {
        $excluded = $this->matcher->folderScope($folders);

        return $excluded === null ? null : array_values(array_diff(['INBOX'], $excluded));
    }

    public function matches(MessageInterface $message): bool
    {
        return !$this->matcher->matches($message);
    }
}
