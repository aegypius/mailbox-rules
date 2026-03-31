<?php

declare(strict_types=1);

namespace MailboxRules;

use DirectoryTree\ImapEngine\Mailbox;
use MailboxRules\ValueObject\Dsn;

interface MailboxFactoryInterface
{
    public static function createMailbox(Dsn $dsn): Mailbox;
}
