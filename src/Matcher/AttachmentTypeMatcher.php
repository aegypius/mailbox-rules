<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use MailboxRules\Contract\AttachmentInterface;
use MailboxRules\Contract\MessageInterface;

final readonly class AttachmentTypeMatcher implements Matcher
{
    use UnscopedMatcher;

    public function __construct(
        private string $pattern,
    ) {
    }

    public function matches(MessageInterface $message): bool
    {
        $attachments = $message->attachments();

        if ($attachments === []) {
            return false;
        }

        return array_any($attachments, fn (AttachmentInterface $attachment): bool => $this->matchesPattern($attachment));
    }

    private function matchesPattern(AttachmentInterface $attachment): bool
    {
        if ($this->isExtensionPattern($this->pattern)) {
            return $this->matchesExtension($attachment);
        }

        return $this->matchesMimeType($attachment);
    }

    private function isExtensionPattern(string $pattern): bool
    {
        return str_starts_with($pattern, '.') ||
               str_starts_with($pattern, '*.') ||
               (!str_contains($pattern, '/') && !str_contains($pattern, '*'));
    }

    private function matchesExtension(AttachmentInterface $attachment): bool
    {
        $filename = $attachment->filename();

        if ($filename === null) {
            return false;
        }

        $extension = $attachment->extension();

        if ($extension === null) {
            return false;
        }

        $normalizedPattern = ltrim($this->pattern, '*.');

        return strcasecmp($extension, $normalizedPattern) === 0;
    }

    private function matchesMimeType(AttachmentInterface $attachment): bool
    {
        $contentType = $attachment->contentType();

        if (str_ends_with($this->pattern, '/*')) {
            $prefix = substr($this->pattern, 0, -2);
            return str_starts_with($contentType, $prefix . '/');
        }

        return strcasecmp($contentType, $this->pattern) === 0;
    }
}
