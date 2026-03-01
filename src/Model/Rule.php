<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;
use MailboxRules\Matcher\Matcher;

final readonly class Rule implements \Stringable
{
    /**
     * @param string $name The name of the rule
     * @param Matcher|null $matcher The matcher to evaluate (null for legacy rules)
     * @param \Closure(Message): iterable<Action> $callback The callback that returns actions
     */
    public function __construct(
        public string $name,
        public ?Matcher $matcher,
        public \Closure $callback,
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
    public function __invoke(Message $message): iterable
    {
        // If matcher exists and doesn't match, return empty
        if ($this->matcher !== null && !$this->matcher->matches($message)) {
            return [];
        }

        // Otherwise execute callback (legacy or matched)
        return ($this->callback)($message);
    }


    public function __toString(): string
    {
        return $this->name;
    }
}
