<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Attachment;
use DirectoryTree\ImapEngine\Message;

final readonly class AttachmentTypeMatcher implements Matcher
{
    public function __construct(
        private string $pattern,
    ) {
    }

    public function matches(Message $message): bool
    {
        $attachments = $message->attachments();

        if ($attachments === []) {
            return false;
        }

        return array_any($attachments, fn (\DirectoryTree\ImapEngine\Attachment $attachment): bool => $this->matchesPattern($attachment));
    }

    private function matchesPattern(Attachment $attachment): bool
    {
        // Check if pattern looks like an extension (.pdf, pdf, *.pdf)
        if ($this->isExtensionPattern($this->pattern)) {
            return $this->matchesExtension($attachment);
        }

        // Otherwise treat as MIME type pattern
        return $this->matchesMimeType($attachment);
    }

    private function isExtensionPattern(string $pattern): bool
    {
        // Patterns starting with . or * are extension patterns
        // Also single words without / are likely extensions (pdf, jpg, etc)
        return str_starts_with($pattern, '.') ||
               str_starts_with($pattern, '*.') ||
               (!str_contains($pattern, '/') && !str_contains($pattern, '*'));
    }

    private function matchesExtension(Attachment $attachment): bool
    {
        $filename = $attachment->filename();

        if ($filename === null) {
            return false;
        }

        $extension = $attachment->extension();

        if ($extension === null) {
            return false;
        }

        // Normalize pattern: remove leading dot and wildcard
        $normalizedPattern = ltrim($this->pattern, '*.');

        return strcasecmp($extension, $normalizedPattern) === 0;
    }

    private function matchesMimeType(Attachment $attachment): bool
    {
        $contentType = $attachment->contentType();

        // Check for wildcard pattern (e.g., image/*)
        if (str_ends_with($this->pattern, '/*')) {
            $prefix = substr($this->pattern, 0, -2);
            return str_starts_with($contentType, $prefix . '/');
        }

        // Exact match (case-insensitive)
        return strcasecmp($contentType, $this->pattern) === 0;
    }
}
