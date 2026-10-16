<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\Matcher;

final readonly class Rule implements \Stringable
{
    /**
     * @param string $name The name of the rule
     * @param Matcher $matcher The matcher to evaluate
     * @param \Closure(MessageInterface): iterable<Action> $then The callback that returns actions
     */
    public function __construct(
        public string $name,
        public Matcher $matcher,
        public \Closure $then,
    ) {
    }

    /**
     * Executes the callback only if the matcher matches.
     *
     * @return list<Action>
     */
    public function __invoke(MessageInterface $message): array
    {
        if (!$this->matcher->matches($message)) {
            return [];
        }

        return iterator_to_array(($this->then)($message), false);
    }

    public function __toString(): string
    {
        return $this->name;
    }
}
