<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use MailboxRules\Contract\MessageInterface;

final readonly class HasAttachmentMatcher implements Matcher
{
    public function matches(MessageInterface $message): bool
    {
        return $message->hasAttachments();
    }
}
