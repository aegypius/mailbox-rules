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
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;

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

    /**
     * @return array{LoggerInterface, \ArrayObject<int, string>} Logger and its warnings.
     */
    private function logger(): array
    {
        /** @var \ArrayObject<int, string> $warnings */
        $warnings = new \ArrayObject();
        $logger = new class($warnings) extends AbstractLogger {
            /**
             * @param \ArrayObject<int, string> $warnings
             */
            public function __construct(public readonly \ArrayObject $warnings)
            {
            }

            public function log(mixed $level, string|\Stringable $message, array $context = []): void
            {
                if ($level === 'warning') {
                    $folder = $context['folder'] ?? '';
                    $this->warnings[] = (string) $message . ' ' . (is_string($folder) ? $folder : '');
                }
            }
        };

        return [$logger, $warnings];
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
    public function a_missing_folder_is_warned_about_in_every_mode(): void
    {
        $mailbox = new InMemoryMailbox();
        [$logger, $warnings] = $this->logger();
        $traversal = new Traversal($mailbox, [$this->rule('R', 'Gone')], $logger);
        [$step] = $this->recorder();

        $traversal->pass($step);
        $traversal->poll($step);

        self::assertSame(['Folder not found: {folder}, skipping rules Gone', 'Folder not found: {folder}, skipping rules Gone'], $warnings->getArrayCopy());
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
