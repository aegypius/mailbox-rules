<?php

declare(strict_types=1);

namespace Tests\Model;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\FolderRepository;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\MessageQuery;
use MailboxRules\Action\LogAction;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyOfMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Matcher\FromMatcher;
use MailboxRules\Matcher\ReceivedBeforeMatcher;
use MailboxRules\Matcher\SubjectMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Tests the behavior when anyOf has MIXED branches:
 * - Some branches have QueryAwareMatcher (e.g., folder)
 * - Some branches do NOT have QueryAwareMatcher
 *
 * Expected behavior:
 * - Branches WITH QueryAwareMatcher → use their specific queries
 * - Branches WITHOUT QueryAwareMatcher → query INBOX
 */
#[CoversClass(Rules::class)]
#[AllowMockObjectsWithoutExpectations]
final class RulesWithMixedAnyOfBranchesTest extends TestCase
{
    #[Test]
    public function it_queries_inbox_for_non_folder_branches_and_specific_folder_for_folder_branches(): void
    {
        // This mirrors the "Cleanup" rule structure from my-rules.php:
        // anyOf(
        //     allOf(from("gravatar"), subject(...), receivedBefore(...)),    // → INBOX
        //     allOf(from("pocket"), subject(...), receivedBefore(...)),      // → INBOX
        //     allOf(folder("Promotions/Steam"), receivedBefore(...)),        // → Promotions/Steam
        // )

        $matcher = new AnyOfMatcher(
            // Branch 1: No folder matcher → should query INBOX
            new AllOfMatcher(
                new FromMatcher('donotreply@gravatar.com'),
                new SubjectMatcher('*is your Gravatar code'),
                new ReceivedBeforeMatcher('-2 hours')
            ),
            // Branch 2: No folder matcher → should query INBOX
            new AllOfMatcher(
                new FromMatcher('noreply@example.com'),
                new SubjectMatcher('Login Code'),
                new ReceivedBeforeMatcher('-2 hours')
            ),
            // Branch 3: Has folder matcher → should query Promotions/Steam
            new AllOfMatcher(
                new FolderMatcher('Promotions/Steam'),
                new ReceivedBeforeMatcher('-2 weeks')
            )
        );

        $rule = new Rule(
            name: 'Cleanup',
            matcher: $matcher,
            then: static fn () => yield new LogAction()
        );

        // Setup mocks
        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $inboxFolder = $this->createMock(Folder::class);
        $steamFolder = $this->createMock(Folder::class);

        $emptyMessages = new MessageCollection([]);

        $inboxQuery = $this->createMock(MessageQuery::class);
        $steamQuery = $this->createMock(MessageQuery::class);

        $mailbox->expects($this->once())->method('connect');

        // Branches 1 and 2 (no folder matcher) → query INBOX once (deduplicated at scope level)
        $mailbox->expects($this->once())
            ->method('inbox')
            ->willReturn($inboxFolder);

        $inboxFolder->expects($this->once())->method('messages')->willReturn($inboxQuery);
        $inboxQuery->expects($this->once())->method('withHeaders')->willReturn($inboxQuery);
        $inboxQuery->expects($this->once())->method('get')->willReturn($emptyMessages);

        // Branch 3 (has folder matcher) → query Promotions/Steam
        $mailbox->expects($this->once())
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->once())
            ->method('find')
            ->with('Promotions/Steam')
            ->willReturn($steamFolder);

        $steamFolder->expects($this->once())->method('messages')->willReturn($steamQuery);
        $steamQuery->expects($this->once())->method('withHeaders')->willReturn($steamQuery);
        $steamQuery->expects($this->once())->method('get')->willReturn($emptyMessages);

        // Execute
        $rules = new Rules($mailbox, [$rule]);
        $rules->apply();

        // If we get here without exceptions, the test passes
        // The key assertion is verified by the mock expectations above:
        // - mailbox->inbox() called once (deduplicated for branches 1 & 2)
        // - folders->find('Promotions/Steam') called once (for branch 3)
    }
}
