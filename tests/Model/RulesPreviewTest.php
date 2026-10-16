<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Action\Flag;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;
use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\AnyMatcher;
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
    private function mailbox(int $messages = 1): InMemoryMailbox
    {
        $mailbox = new InMemoryMailbox();
        for ($i = 0; $i < $messages; ++$i) {
            $mailbox->deliver('INBOX');
        }

        return $mailbox;
    }

    public function testPreviewReturnsSingleRuleMatchWithoutExecuting(): void
    {
        $mailbox = $this->mailbox();

        $rule = new Rule(
            name: 'Test Rule',
            matcher: new AnyMatcher(),
            then: static fn () => yield new MoveToFolder('Folder')
        );

        $results = (new Rules($mailbox, [$rule]))->preview();

        $this->assertCount(1, $results);
        $this->assertInstanceOf(MessageInterface::class, $results[0]->message);
        $this->assertSame('Test Rule', $results[0]->ruleName);
        $this->assertCount(1, $results[0]->actions);
        $this->assertInstanceOf(MoveToFolder::class, $results[0]->actions[0]);
        $this->assertSame(['INBOX'], $mailbox->folders(), 'Preview must not move anything');
    }

    public function testPreviewReturnsMultipleActionsForSingleRule(): void
    {
        $rule = new Rule(
            name: 'Multi-Action Rule',
            matcher: new AnyMatcher(),
            then: static function () {
                yield new MoveToFolder('Archive');
                yield new MarkAsRead();
                yield new Flag();
            }
        );

        $results = (new Rules($this->mailbox(), [$rule]))->preview();

        $this->assertCount(1, $results);
        $this->assertCount(3, $results[0]->actions);
        $this->assertInstanceOf(MoveToFolder::class, $results[0]->actions[0]);
        $this->assertInstanceOf(MarkAsRead::class, $results[0]->actions[1]);
        $this->assertInstanceOf(Flag::class, $results[0]->actions[2]);
    }

    public function testPreviewReturnsMultipleRulesForSingleMessage(): void
    {
        $rule1 = new Rule('Rule 1', new AnyMatcher(), static fn () => yield new MoveToFolder('Folder1'));
        $rule2 = new Rule('Rule 2', new AnyMatcher(), static fn () => yield new MarkAsRead());

        $results = (new Rules($this->mailbox(), [$rule1, $rule2]))->preview();

        $this->assertCount(2, $results);
        $this->assertSame('Rule 1', $results[0]->ruleName);
        $this->assertSame('Rule 2', $results[1]->ruleName);
    }

    public function testPreviewSkipsNonMatchingRules(): void
    {
        $matcher = $this->createStub(Matcher::class);
        $matcher->method('matches')->willReturn(false);

        $rule = new Rule('Non-matching Rule', $matcher, static fn () => yield new MoveToFolder('Folder'));

        $this->assertCount(0, (new Rules($this->mailbox(), [$rule]))->preview());
    }

    public function testPreviewHandlesMultipleMessages(): void
    {
        $rule = new Rule('Test Rule', new AnyMatcher(), static fn () => yield new Flag());

        $results = (new Rules($this->mailbox(2), [$rule]))->preview();

        $this->assertCount(2, $results);
    }
}
