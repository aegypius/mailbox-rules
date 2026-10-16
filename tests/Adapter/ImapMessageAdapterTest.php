<?php

declare(strict_types=1);

namespace Tests\Adapter;

use Carbon\Carbon;
use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Attachment;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\FolderRepository;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Adapter\ImapAttachmentAdapter;
use MailboxRules\Adapter\ImapMessageAdapter;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\ValueObject\EmailAddress;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImapMessageAdapter::class)]
final class ImapMessageAdapterTest extends TestCase
{
    private function makeAdapter(?Message $message = null): ImapMessageAdapter
    {
        return new ImapMessageAdapter($message ?? $this->createStub(Message::class));
    }

    private function makeMessageWithFolder(?Folder $folder = null): Message
    {
        $folder ??= $this->createStub(Folder::class);
        $message = $this->createStub(Message::class);
        $message->method('folder')->willReturn($folder);
        return $message;
    }

    private function makeFolderWithMailbox(?Mailbox $mailbox = null): Folder
    {
        $mailbox ??= $this->createStub(Mailbox::class);
        $folder = $this->createStub(Folder::class);
        $folder->method('mailbox')->willReturn($mailbox);
        return $folder;
    }

    public function testImplementsMessageInterface(): void
    {
        self::assertInstanceOf(MessageInterface::class, $this->makeAdapter());
    }

    public function testFromDelegatesToMessage(): void
    {
        $address = $this->createStub(Address::class);
        $address->method('email')->willReturn('a@example.com');
        $message = $this->createStub(Message::class);
        $message->method('from')->willReturn($address);

        self::assertEquals(new EmailAddress('a@example.com'), $this->makeAdapter($message)->from());
    }

    public function testFromReturnsNullWhenNoSender(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('from')->willReturn(null);

        self::assertNull($this->makeAdapter($message)->from());
    }

    public function testToDelegatesToMessage(): void
    {
        $address = $this->createStub(Address::class);
        $address->method('email')->willReturn('a@example.com');
        $message = $this->createStub(Message::class);
        $message->method('to')->willReturn([$address]);

        self::assertEquals([new EmailAddress('a@example.com')], $this->makeAdapter($message)->to());
    }

