<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;

final readonly class CopyToFolder implements Action
{
    public function __construct(
        private string $folder,
    ) {
    }

    public function __invoke(MessageInterface $message): void
    {
        $message->copy($this->folder);
    }
}
