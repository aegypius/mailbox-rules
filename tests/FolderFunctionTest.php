<?php

declare(strict_types=1);

namespace Tests;

use MailboxRules\Matcher\FolderMatcher;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use function MailboxRules\folder;

#[CoversFunction('MailboxRules\folder')]
final class FolderFunctionTest extends TestCase
{
    #[Test]
    public function it_creates_folder_matcher(): void
    {
        $matcher = folder('INBOX');

        $this->assertInstanceOf(FolderMatcher::class, $matcher);
    }

    #[Test]
    public function it_accepts_wildcard_patterns(): void
    {
        $matcher = folder('Archives/*');

        $this->assertInstanceOf(FolderMatcher::class, $matcher);
    }

    #[Test]
    public function it_accepts_regex_patterns(): void
    {
        $matcher = folder('/^Projects\\/Client[A-Z]+$/i');

        $this->assertInstanceOf(FolderMatcher::class, $matcher);
    }
}
