<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\MailboxInterface;

/**
 * Interface for matchers that can optimize message retrieval by applying
 * constraints directly at the query level before fetching messages.
 *
 * This allows for server-side filtering, reducing bandwidth and improving
 * performance by only fetching relevant messages from the IMAP server.
 *
 * The matcher receives the mailbox and returns a modified query/queries
 * via a callback that can:
 * - Change folders to search (e.g., search "Archives" instead of "INBOX")
 * - Add date filters (e.g., ->since('2024-01-01'))
 * - Add size filters (e.g., ->larger(1000000))
 * - Add flag filters (e.g., ->unseen())
 * - Any other IMAP-level query constraint
 *
 * Examples:
 * - folder('Archives') → searches Archives folder instead of INBOX
 * - receivedSince('-7 days') → adds ->since('-7 days') to query
 * - largerThan('1MB') → adds ->larger(1048576) to query
 * - isUnread() → adds ->unseen() to query
 */
interface QueryAwareMatcher extends Matcher
{
    /**
     * Apply query-level optimizations by modifying how messages are fetched.
     *
     * The callback receives the mailbox and should yield message queries.
     * This allows the matcher to control both which folders to search
     * and what query constraints to apply.
     *
     * Default behavior (INBOX only):
     * ```php
     * yield $mailbox->inbox()->messages();
     * ```
     *
     * Search specific folder:
     * ```php
     * $folder = $mailbox->folders()->find('Archives');
     * if ($folder) yield $folder->messages();
     * ```
     *
     * Search all folders:
     * ```php
     * foreach ($mailbox->folders()->get() as $folder) {
     *     yield $folder->messages();
     * }
     * ```
     *
     * Add query constraints:
     * ```php
     * yield $mailbox->inbox()->messages()->unseen()->since('-7 days');
     * ```
     *
     * @return callable(MailboxInterface): iterable<\DirectoryTree\ImapEngine\MessageQueryInterface>
     */
    public function getQueryProvider(): callable;
}
