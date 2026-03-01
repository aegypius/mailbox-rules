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
        $bcc = $this->createAddress('bcc@example.com');

        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$bcc]);

        $matcher = new BccMatcher('bcc@example.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesWithWildcard(): void
    {
        $bcc = $this->createAddress('user@example.com');

        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$bcc]);

        $matcher = new BccMatcher('*@example.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesAnyBccRecipient(): void
    {
        $bcc1 = $this->createAddress('first@example.com');
        $bcc2 = $this->createAddress('second@other.com');

        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$bcc1, $bcc2]);

        $matcher = new BccMatcher('*@other.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testDoesNotMatchDifferentDomain(): void
    {
        $bcc = $this->createAddress('user@example.com');

        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([$bcc]);

        $matcher = new BccMatcher('*@other.com');

        self::assertFalse($matcher->matches($message));
    }

    public function testReturnsFalseForNoBccRecipients(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('bcc')->willReturn([]);

        $matcher = new BccMatcher('user@example.com');

        self::assertFalse($matcher->matches($message));
    }
}
