<?php

/**
 * Test fixture: Single mailbox configuration (backward compatibility)
 */

use MailboxRules\Action\LogAction;
use function MailboxRules\any;
use function MailboxRules\mailbox;
use function MailboxRules\rule;

return mailbox('imap://user:pass@localhost:993/INBOX', [
    rule(
        name: 'Test Rule',
        when: any(),
        then: static fn () => [new LogAction()]
    ),
]);
