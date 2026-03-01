<?php

declare(strict_types=1);

namespace Tests;

use MailboxRules\Matcher\BodyMatcher;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\body;

#[CoversFunction('MailboxRules\body')]
final class BodyMatcherHelpersTest extends TestCase
{
    public function testBodyReturnsCorrectMatcher(): void
    {
        $matcher = body('*invoice*');
        self::assertInstanceOf(BodyMatcher::class, $matcher);
    }
}
