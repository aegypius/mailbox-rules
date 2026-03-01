<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

final readonly class HasAttachmentMatcher implements Matcher
{
    public function matches(Message $message): bool
    {
        return $message->hasAttachments();
    }
}
