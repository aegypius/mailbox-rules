<?php

declare(strict_types=1);

use MailboxRules\Action\MoveToFolder;
use MailboxRules\Contract\MailboxInterface;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;

if (!isset($GLOBALS['test_mailbox'])) {
    throw new \RuntimeException('test_mailbox must be set in GLOBALS');
}

$mailbox = $GLOBALS['test_mailbox'];
assert($mailbox instanceof MailboxInterface);

$rule = new Rule(
    name: 'Test Rule',
    matcher: new \MailboxRules\Matcher\AnyMatcher(),
    then: static fn () => yield new MoveToFolder('Archive')
);

return new Rules($mailbox, [$rule]);
