<?php

declare(strict_types=1);

namespace MailboxRules\ValueObject;

final readonly class Dsn implements \Stringable
{
    public function __construct(
        public string $protocol,
        #[\SensitiveParameter]
        public string $user,
        #[\SensitiveParameter]
        public string $password,
        public string $host,
        public int $port,
        public string $path,
    ) {
    }

    public static function fromString(string $dsn): self
    {
        $parts = parse_url($dsn);

        if ($parts === false) {
            throw new \InvalidArgumentException('Invalid DSN format');
        }

        if (!isset($parts['scheme'], $parts['user'], $parts['pass'], $parts['host'], $parts['port'])) {
            throw new \InvalidArgumentException('DSN must contain scheme, user, pass, host, and port');
        }

        return new self(
            $parts['scheme'],
            urldecode($parts['user']),
            $parts['pass'],
            $parts['host'],
            $parts['port'],
            ltrim($parts['path'] ?? '', '/'),
        );
    }


    public function __toString(): string
    {
        return sprintf(
            '%s://%s:%s@%s:%d/%s',
            $this->protocol,
            $this->user,
            $this->password,
            $this->host,
            $this->port,
            $this->path,
        );
    }
}
