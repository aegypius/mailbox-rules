<?php

declare(strict_types=1);

namespace MailboxRules\Loader;

use MailboxRules\ValueObject\MailboxConfiguration;

final class RuleFileLoader
{
    /**
     * @var array<string, mixed>|null
     */
    private static ?array $cache = [];

    /**
     * Load mailbox configurations from a configuration file.
     *
     * @param string $resource Path to the configuration file
     * @return iterable<MailboxConfiguration> An iterable of MailboxConfiguration objects
     */
    public function load(string $resource): iterable
    {
        if ([] === self::$cache && \function_exists('opcache_invalidate') && filter_var(\ini_get('opcache.enable'), \FILTER_VALIDATE_BOOL) && (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) || filter_var(\ini_get('opcache.enable_cli'), \FILTER_VALIDATE_BOOL))) {
            self::$cache = null;
        }

        try {
            if (null === self::$cache) {
                $result = require $resource;
                /** @var MailboxConfiguration|iterable<MailboxConfiguration> $result */
                return $this->normalizeResult($result);
            }

            $cached = self::$cache[$resource] ?? null;
            if ($cached !== null) {
                return $this->normalizeResult($cached);
            }

            $result = require $resource;
            /** @var MailboxConfiguration|iterable<MailboxConfiguration> $result */
            self::$cache[$resource] = $result;
            return $this->normalizeResult($result);
        } catch (\Throwable $throwable) {
            throw new \InvalidArgumentException(\sprintf('Unable to load file "%s" : %s.', $resource, $throwable->getMessage()), $throwable->getCode(), $throwable);
        }
    }

    /**
     * Normalize the result from a configuration file.
     *
     * Supports both single MailboxConfiguration objects (backward compatibility) and
     * iterable of MailboxConfiguration objects (for multiple mailboxes).
     *
     * @param mixed $result The result from requiring the config file
     * @return iterable<MailboxConfiguration> Normalized iterable of MailboxConfiguration objects
     */
    private function normalizeResult(mixed $result): iterable
    {
        // Single MailboxConfiguration object - wrap in array for iteration
        if ($result instanceof MailboxConfiguration) {
            return [$result];
        }

        // Already an iterable of MailboxConfiguration objects - materialize generators to avoid "already closed" errors
        if (is_iterable($result)) {
            $materialized = [];
            /** @var iterable<mixed> $result */
            // Validate that all items are MailboxConfiguration objects while materializing
            foreach ($result as $item) {
                if (!$item instanceof MailboxConfiguration) {
                    throw new \InvalidArgumentException(
                        'Configuration file must return a MailboxConfiguration object or an iterable of MailboxConfiguration objects'
                    );
                }
                $materialized[] = $item;
            }
            return $materialized;
        }

        throw new \InvalidArgumentException(
            'Configuration file must return a MailboxConfiguration object or an iterable of MailboxConfiguration objects'
        );
    }
}
