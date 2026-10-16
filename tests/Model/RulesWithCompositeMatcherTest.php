<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Action\Flag;
use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Adapter\InMemoryMessage;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Matcher\SubjectMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;

use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
final class RulesWithCompositeMatcherTest extends TestCase
{
    private function freeRule(): Rule
    {
        return new Rule(
            name: 'Free Mobile',
            matcher: new AllOfMatcher(new FolderMatcher('Free'), new SubjectMatcher('facture')),
            then: static fn () => yield new Flag()
        );
    }

    #[Test]
    public function apply_runs_a_folder_matcher_nested_in_allOf_in_that_folder_not_inbox(): void
    {
        $mailbox = new InMemoryMailbox('Free');
        $free = $mailbox->deliver('Free', subject: 'facture');
        $inbox = $mailbox->deliver('INBOX', subject: 'facture');

        (new Rules($mailbox, [$this->freeRule()]))->apply();

        self::assertTrue($free->isFlagged());
        self::assertFalse($inbox->isFlagged());
    }

    #[Test]
    public function preview_reports_the_nested_folder_not_inbox(): void
    {
        $mailbox = new InMemoryMailbox('Free');
        $free = $mailbox->deliver('Free', subject: 'facture');
        $mailbox->deliver('INBOX', subject: 'facture');

        $results = (new Rules($mailbox, [$this->freeRule()]))->preview();

        self::assertCount(1, $results);
        self::assertSame($free, $results[0]->message);
        self::assertFalse($free->isFlagged());
    }

    #[Test]
    public function it_handles_multiple_nested_folder_matchers(): void
    {
        $mailbox = new InMemoryMailbox('Free', 'Amazon');
        $messages = [
            $mailbox->deliver('Free', subject: 'facture'),
            $mailbox->deliver('Amazon', subject: 'order'),
        ];

        $amazon = new Rule(
            name: 'Amazon',
            matcher: new AllOfMatcher(new FolderMatcher('Amazon'), new SubjectMatcher('order')),
            then: static fn () => yield new Flag()
        );
        (new Rules($mailbox, [$this->freeRule(), $amazon]))->apply();

        self::assertSame([true, true], array_map(static fn (InMemoryMessage $m): bool => $m->isFlagged(), $messages));
    }
}
