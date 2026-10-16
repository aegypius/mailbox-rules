<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;

final readonly class MoveToFolder implements Action
{
    public function __construct(
        private string $folder,
        private bool $expunge = false,
    ) {
    }

    public function __invoke(MessageInterface $message): void
    {
        $message->move($this->folder, $this->expunge);
    }
}
