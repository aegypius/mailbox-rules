<?php

declare(strict_types=1);

use MailboxRules\Action\MoveToTrash;
use MailboxRules\Contract\MessageInterface;

use function MailboxRules\any;
use function MailboxRules\mailbox;
use function MailboxRules\rule;

return mailbox("imap://user:password@example.com:143/INBOX", [
    rule(
        name: "test",
        when: any(),
        then: static function (MessageInterface $message): iterable {
            if ($message->subject() !== "Test") {
                yield new MoveToTrash();
            }
        }
    ),
]);
