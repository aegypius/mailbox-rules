<?php

declare(strict_types=1);

namespace Tests;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;
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
            then: static fn (Message $message): iterable => []
        );

        $this->assertInstanceOf(Rule::class, $rule);
        $this->assertSame("Test Rule", $rule->name);
    }

    public function testRuleWithMatcherSignature(): void
    {
        $matcher = any();
        $then = static fn (Message $message): iterable => [];

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
                public function __invoke(Message $message): void
                {
                }
            },
        ];

        $rule = rule(
            name: "Test Rule",
            when: any(),
            then: static fn (Message $message): iterable => $actions
        );

        $message = $this->createStub(Message::class);
        $result = ($rule)($message);

        $this->assertSame($actions, iterator_to_array($result));
    }
}
