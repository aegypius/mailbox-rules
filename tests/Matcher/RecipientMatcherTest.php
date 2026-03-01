<?php

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\RecipientMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(RecipientMatcher::class)]
final class RecipientMatcherTest extends TestCase
{
    private function createAddress(string $email): Address
    {
        $address = $this->createStub(Address::class);
        $address->method('email')->willReturn($email);

        return $address;
    }

    public function testMatchesToRecipient(): void
    {
        $to = $this->createAddress('to@example.com');

        $message = $this->createStub(Message::class);
        $message->method('to')->willReturn([$to]);
        $message->method('cc')->willReturn([]);
        $message->method('bcc')->willReturn([]);

        $matcher = new RecipientMatcher('to@example.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesCcRecipient(): void
    {
        $cc = $this->createAddress('cc@example.com');

        $message = $this->createStub(Message::class);
        $message->method('to')->willReturn([]);
        $message->method('cc')->willReturn([$cc]);
        $message->method('bcc')->willReturn([]);

        $matcher = new RecipientMatcher('cc@example.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesBccRecipient(): void
    {
        $bcc = $this->createAddress('bcc@example.com');

        $message = $this->createStub(Message::class);
        $message->method('to')->willReturn([]);
        $message->method('cc')->willReturn([]);
        $message->method('bcc')->willReturn([$bcc]);

        $matcher = new RecipientMatcher('bcc@example.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testMatchesAnyRecipientType(): void
    {
        $to = $this->createAddress('to@example.com');
        $cc = $this->createAddress('cc@other.com');
        $bcc = $this->createAddress('bcc@third.com');

        $message = $this->createStub(Message::class);
        $message->method('to')->willReturn([$to]);
        $message->method('cc')->willReturn([$cc]);
        $message->method('bcc')->willReturn([$bcc]);

        $matcher = new RecipientMatcher('*@other.com');

        self::assertTrue($matcher->matches($message));
    }

    public function testDoesNotMatchWhenNotInAnyField(): void
    {
        $to = $this->createAddress('to@example.com');
        $cc = $this->createAddress('cc@example.com');

        $message = $this->createStub(Message::class);
        $message->method('to')->willReturn([$to]);
        $message->method('cc')->willReturn([$cc]);
        $message->method('bcc')->willReturn([]);

        $matcher = new RecipientMatcher('user@other.com');

        self::assertFalse($matcher->matches($message));
    }

    public function testReturnsFalseForNoRecipients(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('to')->willReturn([]);
        $message->method('cc')->willReturn([]);
        $message->method('bcc')->willReturn([]);

        $matcher = new RecipientMatcher('user@example.com');

        self::assertFalse($matcher->matches($message));
    }
}
