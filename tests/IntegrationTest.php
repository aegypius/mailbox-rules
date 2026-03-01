<?php

declare(strict_types=1);

namespace Tests;

use DirectoryTree\ImapEngine\Address;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\Flag;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use function MailboxRules\allOf;
use function MailboxRules\anyOf;
use function MailboxRules\chain;
use function MailboxRules\from;
use function MailboxRules\not;
use function MailboxRules\rule;
use function MailboxRules\subject;
use function MailboxRules\to;

#[CoversClass(\MailboxRules\Model\Rule::class)]
final class IntegrationTest extends TestCase
{
    public function testCompleteRuleWithFromMatcherAndMoveAction(): void
    {
        // Create a rule that moves messages from chaosium.com to Promotions
        $rule = rule(
            name: 'Chaosium Promotions',
            when: from('*@chaosium.com'),
            then: static fn (Message $message) => yield new MoveToFolder('Promotions')
        );

        $message = $this->createMock(Message::class);
        $sender = $this->createStub(Address::class);
        $sender->method('email')->willReturn('test@chaosium.com');
        $message->method('from')->willReturn($sender);
        $message->expects($this->once())
            ->method('move')
            ->with('Promotions', false);

        $actions = $rule($message);
        foreach ($actions as $action) {
            $action($message); // @phpstan-ignore callable.nonCallable
        }
    }

    public function testMultipleRulesWithDifferentMatchers(): void
    {
        // Rule 1: Mark newsletters as read
        $rule1 = rule(
            name: 'Mark Newsletters Read',
            when: subject('*[Newsletter]*'),
            then: static fn (Message $message) => yield new MarkAsRead()
        );

        // Rule 2: Flag important messages
        $rule2 = rule(
            name: 'Flag Important',
            when: subject('*[IMPORTANT]*'),
            then: static fn (Message $message) => yield new Flag()
        );

        // Test rule 1
        $message1 = $this->createMock(Message::class);
        $message1->method('subject')->willReturn('[Newsletter] Weekly Update');
        $message1->expects($this->once())->method('markRead');

        $actions = $rule1($message1);
        foreach ($actions as $action) {
            $action($message1); // @phpstan-ignore callable.nonCallable
        }

        // Test rule 2
        $message2 = $this->createMock(Message::class);
        $message2->method('subject')->willReturn('[IMPORTANT] Urgent Action Required');
        $message2->expects($this->once())->method('markFlagged');

        $actions = $rule2($message2);
        foreach ($actions as $action) {
            $action($message2); // @phpstan-ignore callable.nonCallable
        }
    }

    public function testComplexMatcherCombinations(): void
    {
        // Rule: Move spam from specific domains but not from known contacts
        $rule = rule(
            name: 'Filter Spam',
            when: allOf(
                anyOf(
                    from('*@spam.com'),
                    from('*@junk.net')
                ),
                not(subject('*Order Confirmation*'))
            ),
            then: static fn (Message $message) => yield new MoveToFolder('Spam')
        );

        // Should match: spam domain, no order confirmation
        $spamMessage = $this->createMock(Message::class);
        $spamSender = $this->createStub(Address::class);
        $spamSender->method('email')->willReturn('bad@spam.com');
        $spamMessage->method('from')->willReturn($spamSender);
        $spamMessage->method('subject')->willReturn('Get rich quick!');
        $spamMessage->expects($this->once())
            ->method('move')
            ->with('Spam', false);

        $actions = $rule($spamMessage);
        foreach ($actions as $action) {
            $action($spamMessage); // @phpstan-ignore callable.nonCallable
        }

        // Should not match: spam domain but has order confirmation
        $orderMessage = $this->createMock(Message::class);
        $orderSender = $this->createStub(Address::class);
        $orderSender->method('email')->willReturn('orders@spam.com');
        $orderMessage->method('from')->willReturn($orderSender);
        $orderMessage->method('subject')->willReturn('Order Confirmation #12345');
        $orderMessage->expects($this->never())->method('move');

        $actions = $rule($orderMessage);
        $this->assertEmpty(iterator_to_array($actions));
    }

