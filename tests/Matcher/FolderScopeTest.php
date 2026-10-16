<?php

declare(strict_types=1);

namespace Tests\Matcher;

use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\AnyOfMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Matcher\FromMatcher;
use MailboxRules\Matcher\NotMatcher;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(FolderMatcher::class)]
#[CoversClass(AllOfMatcher::class)]
#[CoversClass(AnyOfMatcher::class)]
#[CoversClass(NotMatcher::class)]
#[CoversClass(AnyMatcher::class)]
#[CoversClass(FromMatcher::class)]
final class FolderScopeTest extends TestCase
{
    private const FOLDERS = ['INBOX', 'Archives', 'Archives/2024', 'Archives/2025', 'Work'];

    #[Test]
    public function matcherWithoutFolderConstraintDoesNotScope(): void
    {
        self::assertNull((new AnyMatcher())->folderScope(self::FOLDERS));
        self::assertNull((new FromMatcher('a@b.c'))->folderScope(self::FOLDERS));
    }

    #[Test]
    public function patternExpandsToEveryMatchingFolder(): void
    {
        self::assertSame(
            ['Archives/2024', 'Archives/2025'],
            (new FolderMatcher('Archives/*'))->folderScope(self::FOLDERS),
        );
        self::assertSame(['Work'], (new FolderMatcher('Work'))->folderScope(self::FOLDERS));
    }

    #[Test]
    public function allOfIntersectsAndIgnoresUnscopedChildren(): void
    {
        $scope = (new AllOfMatcher(new FromMatcher('a@b.c'), new FolderMatcher('Archives*'), new FolderMatcher('*/2025')))
            ->folderScope(self::FOLDERS);

        self::assertSame(['Archives/2025'], $scope);
    }

    #[Test]
    public function allOfMayBeEmpty(): void
    {
        self::assertSame(
            [],
            (new AllOfMatcher(new FolderMatcher('Work'), new FolderMatcher('Archives')))->folderScope(self::FOLDERS),
        );
    }

    #[Test]
    public function anyOfUnitesBranchesWithUnscopedOnesCountingAsInbox(): void
    {
        $scope = (new AnyOfMatcher(new FolderMatcher('Work'), new FromMatcher('a@b.c'), new FolderMatcher('Work')))
            ->folderScope(self::FOLDERS);

        self::assertSame(['Work', 'INBOX'], $scope);
    }

    #[Test]
    public function notSubtractsFromInbox(): void
    {
        self::assertSame([], (new NotMatcher(new FolderMatcher('INBOX')))->folderScope(self::FOLDERS));
        self::assertSame(['INBOX'], (new NotMatcher(new FolderMatcher('Work')))->folderScope(self::FOLDERS));
        self::assertNull((new NotMatcher(new FromMatcher('a@b.c')))->folderScope(self::FOLDERS));
    }
}
