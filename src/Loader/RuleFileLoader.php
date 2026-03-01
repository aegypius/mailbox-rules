<?php

declare(strict_types=1);

namespace MailboxRules\Loader;

use MailboxRules\Model\Rules;

final class RuleFileLoader
{
    /**
     * @var array<string, Rules>|null
     */
    private static ?array $cache = [];

    public function load(string $resource): Rules
    {
        if ([] === self::$cache && \function_exists('opcache_invalidate') && filter_var(\ini_get('opcache.enable'), \FILTER_VALIDATE_BOOL) && (!\in_array(\PHP_SAPI, ['cli', 'phpdbg', 'embed'], true) || filter_var(\ini_get('opcache.enable_cli'), \FILTER_VALIDATE_BOOL))) {
            self::$cache = null;
        }

        try {
            if (null === self::$cache) {
                $result = require $resource;
                assert($result instanceof Rules);
                return $result;
            }

            $cached = self::$cache[$resource] ?? null;
            if ($cached instanceof Rules) {
                return $cached;
            }
            $result = require $resource;
            assert($result instanceof Rules);
            self::$cache[$resource] = $result;
            return $result;
        } catch (\Throwable $throwable) {
            throw new \InvalidArgumentException(\sprintf('Unable to load file "%s" : %s.', $resource, $throwable->getMessage()), $throwable->getCode(), $throwable);
        }
    }
}
