<?php

/**
 * Example mailbox rules configuration demonstrating the declarative DSL.
 *
 * This file shows various matcher patterns, combinators, and actions
 * that can be used to filter and process email messages automatically.
 */

use MailboxRules\Action\Flag;
use MailboxRules\Action\LogAction;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;

use function MailboxRules\allOf;
use function MailboxRules\any;
use function MailboxRules\anyOf;
use function MailboxRules\chain;
use function MailboxRules\env;
use function MailboxRules\from;
use function MailboxRules\mailbox;
use function MailboxRules\not;
use function MailboxRules\rule;
use function MailboxRules\subject;
use function MailboxRules\to;

return mailbox(env("MAILBOX_DSN"), [
    // Move promotional emails from specific vendors to Promotions folder
    rule(
        name: "Chaosium Promotions",
        when: from("*@chaosium.com"),
        then: static fn () => yield new MoveToFolder("Promotions")
    ),

    // Archive newsletters and mark them as read
    rule(
        name: "Archive Newsletters",
        when: subject("*[Newsletter]*"),
        then: static fn () => chain(
            new MoveToFolder("Newsletters"),
            new MarkAsRead()
        )
    ),

    // Flag important messages to support email
    rule(
        name: "Important Support Emails",
        when: allOf(
            to("support@example.com"),
            subject("*[URGENT]*")
        ),
        then: static fn () => yield new Flag()
    ),

    // Filter spam: messages from known spam domains, but not order confirmations
    rule(
        name: "Spam Filter",
        when: allOf(
            anyOf(
                from("*@spam.com"),
                from("*@junk.net"),
                subject("*Get rich quick*")
            ),
            not(subject("*Order Confirmation*"))
        ),
        then: static fn () => yield new MoveToFolder("Spam")
    ),

    // Process team emails: flag and mark as read
    rule(
        name: "Team Updates",
        when: anyOf(
            allOf(
                from("*@company.com"),
                subject("*[Team]*")
            ),
            to("*@team.example.com")
        ),
        then: static fn () => chain(
            new MoveToFolder("Team"),
            new Flag()
        )
    ),

    // Log all unmatched messages for debugging
    rule(
        name: "Log Everything",
        when: any(),
        then: static fn () => yield new LogAction()
    ),
]);
