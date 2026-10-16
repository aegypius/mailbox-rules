<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use MailboxRules\Contract\MessageInterface;

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

    public function folderScope(array $folders): ?array
    {
        $scope = null;
        foreach ($this->matchers as $matcher) {
            $child = $matcher->folderScope($folders);
            if ($child !== null) {
                $scope = $scope === null ? $child : array_values(array_intersect($scope, $child));
            }
        }

        return $scope;
    }

    public function matches(MessageInterface $message): bool
    {
        return array_all($this->matchers, fn ($matcher) => $matcher->matches($message));
    }
}
