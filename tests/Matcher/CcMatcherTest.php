<?php

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\CcMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(CcMatcher::class)]
final class CcMatcherTest extends TestCase
{
    private function createAddress(string $email): Address
    {
        $address = $this->createStub(Address::class);
        $address->method('email')->willReturn($email);

        return $address;
    }

    public function testMatchesExactCcAddress(): void
    {
        $address = $this->createAddress('cc@example.com');

        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$address]);

        $ccMatcher = new CcMatcher('cc@example.com');

        self::assertTrue($ccMatcher->matches($message));
    }

    public function testMatchesWithWildcard(): void
    {
        $address = $this->createAddress('user@example.com');

        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$address]);

        $ccMatcher = new CcMatcher('*@example.com');

        self::assertTrue($ccMatcher->matches($message));
    }

    public function testMatchesAnyCcRecipient(): void
    {
        $address = $this->createAddress('first@example.com');
        $cc2 = $this->createAddress('second@other.com');

        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$address, $cc2]);

        $ccMatcher = new CcMatcher('*@other.com');

        self::assertTrue($ccMatcher->matches($message));
    }

    public function testDoesNotMatchDifferentDomain(): void
    {
        $address = $this->createAddress('user@example.com');

        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$address]);

        $ccMatcher = new CcMatcher('*@other.com');

        self::assertFalse($ccMatcher->matches($message));
    }

    public function testReturnsFalseForNoCcRecipients(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([]);

        $ccMatcher = new CcMatcher('user@example.com');

        self::assertFalse($ccMatcher->matches($message));
    }
}
