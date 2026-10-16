<?php

declare(strict_types=1);

namespace MailboxRules\Model;

final readonly class MailboxPreview
{
    /**
     * @param list<PreviewResult> $results
     */
    public function __construct(
        public ?string $mailbox,
        public array $results,
    ) {
    }
}
