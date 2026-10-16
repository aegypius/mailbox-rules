<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\ValueObject\EmailAddress;
use Psr\Log\LoggerInterface;

final readonly class LogAction implements Action
{
    public function __invoke(MessageInterface $message, LoggerInterface $logger): void
    {
        $logger->info("{from} {to} {subject} {receivedAt}", [
            "from" => $message->from()?->email(),
            "to" => \array_map(
                static fn (EmailAddress $address): string => $address->email(),
                $message->to()
            ),
            "subject" => $message->subject(),
            "receivedAt" => $message->date()?->toAtomString(),
        ]);
    }
}
