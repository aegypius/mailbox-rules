<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

/**
 * Represents a preview of what actions would be executed for a message.
 *
 * Contains the message, the rule that matched, and the actions that would be executed.
 * Used by dry-run/preview mode to show what would happen without actually executing actions.
 */
final readonly class PreviewResult
{
    /**
     * @param Message $message The message being processed
     * @param string $ruleName The name of the rule that matched
     * @param list<Action> $actions The actions that would be executed
     */
    public function __construct(
        public Message $message,
        public string $ruleName,
        public array $actions,
    ) {
    }
}
