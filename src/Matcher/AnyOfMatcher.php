<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use MailboxRules\Contract\MessageInterface;

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

    public function folderScope(array $folders): array
    {
        $scope = [];
        foreach ($this->matchers as $matcher) {
            $scope = [...$scope, ...($matcher->folderScope($folders) ?? ['INBOX'])];
        }

        return array_values(array_unique($scope));
    }

    public function matches(MessageInterface $message): bool
    {
        return array_any($this->matchers, fn ($matcher) => $matcher->matches($message));
    }
}
