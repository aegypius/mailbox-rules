<?php

declare(strict_types=1);

namespace MailboxRules\Adapter;

use DirectoryTree\ImapEngine\Attachment;
use MailboxRules\Contract\AttachmentInterface;

final readonly class ImapAttachmentAdapter implements AttachmentInterface
{
    public function __construct(private Attachment $attachment)
    {
    }

    public function contentType(): string
    {
        return $this->attachment->contentType();
    }

    public function filename(): ?string
    {
        return $this->attachment->filename();
    }

    public function extension(): ?string
    {
        return $this->attachment->extension();
    }

    public function size(): int
    {
        return strlen($this->attachment->contents());
    }

    public function content(): string
    {
        return $this->attachment->contents();
    }
}
