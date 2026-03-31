<?php

declare(strict_types=1);

namespace MailboxRules\ValueObject;

use MailboxRules\Model\Rule;

/**
 * Represents a mailbox configuration with its DSN and rules.
 *
 * This is a value object that separates mailbox configuration from execution,
 * enabling better separation of concerns and parallel processing capabilities.
 */
final readonly class MailboxConfiguration
{
    /**
     * @param Dsn $dsn The IMAP connection DSN
     * @param iterable<Rule> $rules The rules to apply to this mailbox
     * @param string|null $name Optional name for logging/identification
     */
    public function __construct(
        public Dsn $dsn,
        public iterable $rules,
        public ?string $name = null,
    ) {
    }
}
