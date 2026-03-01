<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use Carbon\Carbon;
use Carbon\CarbonInterface;
use DirectoryTree\ImapEngine\Message;

/**
 * Matcher that matches messages received after a specific date/time.
 */
final readonly class ReceivedAfterMatcher implements Matcher
{
    private CarbonInterface $cutoff;

    /**
     * @param CarbonInterface|string $datetime The cutoff date/time (inclusive boundary excluded)
     */
    public function __construct(CarbonInterface|string $datetime)
    {
        $this->cutoff = $datetime instanceof CarbonInterface
            ? $datetime
            : Carbon::parse($datetime);
    }

    public function matches(Message $message): bool
    {
        $messageDate = $message->date();

        if ($messageDate === null) {
            return false;
        }

        return $messageDate->isAfter($this->cutoff);
    }
}
