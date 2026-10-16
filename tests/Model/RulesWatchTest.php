<?php

declare(strict_types=1);

namespace Tests\Model;

use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\FolderRepository;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;
use MailboxRules\Action\LogAction;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Matcher\SubjectMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
final class RulesWatchTest extends TestCase
{
    #[Test]
    public function watch_uses_folder_scoped_idle_when_rule_targets_specific_folder(): void
    {
        $rule = new Rule(
            name: 'Watch Free folder',
            matcher: new AllOfMatcher(
                new FolderMatcher('Free'),
                new SubjectMatcher('facture')
            ),
            then: static function (MessageInterface $m): iterable { yield new LogAction(); }
        );

        $mailbox = $this->createMock(Mailbox::class);
        $folders = $this->createMock(FolderRepository::class);
        $freeFolder = $this->createMock(Folder::class);

        $mailbox->expects($this->once())->method('connect');

        // Should resolve the "Free" folder, not inbox()
        $mailbox->expects($this->once())
            ->method('folders')
            ->willReturn($folders);

        $folders->expects($this->once())
            ->method('find')
            ->with('Free')
            ->willReturn($freeFolder);

        // watch() must NOT fall back to inbox() when a folder-scoped rule exists
        $mailbox->expects($this->never())->method('inbox');

        // idle() should be called on the targeted folder
        $freeFolder->expects($this->once())
            ->method('idle')
            ->with($this->isCallable());

        $rules = new Rules($mailbox, [$rule]);
        $rules->watch();
    }

    #[Test]
    public function watch_uses_inbox_idle_when_no_folder_matcher_present(): void
    {
        $rule = new Rule(
            name: 'Watch inbox',
            matcher: null,
            then: static function (MessageInterface $m): iterable { yield new LogAction(); }
        );

        $mailbox = $this->createMock(Mailbox::class);
        $inbox = $this->createMock(Folder::class);

        $mailbox->expects($this->once())->method('connect');

        // When no folder scope, inbox() should be used
        $mailbox->expects($this->once())
            ->method('inbox')
            ->willReturn($inbox);

        $mailbox->expects($this->never())->method('folders');

        $inbox->expects($this->once())
            ->method('idle')
            ->with($this->isCallable());

        $rules = new Rules($mailbox, [$rule]);
        $rules->watch();
    }

    #[Test]
    public function watch_idle_callback_applies_rules_to_received_message(): void
    {
        $action = new class() implements Action {
            public bool $invoked = false;

            public function __invoke(MessageInterface $message): void
            {
                $this->invoked = true;
            }
        };

        $rule = new Rule(
            name: 'Inbox rule',
            matcher: null,
            then: static function (MessageInterface $message) use ($action): iterable {
                yield $action;
            }
        );

        $mailbox = $this->createStub(Mailbox::class);
        $inbox = $this->createMock(Folder::class);
        $message = $this->createStub(Message::class);

        $mailbox->method('inbox')->willReturn($inbox);

        // Capture the callable passed to idle() and invoke it immediately with a message
        $inbox->expects($this->once())
            ->method('idle')
            ->willReturnCallback(function (callable $callback) use ($message): void {
                $callback($message);
            });

        $rules = new Rules($mailbox, [$rule]);
        $rules->watch();

        $this->assertTrue($action->invoked, 'Action should be executed when idle callback fires');
    }
}
