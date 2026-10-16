<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Action\Flag;
use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Matcher\SubjectMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
final class RulesWithNestedFolderTest extends TestCase
{
    #[Test]
    public function it_runs_the_rule_in_a_nested_folder_only(): void
    {
        $mailbox = new InMemoryMailbox('Promotions/Steam');
        $steam = $mailbox->deliver('Promotions/Steam', subject: 'test');
        $inbox = $mailbox->deliver('INBOX', subject: 'test');

        $rule = new Rule(
            name: 'Steam Promotions',
            matcher: new AllOfMatcher(new FolderMatcher('Promotions/Steam'), new SubjectMatcher('test')),
            then: static fn () => yield new Flag()
        );

        (new Rules($mailbox, [$rule]))->apply();

        self::assertTrue($steam->isFlagged());
        self::assertFalse($inbox->isFlagged());
    }

    #[Test]
    public function it_handles_multiple_nested_folder_paths(): void
    {
        $mailbox = new InMemoryMailbox('Promotions/Steam', 'Archives/2024');
        $steam = $mailbox->deliver('Promotions/Steam', subject: 'game');
        $archive = $mailbox->deliver('Archives/2024', subject: 'old');

        $rules = new Rules($mailbox, [
            new Rule('Steam', new AllOfMatcher(new FolderMatcher('Promotions/Steam'), new SubjectMatcher('game')), static fn () => yield new Flag()),
            new Rule('Archive', new AllOfMatcher(new FolderMatcher('Archives/2024'), new SubjectMatcher('old')), static fn () => yield new Flag()),
        ]);
        $rules->apply();

        self::assertTrue($steam->isFlagged());
        self::assertTrue($archive->isFlagged());
    }
}
