<?php

declare(strict_types=1);

namespace MailboxRules;

use MailboxRules\Contract\MailboxInterface;
use MailboxRules\ValueObject\Dsn;

interface MailboxFactoryInterface
{
    public static function createMailbox(Dsn $dsn): MailboxInterface;
}
