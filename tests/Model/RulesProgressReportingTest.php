<?php

declare(strict_types=1);

namespace Tests\Model;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\FolderRepository;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Contract\MessageInterface;
use DirectoryTree\ImapEngine\MessageQuery;
use MailboxRules\Action\LogAction;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\FolderMatcher;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

#[CoversClass(Rules::class)]
final class RulesProgressReportingTest extends TestCase
{
    private function makeQuery(MessageCollection $messages): MessageQuery
    {
        $query = $this->createStub(MessageQuery::class);
        $query->method('withHeaders')->willReturn($query);
        $query->method('get')->willReturn($messages);
        return $query;
    }

    #[Test]
    public function apply_logs_info_when_processing_folder(): void
    {
        $query = $this->makeQuery(new MessageCollection([]));
        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($folder);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with(
                $this->anything(),
                $this->callback(static fn (array $ctx): bool => ($ctx['folder'] ?? null) === 'INBOX')
            );

        $rule = new Rule('test', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction());
        $rules = new Rules($mailbox, [$rule], $logger);
        $rules->apply();
    }

    #[Test]
    public function apply_logs_info_message_count_per_folder(): void
    {
        $msg1 = $this->createStub(Message::class);
        $msg2 = $this->createStub(Message::class);

        $query = $this->makeQuery(new MessageCollection([$msg1, $msg2]));
        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($folder);

        $infoCalls = [];
        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('info')
            ->willReturnCallback(function (string $msg, array $ctx) use (&$infoCalls): void {
                $infoCalls[] = $ctx;
            });

        $rule = new Rule('test', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction());
        $rules = new Rules($mailbox, [$rule], $logger);
        $rules->apply();

        $matched = array_filter($infoCalls, static fn (array $ctx): bool => ($ctx['messages_count'] ?? null) === 2);
        $this->assertNotEmpty($matched, 'Expected info() called with messages_count=2');
    }

    #[Test]
    public function apply_logs_info_when_rule_matches_message(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('subject')->willReturn('Hello world');

        $query = $this->makeQuery(new MessageCollection([$message]));
        $folder = $this->createStub(Folder::class);
        $folder->method('messages')->willReturn($query);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($folder);

        $infoCalls = [];
        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('info')
            ->willReturnCallback(function (string $msg, array $ctx) use (&$infoCalls): void {
                $infoCalls[] = $ctx;
            });

        $rule = new Rule('My Rule', new AnyMatcher(), static fn (MessageInterface $m): iterable => yield new LogAction());
        $rules = new Rules($mailbox, [$rule], $logger);
        $rules->apply();

        $matched = array_filter($infoCalls, static fn (array $ctx): bool => ($ctx['rule'] ?? null) === 'My Rule');
        $this->assertNotEmpty($matched, 'Expected info() called with rule="My Rule"');
    }

    #[Test]
    public function apply_logs_warning_when_folder_not_found(): void
    {
        $folders = $this->createStub(FolderRepository::class);
        $folders->method('find')->willReturn(null);

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('folders')->willReturn($folders);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->anything(), $this->anything());

        $rule = new Rule(
            'test',
            new AllOfMatcher(new FolderMatcher('Missing')),
            static fn (MessageInterface $m): iterable => yield new LogAction()
        );
        $rules = new Rules($mailbox, [$rule], $logger);
        $rules->apply();
    }

    #[Test]
    public function watch_logs_info_when_watching_folder(): void
    {
        $inbox = $this->createStub(Folder::class);
        $inbox->method('idle')->willReturnCallback(static function (callable $cb): void {});

        $mailbox = $this->createStub(Mailbox::class);
        $mailbox->method('inbox')->willReturn($inbox);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->atLeastOnce())
            ->method('info')
            ->with(
                $this->anything(),
                $this->callback(static fn (array $ctx): bool => ($ctx['folder'] ?? null) === 'INBOX')
            );

        $rule = new Rule('test', null, static fn (MessageInterface $m): iterable => yield new LogAction());
        $rules = new Rules($mailbox, [$rule], $logger);
        $rules->watch();
    }
}
