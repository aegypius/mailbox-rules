<?php

declare(strict_types=1);

namespace Tests\Support;

use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Contract\MailboxInterface;
use MailboxRules\MailboxFactoryInterface;
use MailboxRules\ValueObject\Dsn;

final class InMemoryMailboxFactory implements MailboxFactoryInterface
{
    public static InMemoryMailbox $mailbox;

    public static function createMailbox(Dsn $dsn): MailboxInterface
    {
        return self::$mailbox;
    }
}
