<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Address;
use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;
use Psr\Log\LoggerInterface;

final readonly class LogAction implements Action
{
    public function __invoke(MessageInterface $message, LoggerInterface $logger): void
    {
        $logger->info("{from} {to} {subject} {receivedAt}", [
            "from" => $message->from()?->email(),
            "to" => \array_map(
                static fn (Address $address): string => $address->email(),
                $message->to()
            ),
            "subject" => $message->subject(),
            "receivedAt" => $message->date()?->toAtomString(),
        ]);
    }
}
