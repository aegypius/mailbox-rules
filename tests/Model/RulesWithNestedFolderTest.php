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
final class RulesWithNestedFolderTest extends TestCase
{
    #[Test]
    public function it_handles_nested_folder_paths_like_promotions_slash_steam(): void
    {
        // Create a matcher with nested folder path: Promotions/Steam
        $matcher = new AllOfMatcher(
            new FolderMatcher('Promotions/Steam'),
            new SubjectMatcher('test')
        );

        $rule = new Rule(
            name: 'Steam Promotions',
            matcher: $matcher,
            then: static fn () => yield new LogAction()
        );

        // Setup mailbox mock
        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $promotionsSteamFolder = $this->createMock(Folder::class);
        $query = $this->createMock(MessageQuery::class);
        $messages = new MessageCollection([]);

        $mailbox->expects($this->once())->method('connect');

        // Should query the "Promotions/Steam" folder exactly
        $mailbox->expects($this->once())
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->once())
            ->method('find')
            ->with('Promotions/Steam')
            ->willReturn($promotionsSteamFolder);

        // Should NEVER call inbox() when folder matcher is present
        $mailbox->expects($this->never())->method('inbox');

        $promotionsSteamFolder->expects($this->once())->method('messages')->willReturn($query);
        $query->expects($this->once())->method('withHeaders')->willReturn($query);
        $query->expects($this->once())->method('get')->willReturn($messages);

        // Execute
        $rules = new Rules($mailbox, [$rule]);
        $rules->apply();
    }

    #[Test]
    public function it_handles_multiple_nested_folder_paths(): void
    {
        // Rule 1: Promotions/Steam
        $rule1 = new Rule(
            name: 'Steam Promotions',
            matcher: new AllOfMatcher(
                new FolderMatcher('Promotions/Steam'),
                new SubjectMatcher('game')
            ),
            then: static fn () => yield new LogAction()
        );

        // Rule 2: Archives/2024
        $rule2 = new Rule(
            name: 'Archives 2024',
            matcher: new AllOfMatcher(
                new FolderMatcher('Archives/2024'),
                new SubjectMatcher('old')
            ),
            then: static fn () => yield new LogAction()
        );

        // Setup mailbox mock
        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $steamFolder = $this->createMock(Folder::class);
        $archivesFolder = $this->createMock(Folder::class);
        $query = $this->createMock(MessageQuery::class);
        $messages = new MessageCollection([]);

        $mailbox->expects($this->once())->method('connect');

        // Should query BOTH folders
        $mailbox->expects($this->exactly(2))
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(function (string $name) use ($steamFolder, $archivesFolder): ?Folder {
                return match ($name) {
                    'Promotions/Steam' => $steamFolder,
                    'Archives/2024' => $archivesFolder,
                    default => null,
                };
            });

        $mailbox->expects($this->never())->method('inbox');

        $steamFolder->expects($this->once())->method('messages')->willReturn($query);
        $archivesFolder->expects($this->once())->method('messages')->willReturn($query);
        $query->expects($this->exactly(2))->method('withHeaders')->willReturn($query);
        $query->expects($this->exactly(2))->method('get')->willReturn($messages);

        // Execute
        $rules = new Rules($mailbox, [$rule1, $rule2]);
        $rules->apply();
    }
}
