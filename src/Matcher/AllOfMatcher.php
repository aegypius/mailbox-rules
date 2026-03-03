<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matches messages when ALL provided matchers match (logical AND).
 *
 * Short-circuits on first failure for efficiency.
 */
final readonly class AllOfMatcher implements Matcher
{
    /**
     * @var array<Matcher>
     */
    private array $matchers;

    public function __construct(Matcher ...$matchers)
    {
        $this->matchers = $matchers;
    }

    public function matches(Message $message): bool
    {
        return array_all($this->matchers, fn ($matcher) => $matcher->matches($message));
    }
}
