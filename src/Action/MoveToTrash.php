<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use MailboxRules\Action;
use MailboxRules\Contract\MailboxInterface;
use MailboxRules\Contract\MessageInterface;

/**
 * Never creates a Trash Folder: with none and no explicit name, it fails.
 */
final readonly class MoveToTrash implements Action
{
    public function __construct(
        private ?string $trashFolder = null,
        private bool $expunge = false,
    ) {
    }

    public function __invoke(MessageInterface $message, MailboxInterface $mailbox): void
    {
        $trash = $this->trashFolder ?? $mailbox->trash()
            ?? throw new \RuntimeException('No \\Trash folder on this mailbox; pass an explicit folder to MoveToTrash.');

        $message->move($trash, $this->expunge);
    }
}
