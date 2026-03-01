<?php

declare(strict_types=1);

namespace Tests;

use MailboxRules\Matcher\BccMatcher;
use MailboxRules\Matcher\CcMatcher;
use MailboxRules\Matcher\RecipientMatcher;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\bcc;
use function MailboxRules\cc;
use function MailboxRules\recipient;

#[CoversFunction('MailboxRules\cc')]
#[CoversFunction('MailboxRules\bcc')]
#[CoversFunction('MailboxRules\recipient')]
final class RecipientMatcherHelpersTest extends TestCase
{
    public function testCcReturnsCorrectMatcher(): void
    {
        $matcher = cc('user@example.com');
        self::assertInstanceOf(CcMatcher::class, $matcher);
    }

    public function testBccReturnsCorrectMatcher(): void
    {
        $matcher = bcc('user@example.com');
        self::assertInstanceOf(BccMatcher::class, $matcher);
    }

    public function testRecipientReturnsCorrectMatcher(): void
    {
        $matcher = recipient('user@example.com');
        self::assertInstanceOf(RecipientMatcher::class, $matcher);
    }
}
