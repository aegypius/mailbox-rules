<?php

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Attachment;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\AttachmentTypeMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(AttachmentTypeMatcher::class)]
final class AttachmentTypeMatcherTest extends TestCase
{
    private function createAttachment(string $contentType, ?string $filename): Attachment
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('contentType')->willReturn($contentType);
        $attachment->method('filename')->willReturn($filename);
        $attachment->method('extension')->willReturn(
            $filename !== null ? pathinfo($filename, PATHINFO_EXTENSION) : null
        );

        return $attachment;
    }

    public function testMatchesByExactMimeType(): void
    {
        $attachment = $this->createAttachment('image/jpeg', 'photo.jpg');

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment]);

        $matcher = new AttachmentTypeMatcher('image/jpeg');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesByMimeTypeWildcard(): void
    {
        $attachment = $this->createAttachment('image/jpeg', 'photo.jpg');

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment]);

        $matcher = new AttachmentTypeMatcher('image/*');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesByExtensionWithDot(): void
    {
        $attachment = $this->createAttachment('application/pdf', 'document.pdf');

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment]);

        $matcher = new AttachmentTypeMatcher('.pdf');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesByExtensionWithoutDot(): void
    {
        $attachment = $this->createAttachment('application/pdf', 'document.pdf');

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment]);

        $matcher = new AttachmentTypeMatcher('pdf');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesByExtensionWildcard(): void
    {
        $attachment = $this->createAttachment('application/pdf', 'document.pdf');

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment]);

        $matcher = new AttachmentTypeMatcher('*.pdf');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesAnyAttachmentInMultiple(): void
    {
        $attachment1 = $this->createAttachment('text/plain', 'notes.txt');
        $attachment2 = $this->createAttachment('image/jpeg', 'photo.jpg');

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment1, $attachment2]);

        $matcher = new AttachmentTypeMatcher('image/*');

        self::assertTrue($matcher->matches($message));
    }

    public function testDoesNotMatchDifferentMimeType(): void
    {
        $attachment = $this->createAttachment('image/jpeg', 'photo.jpg');

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment]);

        $matcher = new AttachmentTypeMatcher('application/pdf');

        self::assertFalse($matcher->matches($message));
    }

    public function testDoesNotMatchDifferentExtension(): void
    {
        $attachment = $this->createAttachment('image/jpeg', 'photo.jpg');

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment]);

        $matcher = new AttachmentTypeMatcher('.pdf');

        self::assertFalse($matcher->matches($message));
    }

    public function testReturnsFalseForNoAttachments(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([]);

        $matcher = new AttachmentTypeMatcher('image/*');

        self::assertFalse($matcher->matches($message));
    }

    public function testDoesNotMatchWhenFilenameIsNull(): void
    {
        $attachment = $this->createAttachment('application/octet-stream', null);

        $message = $this->createStub(Message::class);
        $message->method('attachments')->willReturn([$attachment]);

        $matcher = new AttachmentTypeMatcher('.pdf');

        self::assertFalse($matcher->matches($message));
    }
}
