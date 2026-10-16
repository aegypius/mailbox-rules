<?php

declare(strict_types=1);

namespace MailboxRules\Contract;

interface AttachmentInterface
{
    public function contentType(): string;
    public function filename(): ?string;
    public function extension(): ?string;
    public function size(): int;
    public function content(): string;
}