    public function testCcDelegatesToMessage(): void
    {
        $address = $this->createStub(Address::class);
        $address->method('email')->willReturn('a@example.com');
        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$address]);

        self::assertEquals([new EmailAddress('a@example.com')], $this->makeAdapter($message)->cc());
    }

    public function testBccDelegatesToMessage(): void
    {
        $address = $this->createStub(Address::class);
        $address->method('email')->willReturn('a@example.com');
        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$address]);

        self::assertEquals([new EmailAddress('a@example.com')], $this->makeAdapter($message)->bcc());
    }

    public function testSubjectDelegatesToMessage(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('subject')->willReturn('Hello World');

        self::assertSame('Hello World', $this->makeAdapter($message)->subject());
    }

    public function testDateDelegatesToMessage(): void
    {
        $date = Carbon::now();
        $message = $this->createStub(Message::class);
        $message->method('date')->willReturn($date);

        self::assertSame($date, $this->makeAdapter($message)->date());
    }

    public function testSizeDelegatesToMessage(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('size')->willReturn(1024);

        self::assertSame(1024, $this->makeAdapter($message)->size());
    }

    public function testTextDelegatesToMessage(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn('plain text body');

        self::assertSame('plain text body', $this->makeAdapter($message)->text());
    }

    public function testHtmlDelegatesToMessage(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('html')->willReturn('<p>html body</p>');

        self::assertSame('<p>html body</p>', $this->makeAdapter($message)->html());
    }

    public function testHasAttachmentsDelegatesToMessage(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('hasAttachments')->willReturn(true);

        self::assertTrue($this->makeAdapter($message)->hasAttachments());
    }

    public function testAttachmentsWrapsEachInImapAttachmentAdapter(): void
    {
        $attachment1 = $this->createStub(Attachment::class);
        $attachment2 = $this->createStub(Attachment::class);

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment1, $attachment2]);

        $result = $this->makeAdapter($message)->attachments();

        self::assertCount(2, $result);
        self::assertInstanceOf(ImapAttachmentAdapter::class, $result[0]);
        self::assertInstanceOf(ImapAttachmentAdapter::class, $result[1]);
    }

    public function testAttachmentsReturnsEmptyArrayWhenNoAttachments(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([]);

        self::assertSame([], $this->makeAdapter($message)->attachments());
    }

    public function testFolderPathReturnsFolderPath(): void
    {
        $folder = $this->createStub(Folder::class);
        $folder->method('path')->willReturn('INBOX');

        $message = $this->makeMessageWithFolder($folder);

        self::assertSame('INBOX', $this->makeAdapter($message)->folderPath());
    }

    public function testMoveCallsMessageMoveWithUtf7EncodedFolder(): void
    {
        $folders = $this->createStub(FolderRepository::class);
        $folders->method('find')->willReturn($this->createStub(Folder::class));

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('folders')->willReturn($folders);

        $folder = $this->makeFolderWithMailbox($mailbox);

        $message = $this->createMock(Message::class);
        $message->method('folder')->willReturn($folder);
        $message->expects(self::once())
            ->method('move')
            ->with('Archive', false);

        (new ImapMessageAdapter($message))->move('Archive');
    }

    public function testMoveEncodesAccentedFolderNameToUtf7(): void
    {
        $folders = $this->createStub(FolderRepository::class);
        $folders->method('find')->willReturn($this->createStub(Folder::class));

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('folders')->willReturn($folders);

        $folder = $this->makeFolderWithMailbox($mailbox);

        $message = $this->createMock(Message::class);
        $message->method('folder')->willReturn($folder);
        $message->expects(self::once())
            ->method('move')
            ->with('Copropri&AOk-t&AOk-', false);

        (new ImapMessageAdapter($message))->move('Copropriété');
    }

    public function testMoveCreatesDestinationFolderIfItDoesNotExist(): void
    {
        $folders = $this->createMock(FolderRepository::class);
        $folders->method('find')->willReturn(null);
        $folders->expects(self::atLeastOnce())->method('create');

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('folders')->willReturn($folders);

        $folder = $this->makeFolderWithMailbox($mailbox);

        $message = $this->createMock(Message::class);
        $message->method('folder')->willReturn($folder);
        $message->expects(self::once())->method('move');

        (new ImapMessageAdapter($message))->move('NewFolder');
    }

    public function testMovePassesExpungeFlag(): void
    {
        $folders = $this->createStub(FolderRepository::class);
        $folders->method('find')->willReturn($this->createStub(Folder::class));

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('folders')->willReturn($folders);

        $folder = $this->makeFolderWithMailbox($mailbox);

        $message = $this->createMock(Message::class);
        $message->method('folder')->willReturn($folder);
        $message->expects(self::once())
            ->method('move')
            ->with('Trash', true);

        (new ImapMessageAdapter($message))->move('Trash', true);
    }

    public function testCopyCreatesMissingParentFolders(): void
    {
        $folders = $this->createMock(FolderRepository::class);
        $folders->method('find')->willReturn(null);
        $created = [];
        $folders->expects(self::exactly(2))
            ->method('create')
            ->willReturnCallback(function (string $path) use (&$created): Folder {
                $created[] = $path;
                return $this->createStub(Folder::class);
            });

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('folders')->willReturn($folders);

        $message = $this->createMock(Message::class);
        $message->method('folder')->willReturn($this->makeFolderWithMailbox($mailbox));
        $message->expects(self::once())->method('copy');

        (new ImapMessageAdapter($message))->copy('A/B');

        self::assertSame(['A', 'A/B'], $created);
    }

    public function testCopyCallsMessageCopyWithUtf7EncodedFolder(): void
    {
        $folders = $this->createStub(FolderRepository::class);
        $folders->method('find')->willReturn($this->createStub(Folder::class));

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('folders')->willReturn($folders);

        $folder = $this->makeFolderWithMailbox($mailbox);

        $message = $this->createMock(Message::class);
        $message->method('folder')->willReturn($folder);
        $message->expects(self::once())
            ->method('copy')
            ->with('Archive');

        (new ImapMessageAdapter($message))->copy('Archive');
    }

    public function testCopyEncodesAccentedFolderNameToUtf7(): void
    {
        $folders = $this->createStub(FolderRepository::class);
        $folders->method('find')->willReturn($this->createStub(Folder::class));

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('folders')->willReturn($folders);

        $folder = $this->makeFolderWithMailbox($mailbox);

        $message = $this->createMock(Message::class);
        $message->method('folder')->willReturn($folder);
        $message->expects(self::once())
            ->method('copy')
            ->with('Copropri&AOk-t&AOk-');

        (new ImapMessageAdapter($message))->copy('Copropriété');
    }

    public function testMarkFlaggedDelegatesToMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())->method('markFlagged');

        $this->makeAdapter($message)->markFlagged();
    }

    public function testUnmarkFlaggedDelegatesToMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())->method('unmarkFlagged');

        $this->makeAdapter($message)->unmarkFlagged();
    }

    public function testMarkReadDelegatesToMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())->method('markRead');

        $this->makeAdapter($message)->markRead();
    }

    public function testMarkUnreadDelegatesToMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())->method('markUnread');

        $this->makeAdapter($message)->markUnread();
    }

    public function testDeleteDelegatesToMessage(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())->method('delete')->with(false);

        $this->makeAdapter($message)->delete();
    }

    public function testDeleteWithExpunge(): void
    {
        $message = $this->createMock(Message::class);
        $message->expects(self::once())->method('delete')->with(true);

        $this->makeAdapter($message)->delete(true);
    }
}
