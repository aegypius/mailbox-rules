<?php

declare(strict_types=1);

namespace Tests;

use MailboxRules\Action;
use MailboxRules\Action\LogAction;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Model\Rule;
use PHPUnit\Framework\Attributes\CoversFunction;

use PHPUnit\Framework\TestCase;

use function MailboxRules\any;
use function MailboxRules\chain;
use function MailboxRules\rule;

#[CoversFunction('MailboxRules\rule')]
#[CoversFunction('MailboxRules\chain')]
final class RuleFunctionTest extends TestCase
{
    public function testRuleWithoutMatcherIsRejected(): void
    {
        $this->expectException(\ArgumentCountError::class);

        // @phpstan-ignore-next-line argument.missing
        rule(name: "Test Rule", then: static fn (MessageInterface $message): iterable => []);
    }

    public function testRuleWithMatcherSignature(): void
    {
        $matcher = any();
        $then = static fn (MessageInterface $message): iterable => [];

        $rule = rule(
            name: "Test Rule",
            when: $matcher,
            then: $then
        );

        $this->assertInstanceOf(Rule::class, $rule);
        $this->assertSame("Test Rule", $rule->name);
    }

    public function testRuleWithMatcherReturnsValidRule(): void
    {
        $actions = [
            new class() implements Action {
                public function __invoke(MessageInterface $message): void
                {
                }
            },
        ];

        $rule = rule(
            name: "Test Rule",
            when: any(),
            then: static fn (MessageInterface $message): iterable => $actions
        );

        $message = $this->createStub(MessageInterface::class);
        $result = ($rule)($message);

        $this->assertSame($actions, iterator_to_array($result));
    }

    public function testDirectGeneratorIsReplayableAcrossMessages(): void
    {
        $rule = rule(name: "Test Rule", when: any(), then: chain(new LogAction()));
        $message = $this->createStub(MessageInterface::class);

        $this->assertCount(1, $rule($message));
        $this->assertCount(1, $rule($message));
    }

    public function testClosureGeneratorIsReplayableAcrossMessages(): void
    {
        $rule = rule(name: "Test Rule", when: any(), then: static fn (): \Generator => chain(new LogAction()));
        $message = $this->createStub(MessageInterface::class);

        $this->assertCount(1, $rule($message));
        $this->assertCount(1, $rule($message));
    }
}
