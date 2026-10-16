<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Action\Flag;
use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
final class RulesWatchTest extends TestCase
{
    /**
     * @return array{Rule, \ArrayObject<int, ?string>} Rule recording every subject it ran on.
     */
    private function recordingRule(?\Closure $onMessage = null): array
    {
        /** @var \ArrayObject<int, ?string> $seen */
        $seen = new \ArrayObject();
        $rule = new Rule(
            'Record',
            new AnyMatcher(),
            static function (MessageInterface $m) use ($seen, $onMessage): iterable {
                $seen[] = $m->subject();
                if ($onMessage !== null) {
                    $onMessage($m);
                }
                yield new Flag();
            },
        );

        return [$rule, $seen];
    }

    #[Test]
    public function the_first_pass_applies_rules_to_existing_messages(): void
    {
        $mailbox = new InMemoryMailbox();
        $mailbox->deliver('INBOX', subject: 'old');
        [$rule, $seen] = $this->recordingRule();

        (new Rules($mailbox, [$rule]))->watch(interval: 0, passes: 1);

        self::assertSame(['old'], $seen->getArrayCopy());
    }

    #[Test]
    public function later_passes_only_run_rules_on_newly_arrived_messages(): void
    {
        $mailbox = new InMemoryMailbox();
        $mailbox->deliver('INBOX', subject: 'old');
        $delivered = false;
        [$rule, $seen] = $this->recordingRule(static function () use ($mailbox, &$delivered): void {
            if (!$delivered) {
                $delivered = true;
                $mailbox->deliver('INBOX', subject: 'new');
            }
        });

        (new Rules($mailbox, [$rule]))->watch(interval: 0, passes: 3);

        self::assertSame(['old', 'new'], $seen->getArrayCopy());
    }

    #[Test]
    public function watch_targets_the_rules_folder_scope(): void
    {
        $mailbox = new InMemoryMailbox('Free');
        $free = $mailbox->deliver('Free');
        $inbox = $mailbox->deliver('INBOX');
        $rule = new Rule('Free', new AllOfMatcher(new FolderMatcher('Free')), static fn () => yield new Flag());

        (new Rules($mailbox, [$rule]))->watch(interval: 0, passes: 1);

        self::assertTrue($free->isFlagged());
        self::assertFalse($inbox->isFlagged());
    }
}
