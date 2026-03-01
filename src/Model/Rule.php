<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

final readonly class Rule implements \Stringable
{
    /**
     * @param \Closure(Message $name): iterable<Action> $callback
     */
    public function __construct(
        public string $name,
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
