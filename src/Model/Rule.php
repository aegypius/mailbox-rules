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
     * @param Matcher|null $matcher The matcher to evaluate (null for legacy rules)
     * @param \Closure(MessageInterface): iterable<Action> $then The callback that returns actions
     */
    public function __construct(
        public string $name,
        public ?Matcher $matcher,
        public \Closure $then,
    ) {
    }

    /**
     * Executes the rule against a message.
     *
     * If a matcher is present, evaluates it first. Only executes the callback
     * if the matcher matches (or if no matcher is present for legacy rules).
     *
     * @return iterable<Action>
     */
    public function __invoke(MessageInterface $message): iterable
    {
        if ($this->matcher instanceof \MailboxRules\Matcher\Matcher && !$this->matcher->matches($message)) {
            return [];
        }

        return ($this->then)($message);
    }


    public function __toString(): string
    {
        return $this->name;
    }
}
