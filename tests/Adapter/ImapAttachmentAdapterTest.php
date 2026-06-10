<?php

declare(strict_types=1);

namespace Tests\Adapter;

use DirectoryTree\ImapEngine\Attachment;
use MailboxRules\Adapter\ImapAttachmentAdapter;
use MailboxRules\Contract\AttachmentInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ImapAttachmentAdapter::class)]
final class ImapAttachmentAdapterTest extends TestCase
{
    public function testImplementsAttachmentInterface(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertInstanceOf(AttachmentInterface::class, $adapter);
    }

    public function testContentTypeDelegatesToAttachment(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('contentType')->willReturn('image/jpeg');

        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertSame('image/jpeg', $adapter->contentType());
    }

    public function testFilenameDelegatesToAttachment(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('filename')->willReturn('photo.jpg');

        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertSame('photo.jpg', $adapter->filename());
    }

    public function testFilenameReturnsNullWhenAttachmentHasNoFilename(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('filename')->willReturn(null);

        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertNull($adapter->filename());
    }

    public function testExtensionDelegatesToAttachment(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('extension')->willReturn('jpg');

        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertSame('jpg', $adapter->extension());
    }

    public function testExtensionReturnsNullWhenAttachmentHasNoExtension(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('extension')->willReturn(null);

        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertNull($adapter->extension());
    }

    public function testContentDelegatesToAttachmentContents(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('contents')->willReturn('binary content here');

        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertSame('binary content here', $adapter->content());
    }

    public function testSizeIsStrlenOfContents(): void
    {
        $contents = 'hello world';
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('contents')->willReturn($contents);

        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertSame(strlen($contents), $adapter->size());
    }

    public function testSizeForEmptyContents(): void
    {
        $attachment = $this->createStub(Attachment::class);
        $attachment->method('contents')->willReturn('');

        $adapter = new ImapAttachmentAdapter($attachment);

        self::assertSame(0, $adapter->size());
    }
}
