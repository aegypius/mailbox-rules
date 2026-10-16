<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Traversal;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Traversal::class)]
final class TraversalTest extends TestCase
{
    private function rule(string $name, ?string $folder = null): Rule
    {
        return new Rule(
            $name,
            $folder === null ? new AnyMatcher() : new AllOfMatcher(new FolderMatcher($folder)),
            static fn (): iterable => [],
        );
    }

    /**
     * @return array{\Closure(MessageInterface, list<Rule>): void, \ArrayObject<int, string>}
     */
    private function recorder(): array
    {
        /** @var \ArrayObject<int, string> $visits */
        $visits = new \ArrayObject();

        $step = static function (MessageInterface $m, array $rules) use ($visits): void {
            $names = [];
            foreach ($rules as $rule) {
                assert($rule instanceof Rule);
                $names[] = $rule->name;
            }
            $visits[] = sprintf('%s:%s:%s', $m->folderPath(), $m->subject(), implode(',', $names));
        };

        return [$step, $visits];
    }

    #[Test]
    public function a_pass_gives_each_message_the_rules_of_its_folder(): void
    {
        $mailbox = new InMemoryMailbox('Free');
        $mailbox->deliver('INBOX', subject: 'a');
        $mailbox->deliver('Free', subject: 'b');
        [$step, $visits] = $this->recorder();

        (new Traversal($mailbox, [$this->rule('R1'), $this->rule('R2', 'Free')]))->pass($step);

        self::assertSame(['INBOX:a:R1', 'Free:b:R2'], $visits->getArrayCopy());
    }

    #[Test]
    public function a_pattern_expands_to_every_matching_folder(): void
    {
        $mailbox = new InMemoryMailbox('Archives/2024', 'Archives/2025', 'Work');
        foreach (['INBOX', 'Archives/2024', 'Archives/2025', 'Work'] as $folder) {
            $mailbox->deliver($folder, subject: $folder);
        }
        [$step, $visits] = $this->recorder();

        (new Traversal($mailbox, [$this->rule('R', 'Archives/*')]))->pass($step);

        self::assertSame(['Archives/2024:Archives/2024:R', 'Archives/2025:Archives/2025:R'], $visits->getArrayCopy());
    }

    #[Test]
    public function a_pattern_matches_folders_created_after_the_traversal_was_built(): void
    {
        $mailbox = new InMemoryMailbox();
        $traversal = new Traversal($mailbox, [$this->rule('R', 'Archives/*')]);
        [$step, $visits] = $this->recorder();

        $traversal->pass($step);
        $mailbox->addFolder('Archives/2026');
        $mailbox->deliver('Archives/2026', subject: 'late');
        $traversal->pass($step);

        self::assertSame(['Archives/2026:late:R'], $visits->getArrayCopy());
    }

    #[Test]
    public function a_pass_honours_the_limit_per_folder(): void
    {
        $mailbox = new InMemoryMailbox();
        foreach (['a', 'b', 'c'] as $subject) {
            $mailbox->deliver('INBOX', subject: $subject);
        }
        [$step, $visits] = $this->recorder();

        (new Traversal($mailbox, [$this->rule('R')]))->pass($step, limit: 2);

        self::assertSame(['INBOX:a:R', 'INBOX:b:R'], $visits->getArrayCopy());
    }

    #[Test]
    public function polling_visits_everything_first_then_only_messages_above_each_folders_mark(): void
    {
        $mailbox = new InMemoryMailbox('Free');
        $mailbox->deliver('INBOX', subject: 'old-i');
        $mailbox->deliver('Free', subject: 'old-f');
        $traversal = new Traversal($mailbox, [$this->rule('R1'), $this->rule('R2', 'Free')]);
        [$step, $visits] = $this->recorder();

        $traversal->poll($step);
        $mailbox->deliver('Free', subject: 'new-f');
        $traversal->poll($step);
        $traversal->poll($step);

        self::assertSame(
            ['INBOX:old-i:R1', 'Free:old-f:R2', 'Free:new-f:R2'],
            $visits->getArrayCopy(),
        );
    }

    #[Test]
    public function a_folder_created_between_polls_is_picked_up(): void
    {
        $mailbox = new InMemoryMailbox();
        $traversal = new Traversal($mailbox, [$this->rule('R', 'Later')]);
        [$step, $visits] = $this->recorder();

        $traversal->poll($step);
        $mailbox->deliver('Later', subject: 'x');
        $traversal->poll($step);

        self::assertSame(['Later:x:R'], $visits->getArrayCopy());
    }
}
