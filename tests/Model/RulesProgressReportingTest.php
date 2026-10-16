<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Action\LogAction;
use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use Monolog\Handler\TestHandler;
use Monolog\Level;
use Monolog\Logger;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
final class RulesProgressReportingTest extends TestCase
{
    private function rule(string $name = 'test', ?\MailboxRules\Matcher\Matcher $matcher = null): Rule
    {
        return new Rule($name, $matcher ?? new AnyMatcher(), static fn (): iterable => yield new LogAction());
    }

    /**
     * @return list<mixed>
     */
    private function contexts(TestHandler $handler, Level $level, string $key): array
    {
        $values = [];
        foreach ($handler->getRecords() as $record) {
            if ($record->level === $level && isset($record->context[$key])) {
                $values[] = $record->context[$key];
            }
        }

        return $values;
    }

    #[Test]
    public function apply_logs_info_when_processing_folder(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);

        (new Rules(new InMemoryMailbox(), [$this->rule()], $logger))->apply();

        self::assertContains('INBOX', $this->contexts($handler, Level::Info, 'folder'));
    }

    #[Test]
    public function apply_logs_info_message_count_per_folder(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $mailbox = new InMemoryMailbox();
        $mailbox->deliver('INBOX');
        $mailbox->deliver('INBOX');

        (new Rules($mailbox, [$this->rule()], $logger))->apply();

        self::assertContains(2, $this->contexts($handler, Level::Info, 'messages_count'));
    }

    #[Test]
    public function apply_logs_info_when_rule_matches_message(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $mailbox = new InMemoryMailbox();
        $mailbox->deliver('INBOX', subject: 'Hello world');

        (new Rules($mailbox, [$this->rule('My Rule')], $logger))->apply();

        self::assertContains('My Rule', $this->contexts($handler, Level::Info, 'rule'));
    }

    #[Test]
    public function apply_logs_warning_when_no_folder_is_in_scope(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);
        $rule = $this->rule('test', new AllOfMatcher(new FolderMatcher('Missing')));

        (new Rules(new InMemoryMailbox(), [$rule], $logger))->apply();

        self::assertContains('test', $this->contexts($handler, Level::Warning, 'rule'));
    }

    #[Test]
    public function watch_logs_info_when_processing_folder(): void
    {
        $handler = new TestHandler();
        $logger = new Logger('test', [$handler]);

        (new Rules(new InMemoryMailbox(), [$this->rule()], $logger))->poll();

        self::assertContains('INBOX', $this->contexts($handler, Level::Info, 'folder'));
    }
}
