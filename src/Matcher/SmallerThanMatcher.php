<?php

declare(strict_types=1);

namespace MailboxRules\Matcher;

use DirectoryTree\ImapEngine\Message;

/**
 * Matcher that matches messages smaller than a specific size.
 */
final readonly class SmallerThanMatcher implements Matcher
{
    private int $bytes;

    /**
     * @param int|string $size Size in bytes or human-readable format (e.g., "1KB", "5MB", "2GB")
     */
    public function __construct(int|string $size)
    {
        $this->bytes = is_int($size) ? $size : self::parseSize($size);
    }

    public function matches(Message $message): bool
    {
        $messageSize = $message->size();

        if ($messageSize === null) {
            return false;
        }

        return $messageSize < $this->bytes;
    }

    private static function parseSize(string $size): int
    {
        $size = trim($size);
        $size = strtoupper($size);

        if (preg_match('/^(\d+(?:\.\d+)?)\s*(KB|MB|GB|K|M|G)?$/i', $size, $matches)) {
            $value = (float) $matches[1];
            $unit = $matches[2] ?? '';

            return (int) match ($unit) {
                'KB', 'K' => $value * 1024,
                'MB', 'M' => $value * 1024 * 1024,
                'GB', 'G' => $value * 1024 * 1024 * 1024,
                default => $value,
            };
        }

        throw new \InvalidArgumentException(sprintf('Invalid size format: "%s"', $size));
    }
}