    public function testActionsExecuteInOrder(): void
    {
        // Rule: Multiple actions executed in sequence
        $rule = rule(
            name: 'Process Important',
            when: subject('*[Important]*'),
            then: static fn (Message $message) => chain(
                new MoveToFolder('Important'),
                new MarkAsRead(),
                new Flag()
            )
        );

        $message = $this->createMock(Message::class);
        $message->method('subject')->willReturn('[Important] Meeting Notes');

        // Set up expectations for each action
        $message->expects($this->once())->method('move')->with('Important', false);
        $message->expects($this->once())->method('markRead');
        $message->expects($this->once())->method('markFlagged');

        $actions = $rule($message);
        foreach ($actions as $action) {
            $action($message); // @phpstan-ignore callable.nonCallable
        }
    }

    public function testRuleWithToMatcher(): void
    {
        // Rule: Flag messages sent to support
        $rule = rule(
            name: 'Flag Support Emails',
            when: to('support@example.com'),
            then: static fn (Message $message) => yield new Flag()
        );

        $message = $this->createMock(Message::class);
        $recipient = $this->createStub(Address::class);
        $recipient->method('email')->willReturn('support@example.com');
        $message->method('to')->willReturn([$recipient]);
        $message->expects($this->once())->method('markFlagged');

        $actions = $rule($message);
        foreach ($actions as $action) {
            $action($message); // @phpstan-ignore callable.nonCallable
        }
    }

    public function testRuleDoesNotMatchSkipsActions(): void
    {
        // Rule with non-matching condition
        $rule = rule(
            name: 'Archive Old',
            when: from('old@example.com'),
            then: static fn (Message $message) => yield new MoveToFolder('Archive')
        );

        $message = $this->createMock(Message::class);
        $sender = $this->createStub(Address::class);
        $sender->method('email')->willReturn('new@example.com');
        $message->method('from')->willReturn($sender);
        $message->expects($this->never())->method('move');

        $actions = $rule($message);
        $this->assertEmpty(iterator_to_array($actions));
    }

    public function testNestedCombinators(): void
    {
        // Rule: Complex nested logic
        $rule = rule(
            name: 'Complex Filter',
            when: anyOf(
                allOf(
                    from('*@company.com'),
                    subject('*[Team]*')
                ),
                allOf(
                    to('*@team.example.com'),
                    not(subject('*Out of Office*'))
                )
            ),
            then: static fn (Message $message) => yield new MoveToFolder('Team')
        );

        // Should match: company email with [Team] subject
        $message1 = $this->createMock(Message::class);
        $sender1 = $this->createStub(Address::class);
        $sender1->method('email')->willReturn('alice@company.com');
        $message1->method('from')->willReturn($sender1);
        $message1->method('subject')->willReturn('[Team] Project Update');
        $message1->expects($this->once())->method('move')->with('Team', false);

        $actions = $rule($message1);
        foreach ($actions as $action) {
            $action($message1); // @phpstan-ignore callable.nonCallable
        }

        // Should match: to team domain, not out of office
        $message2 = $this->createMock(Message::class);
        $sender2 = $this->createStub(Address::class);
        $sender2->method('email')->willReturn('external@other.com');
        $recipient2 = $this->createStub(Address::class);
        $recipient2->method('email')->willReturn('dev@team.example.com');
        $message2->method('from')->willReturn($sender2);
        $message2->method('to')->willReturn([$recipient2]);
        $message2->method('subject')->willReturn('Question about API');
        $message2->expects($this->once())->method('move')->with('Team', false);

        $actions = $rule($message2);
        foreach ($actions as $action) {
            $action($message2); // @phpstan-ignore callable.nonCallable
        }
    }
}
