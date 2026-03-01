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
     * @return iterable<Action>
     */
    public function __invoke(Message $message): iterable
    {
        return ($this->callback)($message);
    }


    public function __toString(): string
    {
        return $this->name;
    }
}
