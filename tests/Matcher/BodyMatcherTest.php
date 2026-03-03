<?php

declare(strict_types=1);

namespace Tests\Matcher;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\BodyMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(BodyMatcher::class)]
final class BodyMatcherTest extends TestCase
{
    public function testMatchesExactTextInPlainTextBody(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn('Please find the invoice attached.');
        $message->method('html')->willReturn(null);

        $bodyMatcher = new BodyMatcher('*invoice attached*');

        self::assertTrue($bodyMatcher->matches($message));
    }

    public function testMatchesWildcardInPlainTextBody(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn('Meeting scheduled for tomorrow at 3pm');
        $message->method('html')->willReturn(null);

        $bodyMatcher = new BodyMatcher('*meeting*');

        self::assertTrue($bodyMatcher->matches($message));
    }

    public function testMatchesExactTextInHtmlBody(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn(null);
        $message->method('html')->willReturn('<p>Your order #12345 has shipped.</p>');

        $bodyMatcher = new BodyMatcher('*order #12345*');

        self::assertTrue($bodyMatcher->matches($message));
    }

    public function testMatchesWildcardInHtmlBody(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn(null);
        $message->method('html')->willReturn('<html><body>Confirmation code: ABC123</body></html>');

        $bodyMatcher = new BodyMatcher('*confirmation*');

        self::assertTrue($bodyMatcher->matches($message));
    }

    public function testMatchesInEitherTextOrHtmlBody(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn('Plain text version');
        $message->method('html')->willReturn('<p>HTML version with special keyword</p>');

        $matcherText = new BodyMatcher('*plain text*');
        $matcherHtml = new BodyMatcher('*special keyword*');

        self::assertTrue($matcherText->matches($message));
        self::assertTrue($matcherHtml->matches($message));
    }

    public function testDoesNotMatchWhenPatternNotFound(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn('This is the message body');
        $message->method('html')->willReturn('<p>HTML body content</p>');

        $bodyMatcher = new BodyMatcher('nonexistent');

        self::assertFalse($bodyMatcher->matches($message));
    }

    public function testReturnsFalseWhenBothBodiesAreNull(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn(null);
        $message->method('html')->willReturn(null);

        $bodyMatcher = new BodyMatcher('anything');

        self::assertFalse($bodyMatcher->matches($message));
    }

    public function testMatchesRegexPattern(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn('Your verification code is 123456');
        $message->method('html')->willReturn(null);

        $bodyMatcher = new BodyMatcher('/\d{6}/');

        self::assertTrue($bodyMatcher->matches($message));
    }

    public function testIsCaseInsensitive(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn('IMPORTANT NOTICE');
        $message->method('html')->willReturn(null);

        $bodyMatcher = new BodyMatcher('important notice');

        self::assertTrue($bodyMatcher->matches($message));
    }

    public function testChecksTextBodyFirst(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('text')->willReturn('found in text');
        $message->method('html')->willReturn('<p>found in html</p>');

        $bodyMatcher = new BodyMatcher('*found*');

        // Both contain the pattern, should match (text is checked first)
        self::assertTrue($bodyMatcher->matches($message));
    }
}
