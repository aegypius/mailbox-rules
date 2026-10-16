<?php

declare(strict_types=1);

namespace MailboxRules\ValueObject;

final readonly class EmailAddress
{
    public function __construct(private string $email)
    {
    }

    public function email(): string
    {
        return $this->email;
    }
}
