<?php

declare(strict_types=1);

namespace Tests\Model;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\MessageQuery;
use MailboxRules\Action\Flag;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;
use MailboxRules\Matcher\Matcher;
use MailboxRules\Model\PreviewResult;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rules::class)]
#[CoversClass(PreviewResult::class)]
class RulesPreviewTest extends TestCase
{
    /**
     * @param list<Message> $messages
     */
    private function createMailboxMock(array $messages): Mailbox
    {
        $mailbox = $this->createMock(Mailbox::class);
        $folder = $this->createMock(Folder::class);
        $query = $this->createMock(MessageQuery::class);

        $mailbox->expects($this->once())->method('connect');
        $mailbox->expects($this->once())->method('inbox')->willReturn($folder);
        $folder->expects($this->once())->method('messages')->willReturn($query);
        $query->expects($this->once())->method('withHeaders')->willReturn($query);
        $query->expects($this->once())->method('get')->willReturn(new MessageCollection($messages));

        return $mailbox;
    }

    public function testPreviewReturnsSingleRuleMatch(): void
    {
        $message = $this->createStub(Message::class);
        $mailbox = $this->createMailboxMock([$message]);

        $rule = new Rule(
            name: 'Test Rule',
            matcher: null,
            callback: static fn () => yield new MoveToFolder('Folder')
        );

        $rules = new Rules($mailbox, [$rule]);
        $results = $rules->preview();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(PreviewResult::class, $results[0]);
        $this->assertSame($message, $results[0]->message);
        $this->assertSame('Test Rule', $results[0]->ruleName);
        $this->assertCount(1, $results[0]->actions);
        $this->assertInstanceOf(MoveToFolder::class, $results[0]->actions[0]);
    }

    public function testPreviewReturnsMultipleActionsForSingleRule(): void
    {
        $message = $this->createStub(Message::class);
        $mailbox = $this->createMailboxMock([$message]);

        $rule = new Rule(
            name: 'Multi-Action Rule',
            matcher: null,
            callback: static function () {
                yield new MoveToFolder('Archive');
                yield new MarkAsRead();
                yield new Flag();
            }
        );

        $rules = new Rules($mailbox, [$rule]);
        $results = $rules->preview();

        $this->assertCount(1, $results);
        $this->assertCount(3, $results[0]->actions);
        $this->assertInstanceOf(MoveToFolder::class, $results[0]->actions[0]);
        $this->assertInstanceOf(MarkAsRead::class, $results[0]->actions[1]);
        $this->assertInstanceOf(Flag::class, $results[0]->actions[2]);
    }

    public function testPreviewReturnsMultipleRulesForSingleMessage(): void
    {
        $message = $this->createStub(Message::class);
        $mailbox = $this->createMailboxMock([$message]);

        $rule1 = new Rule(
            name: 'Rule 1',
            matcher: null,
            callback: static fn () => yield new MoveToFolder('Folder1')
        );

        $rule2 = new Rule(
            name: 'Rule 2',
            matcher: null,
            callback: static fn () => yield new MarkAsRead()
        );

        $rules = new Rules($mailbox, [$rule1, $rule2]);
        $results = $rules->preview();

        $this->assertCount(2, $results);
        $this->assertSame('Rule 1', $results[0]->ruleName);
        $this->assertSame('Rule 2', $results[1]->ruleName);
    }

    public function testPreviewSkipsNonMatchingRules(): void
    {
        $message = $this->createStub(Message::class);
        $mailbox = $this->createMailboxMock([$message]);

        $matcher = $this->createStub(Matcher::class);
        $matcher->method('matches')->willReturn(false);

        $rule = new Rule(
            name: 'Non-matching Rule',
            matcher: $matcher,
            callback: static fn () => yield new MoveToFolder('Folder')
        );

        $rules = new Rules($mailbox, [$rule]);
        $results = $rules->preview();

        $this->assertCount(0, $results);
    }

    public function testPreviewHandlesMultipleMessages(): void
    {
        $message1 = $this->createStub(Message::class);
        $message2 = $this->createStub(Message::class);
        $mailbox = $this->createMailboxMock([$message1, $message2]);

        $rule = new Rule(
            name: 'Test Rule',
            matcher: null,
            callback: static fn () => yield new Flag()
        );

        $rules = new Rules($mailbox, [$rule]);
        $results = $rules->preview();

        $this->assertCount(2, $results);
        $this->assertSame($message1, $results[0]->message);
        $this->assertSame($message2, $results[1]->message);
    }
}
