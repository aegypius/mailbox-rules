<?php

declare(strict_types=1);

use DirectoryTree\ImapEngine\Mailbox;
use MailboxRules\Action\MoveToFolder;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;

if (!isset($GLOBALS['test_mailbox'])) {
    throw new \RuntimeException('test_mailbox must be set in GLOBALS');
}

$mailbox = $GLOBALS['test_mailbox'];
assert($mailbox instanceof Mailbox);

$rule = new Rule(
    name: 'Test Rule',
    matcher: null,
    then: static fn () => yield new MoveToFolder('Archive')
);

return new Rules($mailbox, [$rule]);
