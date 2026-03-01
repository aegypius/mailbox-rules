<?php

declare(strict_types=1);

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;
use Psr\Log\LoggerInterface;

final readonly class LogAction implements Action
{
    public function __invoke(Message $message, LoggerInterface $logger): void
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
