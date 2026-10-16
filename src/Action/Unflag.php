<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;

/**
 * Removes the flag from a message.
 *
 * This action removes the \Flagged flag from the message, unmarking it
 * as important or starred.
 */
readonly class Unflag implements Action
{
    public function __invoke(MessageInterface $message): void
    {
        $message->unmarkFlagged();
    }
}
