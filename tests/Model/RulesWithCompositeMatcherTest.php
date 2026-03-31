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
final class RulesWithCompositeMatcherTest extends TestCase
{
    #[Test]
    public function it_detects_folder_matcher_nested_in_allOf(): void
    {
        // Create a matcher that combines folder("Free") and subject("...")
        $matcher = new AllOfMatcher(
            new FolderMatcher('Free'),
            new SubjectMatcher('Votre facture mobile Free est disponible')
        );

        // Create a rule with this composite matcher
        $rule = new Rule(
            name: 'Free Mobile',
            matcher: $matcher,
            then: static fn () => yield new LogAction()
        );

        // Setup mailbox mock to verify it queries the "Free" folder, not INBOX
        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $freeFolder = $this->createMock(Folder::class);
        $query = $this->createMock(MessageQuery::class);
        $messages = new MessageCollection([]);

        $mailbox->expects($this->once())->method('connect');

        // The key assertion: it should call folders()->find('Free'), NOT inbox()
        $mailbox->expects($this->once())
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->once())
            ->method('find')
            ->with('Free')
            ->willReturn($freeFolder);

        // Mailbox should NEVER call inbox() when folder matcher is present
        $mailbox->expects($this->never())->method('inbox');

        $freeFolder->expects($this->once())->method('messages')->willReturn($query);
        $query->expects($this->once())->method('withHeaders')->willReturn($query);
        $query->expects($this->once())->method('get')->willReturn($messages);

        // Execute
        $rules = new Rules($mailbox, [$rule]);
        $rules->apply();
    }

    #[Test]
    public function it_searches_correct_folder_in_preview_mode(): void
    {
        // Create a matcher that combines folder("Free") and subject("...")
        $matcher = new AllOfMatcher(
            new FolderMatcher('Free'),
            new SubjectMatcher('Votre facture mobile Free est disponible')
        );

        $rule = new Rule(
            name: 'Free Mobile',
            matcher: $matcher,
            then: static fn () => yield new LogAction()
        );

        // Setup mailbox mock
        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $freeFolder = $this->createMock(Folder::class);
        $query = $this->createMock(MessageQuery::class);
        $messages = new MessageCollection([]);

        $mailbox->expects($this->once())->method('connect');

        // Should query the "Free" folder
        $mailbox->expects($this->once())
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->once())
            ->method('find')
            ->with('Free')
            ->willReturn($freeFolder);

        $mailbox->expects($this->never())->method('inbox');

        $freeFolder->expects($this->once())->method('messages')->willReturn($query);
        $query->expects($this->once())->method('withHeaders')->willReturn($query);
        $query->expects($this->once())->method('limit')->with(10)->willReturn($query);
        $query->expects($this->once())->method('get')->willReturn($messages);

        // Execute preview
        $rules = new Rules($mailbox, [$rule]);
        $rules->preview();
    }

    #[Test]
    public function it_handles_multiple_nested_folder_matchers(): void
    {
        // Rule 1: allOf(folder("Free"), subject(...))
        $rule1 = new Rule(
            name: 'Free Mobile',
            matcher: new AllOfMatcher(
                new FolderMatcher('Free'),
                new SubjectMatcher('facture')
            ),
            then: static fn () => yield new LogAction()
        );

        // Rule 2: allOf(folder("Amazon"), subject(...))
        $rule2 = new Rule(
            name: 'Amazon',
            matcher: new AllOfMatcher(
                new FolderMatcher('Amazon'),
                new SubjectMatcher('order')
            ),
            then: static fn () => yield new LogAction()
        );

        // Setup mailbox mock
        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $freeFolder = $this->createMock(Folder::class);
        $amazonFolder = $this->createMock(Folder::class);
        $query = $this->createMock(MessageQuery::class);
        $messages = new MessageCollection([]);

        $mailbox->expects($this->once())->method('connect');

        // Should query BOTH folders
        $mailbox->expects($this->exactly(2))
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->exactly(2))
            ->method('find')
            ->willReturnCallback(function (string $name) use ($freeFolder, $amazonFolder): ?Folder {
                return match ($name) {
                    'Free' => $freeFolder,
                    'Amazon' => $amazonFolder,
                    default => null,
                };
            });

        $mailbox->expects($this->never())->method('inbox');

        $freeFolder->expects($this->once())->method('messages')->willReturn($query);
        $amazonFolder->expects($this->once())->method('messages')->willReturn($query);
        $query->expects($this->exactly(2))->method('withHeaders')->willReturn($query);
        $query->expects($this->exactly(2))->method('get')->willReturn($messages);

        // Execute
        $rules = new Rules($mailbox, [$rule1, $rule2]);
        $rules->apply();
    }
}
