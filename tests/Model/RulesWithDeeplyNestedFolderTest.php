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
use MailboxRules\Matcher\SubjectMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
#[AllowMockObjectsWithoutExpectations]
final class RulesWithDeeplyNestedFolderTest extends TestCase
{
    #[Test]
    public function it_handles_folder_matcher_nested_in_anyOf_allOf(): void
    {
        // This mirrors the "Cleanup" rule structure:
        // anyOf(
        //     allOf(from(...), subject(...)),           // Branch 1: no folder → queries INBOX
        //     allOf(folder("Promotions/Steam"), ...)   // Branch 2: has folder → queries Steam
        // )

        $matcher = new AnyOfMatcher(
            new AllOfMatcher(
                new SubjectMatcher('test1')
            ),
            new AllOfMatcher(
                new FolderMatcher('Promotions/Steam'),
                new SubjectMatcher('test2')
            )
        );

        $rule = new Rule(
            name: 'Cleanup',
            matcher: $matcher,
            then: static fn () => yield new LogAction()
        );

        // Setup mailbox mock
        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $inboxFolder = $this->createMock(Folder::class);
        $steamFolder = $this->createMock(Folder::class);
        $inboxQuery = $this->createMock(MessageQuery::class);
        $steamQuery = $this->createMock(MessageQuery::class);
        $messages = new MessageCollection([]);

        $mailbox->expects($this->once())->method('connect');

        // Branch 1 (no folder matcher) → queries INBOX
        $mailbox->expects($this->once())
            ->method('inbox')
            ->willReturn($inboxFolder);

        $inboxFolder->expects($this->once())->method('messages')->willReturn($inboxQuery);
        $inboxQuery->expects($this->once())->method('withHeaders')->willReturn($inboxQuery);
        $inboxQuery->expects($this->once())->method('get')->willReturn($messages);

        // Branch 2 (has folder matcher) → queries Promotions/Steam
        $mailbox->expects($this->once())
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->once())
            ->method('find')
            ->with('Promotions/Steam')
            ->willReturn($steamFolder);

        $steamFolder->expects($this->once())->method('messages')->willReturn($steamQuery);
        $steamQuery->expects($this->once())->method('withHeaders')->willReturn($steamQuery);
        $steamQuery->expects($this->once())->method('get')->willReturn($messages);

        // Execute
        $rules = new Rules($mailbox, [$rule]);
        $rules->apply();
    }

    #[Test]
    public function it_handles_multiple_folder_matchers_in_different_anyOf_branches(): void
    {
        // anyOf(
        //     allOf(folder("Free"), ...),
        //     allOf(folder("Steam"), ...),
        //     allOf(folder("Amazon"), ...)
        // )

        $matcher = new AnyOfMatcher(
            new AllOfMatcher(
                new FolderMatcher('Free'),
                new SubjectMatcher('facture')
            ),
            new AllOfMatcher(
                new FolderMatcher('Promotions/Steam'),
                new SubjectMatcher('sale')
            ),
            new AllOfMatcher(
                new FolderMatcher('Amazon'),
                new SubjectMatcher('order')
            )
        );

        $rule = new Rule(
            name: 'Multi-folder cleanup',
            matcher: $matcher,
            then: static fn () => yield new LogAction()
        );

        // Setup mailbox mock
        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $freeFolder = $this->createMock(Folder::class);
        $steamFolder = $this->createMock(Folder::class);
        $amazonFolder = $this->createMock(Folder::class);
        $query = $this->createMock(MessageQuery::class);
        $messages = new MessageCollection([]);

        $mailbox->expects($this->once())->method('connect');

        // Should query ALL THREE folders
        $mailbox->expects($this->exactly(3))
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->exactly(3))
            ->method('find')
            ->willReturnCallback(function (string $name) use ($freeFolder, $steamFolder, $amazonFolder): ?Folder {
                return match ($name) {
                    'Free' => $freeFolder,
                    'Promotions/Steam' => $steamFolder,
                    'Amazon' => $amazonFolder,
                    default => null,
                };
            });

        $mailbox->expects($this->never())->method('inbox');

        $freeFolder->expects($this->once())->method('messages')->willReturn($query);
        $steamFolder->expects($this->once())->method('messages')->willReturn($query);
        $amazonFolder->expects($this->once())->method('messages')->willReturn($query);
        $query->expects($this->exactly(3))->method('withHeaders')->willReturn($query);
        $query->expects($this->exactly(3))->method('get')->willReturn($messages);

        // Execute
        $rules = new Rules($mailbox, [$rule]);
        $rules->apply();
    }
}
