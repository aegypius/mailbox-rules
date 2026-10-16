<?php

declare(strict_types=1);

namespace Tests;

use MailboxRules\Action;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Model\Rule;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\any;
use function MailboxRules\rule;

#[CoversFunction('MailboxRules\rule')]
final class RuleFunctionTest extends TestCase
{
    public function testRuleWithCallbackSignature(): void
    {
        $rule = rule(
            name: "Test Rule",
            then: static fn (MessageInterface $message): iterable => []
        );

        $this->assertInstanceOf(Rule::class, $rule);
        $this->assertSame("Test Rule", $rule->name);
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
}
