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

#[CoversClass(Rules::class)]
final class RulesWithDeeplyNestedFolderTest extends TestCase
{
    #[Test]
    public function it_handles_folder_matcher_nested_in_anyOf_allOf(): void
    {
        $mailbox = new InMemoryMailbox('Promotions/Steam');
        $inbox = $mailbox->deliver('INBOX', subject: 'test1');
        $steam = $mailbox->deliver('Promotions/Steam', subject: 'test2');

        $rule = new Rule(
            name: 'Deep',
            matcher: new AnyOfMatcher(
                new AllOfMatcher(new SubjectMatcher('test1')),
                new AllOfMatcher(new FolderMatcher('Promotions/Steam'), new SubjectMatcher('test2')),
            ),
            then: static fn () => yield new Flag()
        );
        (new Rules($mailbox, [$rule]))->apply();

        self::assertTrue($inbox->isFlagged());
        self::assertTrue($steam->isFlagged());
    }

    #[Test]
    public function it_handles_multiple_folder_matchers_in_different_anyOf_branches(): void
    {
        $mailbox = new InMemoryMailbox('Free', 'Promotions/Steam', 'Amazon');
        $messages = [
            $mailbox->deliver('Free', subject: 'facture'),
            $mailbox->deliver('Promotions/Steam', subject: 'sale'),
            $mailbox->deliver('Amazon', subject: 'order'),
        ];
        $untouched = $mailbox->deliver('Amazon', subject: 'sale');

        $rule = new Rule(
            name: 'Multi',
            matcher: new AnyOfMatcher(
                new AllOfMatcher(new FolderMatcher('Free'), new SubjectMatcher('facture')),
                new AllOfMatcher(new FolderMatcher('Promotions/Steam'), new SubjectMatcher('sale')),
                new AllOfMatcher(new FolderMatcher('Amazon'), new SubjectMatcher('order')),
            ),
            then: static fn () => yield new Flag()
        );
        (new Rules($mailbox, [$rule]))->apply();

        foreach ($messages as $message) {
            self::assertTrue($message->isFlagged());
        }
        self::assertFalse($untouched->isFlagged());
    }
}
