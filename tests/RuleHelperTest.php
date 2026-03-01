<?php

declare(strict_types=1);

namespace Tests;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\LogAction;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;
use MailboxRules\Matcher\AnyMatcher;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\chain;
use function MailboxRules\rule;

#[CoversFunction('MailboxRules\rule')]
final class RuleHelperTest extends TestCase
{
    public function testRuleAcceptsChainDirectly(): void
    {
        $matcher = new AnyMatcher();
        $actions = chain(
            new LogAction(),
            new MarkAsRead(),
            new MoveToFolder('Archive')
        );

        $rule = rule('Test Rule', when: $matcher, then: $actions);

        self::assertSame('Test Rule', $rule->name);
        self::assertSame($matcher, $rule->matcher);
    }

    public function testRuleWithChainExecutesActions(): void
    {
        $matcher = new AnyMatcher();

        $rule = rule(
            'Test Rule',
            when: $matcher,
            then: chain(
                new LogAction(),
                new MarkAsRead(),
                new MoveToFolder('Archive')
            )
        );

        $message = $this->createStub(Message::class);
        $actions = iterator_to_array($rule($message));

        self::assertCount(3, $actions);
        self::assertInstanceOf(LogAction::class, $actions[0]);
        self::assertInstanceOf(MarkAsRead::class, $actions[1]);
        self::assertInstanceOf(MoveToFolder::class, $actions[2]);
    }

    public function testRuleWithClosureStillWorks(): void
    {
        $matcher = new AnyMatcher();

        $rule = rule(
            'Test Rule',
            when: $matcher,
            then: static fn () => yield from [
                new LogAction(),
                new MarkAsRead(),
            ]
        );

        $message = $this->createStub(Message::class);
        $actions = iterator_to_array($rule($message));

        self::assertCount(2, $actions);
        self::assertInstanceOf(LogAction::class, $actions[0]);
        self::assertInstanceOf(MarkAsRead::class, $actions[1]);
    }
}
