<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Action\Flag;
use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyOfMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Matcher\SubjectMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * anyOf branches without a folder() run in INBOX; branches with one run in that Folder.
 */
#[CoversClass(Rules::class)]
final class RulesWithMixedAnyOfBranchesTest extends TestCase
{
    #[Test]
    public function it_runs_in_inbox_for_plain_branches_and_in_the_folder_for_folder_branches(): void
    {
        $mailbox = new InMemoryMailbox('Promotions/Steam');
        $inboxHit = $mailbox->deliver('INBOX', subject: 'Login Code');
        $inboxMiss = $mailbox->deliver('INBOX', subject: 'sale');
        $steamHit = $mailbox->deliver('Promotions/Steam', subject: 'sale');
        $untouchedFolder = $mailbox->deliver('Other', subject: 'Login Code');

        $rule = new Rule(
            name: 'Cleanup',
            matcher: new AnyOfMatcher(
                new AllOfMatcher(new SubjectMatcher('Login Code')),
                new AllOfMatcher(new FolderMatcher('Promotions/Steam'), new SubjectMatcher('sale')),
            ),
            then: static fn () => yield new Flag()
        );
        (new Rules($mailbox, [$rule]))->apply();

        self::assertTrue($inboxHit->isFlagged());
        self::assertTrue($steamHit->isFlagged());
        self::assertFalse($inboxMiss->isFlagged());
        self::assertFalse($untouchedFolder->isFlagged(), 'Folders outside the scope are never read');
    }
}
