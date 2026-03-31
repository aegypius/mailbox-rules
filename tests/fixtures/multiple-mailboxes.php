<?php

/**
 * Test fixture: Multiple mailboxes configuration (variadic syntax)
 */

use MailboxRules\Action\LogAction;
use function MailboxRules\any;
use function MailboxRules\mailbox;
use function MailboxRules\mailboxes;
use function MailboxRules\rule;

return mailboxes(
    mailbox('imap://user1:pass1@localhost:993/INBOX', [
        rule(
            name: 'Work Rule',
            when: any(),
            then: static fn () => [new LogAction()]
        ),
    ], name: 'Work'),
    mailbox('imap://user2:pass2@localhost:993/INBOX', [
        rule(
            name: 'Personal Rule',
            when: any(),
            then: static fn () => [new LogAction()]
        ),
    ], name: 'Personal'),
);
