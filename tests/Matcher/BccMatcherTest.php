<?php

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\BccMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BccMatcher::class)]
final class BccMatcherTest extends TestCase
{
    private function createAddress(string $email): Address
    {
        $address = $this->createStub(Address::class);
        $address->method('email')->willReturn($email);

        return $address;
    }

    public function testMatchesExactBccAddress(): void
    {
        $address = $this->createAddress('bcc@example.com');

        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$address]);

        $bccMatcher = new BccMatcher('bcc@example.com');

        self::assertTrue($bccMatcher->matches($message));
    }

    public function testMatchesWithWildcard(): void
    {
        $address = $this->createAddress('user@example.com');

        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$address]);

        $bccMatcher = new BccMatcher('*@example.com');

        self::assertTrue($bccMatcher->matches($message));
    }

    public function testMatchesAnyBccRecipient(): void
    {
        $address = $this->createAddress('first@example.com');
        $bcc2 = $this->createAddress('second@other.com');

        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$address, $bcc2]);

        $bccMatcher = new BccMatcher('*@other.com');

        self::assertTrue($bccMatcher->matches($message));
    }

    public function testDoesNotMatchDifferentDomain(): void
    {
        $address = $this->createAddress('user@example.com');

        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$address]);

        $bccMatcher = new BccMatcher('*@other.com');

        self::assertFalse($bccMatcher->matches($message));
    }

    public function testReturnsFalseForNoBccRecipients(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([]);

        $bccMatcher = new BccMatcher('user@example.com');

        self::assertFalse($bccMatcher->matches($message));
    }
}
