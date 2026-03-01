<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

/**
 * Removes the flag from a message.
 *
 * This action removes the \Flagged flag from the message, unmarking it
 * as important or starred.
 */
readonly class Unflag implements Action
{
    public function __invoke(Message $message): void
    {
        $message->unmarkFlagged();
    }
}
