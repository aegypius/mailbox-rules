<?php

/**
 * Example configuration demonstrating multiple mailbox support with clean architecture.
 *
 * This file shows how to configure rules for multiple email accounts
 * in a single configuration file using the new MailboxConfiguration architecture.
 */

use MailboxRules\Action\LogAction;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;
use MailboxRules\Action\MoveToTrash;

use function MailboxRules\allOf;
use function MailboxRules\anyOf;
use function MailboxRules\chain;
use function MailboxRules\env;
use function MailboxRules\from;
use function MailboxRules\mailbox;
use function MailboxRules\mailboxes;
use function MailboxRules\receivedBefore;
use function MailboxRules\rule;
use function MailboxRules\subject;

return mailboxes(
    // Work mailbox
    mailbox(
        dsn: env('WORK_MAILBOX_DSN'),
        rules: [
            rule(
                name: 'Work - GitHub Notifications',
                when: from('notifications@github.com'),
                then: static fn () => chain(
                    new LogAction(),
                    new MarkAsRead(),
                    new MoveToFolder('GitHub'),
                )
            ),
            rule(
                name: 'Work - Old Notifications',
                when: allOf(
                    from('*@company.com'),
                    receivedBefore('last week'),
                    subject('*[NOTIFICATION]*'),
                ),
                then: static fn () => chain(
                    new LogAction(),
                    new MoveToTrash(),
                )
            ),
        ],
        name: 'Work Account'
    ),

    // Personal mailbox
    mailbox(
        dsn: env('PERSONAL_MAILBOX_DSN'),
        rules: [
            rule(
                name: 'Personal - Newsletters',
                when: anyOf(
                    from('*@newsletter.com'),
                    from('*@updates.com'),
                ),
                then: static fn () => chain(
                    new LogAction(),
                    new MoveToFolder('Newsletters'),
                )
            ),
        ],
        name: 'Personal Account'
    ),
);
