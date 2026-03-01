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
        $cc = $this->createAddress('cc@example.com');

        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$cc]);

        $matcher = new CcMatcher('cc@example.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesWithWildcard(): void
    {
        $cc = $this->createAddress('user@example.com');

        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$cc]);

        $matcher = new CcMatcher('*@example.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesAnyCcRecipient(): void
    {
        $cc1 = $this->createAddress('first@example.com');
        $cc2 = $this->createAddress('second@other.com');

        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$cc1, $cc2]);

        $matcher = new CcMatcher('*@other.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testDoesNotMatchDifferentDomain(): void
    {
        $cc = $this->createAddress('user@example.com');

        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([$cc]);

        $matcher = new CcMatcher('*@other.com');

        self::assertFalse($matcher->matches($message));
    }

    public function testReturnsFalseForNoCcRecipients(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('cc')->willReturn([]);

        $matcher = new CcMatcher('user@example.com');

        self::assertFalse($matcher->matches($message));
    }
}
