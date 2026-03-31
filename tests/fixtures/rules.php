<?php

declare(strict_types=1);

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\MoveToTrash;

use function MailboxRules\mailbox;
use function MailboxRules\rule;

return mailbox("imap://user:password@example.com:143/INBOX", [
    rule(
        name: "test",
        when: null,
        then: static function (Message $message): iterable {
            if ($message->subject() !== "Test") {
                yield new MoveToTrash();
            }
        }
    ),
]);
