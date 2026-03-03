<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matches messages when ANY provided matcher matches (logical OR).
 *
 * Short-circuits on first success for efficiency.
 */
final readonly class AnyOfMatcher implements Matcher
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
        return array_any($this->matchers, fn ($matcher) => $matcher->matches($message));
    }
}
