<?php

declare(strict_types=1);

namespace Tests\Model;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\MessageQuery;
use MailboxRules\Action\LogAction;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
final class RulesPreviewLimitTest extends TestCase
{
    #[Test]
    public function preview_uses_default_limit_of_10(): void
    {
        $query = $this->createMock(MessageQuery::class);
        $query->method('withHeaders')->willReturn($query);
        $query->expects($this->once())->method('limit')->with(10)->willReturn($query);
        $query->method('get')->willReturn(new MessageCollection([]));

        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($folder);

        $rule = new Rule(
            name: 'Test',
            matcher: null,
            then: static fn (Message $m): iterable => yield new LogAction()
        );

        $rules = new Rules($mailbox, [$rule]);
        $rules->preview();
    }

    #[Test]
    public function preview_accepts_custom_limit(): void
    {
        $query = $this->createMock(MessageQuery::class);
        $query->method('withHeaders')->willReturn($query);
        $query->expects($this->once())->method('limit')->with(50)->willReturn($query);
        $query->method('get')->willReturn(new MessageCollection([]));

        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($folder);

        $rule = new Rule(
            name: 'Test',
            matcher: null,
            then: static fn (Message $m): iterable => yield new LogAction()
        );

        $rules = new Rules($mailbox, [$rule]);
        $rules->preview(50);
    }

    #[Test]
    public function preview_with_zero_limit_fetches_all_messages(): void
    {
        $query = $this->createMock(MessageQuery::class);
        $query->method('withHeaders')->willReturn($query);
        $query->expects($this->never())->method('limit');
        $query->method('get')->willReturn(new MessageCollection([]));

        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($folder);

        $rule = new Rule(
            name: 'Test',
            matcher: null,
            then: static fn (Message $m): iterable => yield new LogAction()
        );

        $rules = new Rules($mailbox, [$rule]);
        $rules->preview(0);
    }
}
