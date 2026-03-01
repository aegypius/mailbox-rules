<?php

declare(strict_types=1);

namespace Tests\Model;

use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\LogAction;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\any;
use function MailboxRules\chain;
use function MailboxRules\rule;

#[CoversClass(Rule::class)]
#[CoversClass(Rules::class)]
#[CoversFunction('MailboxRules\rule')]
#[CoversFunction('MailboxRules\any')]
#[CoversFunction('MailboxRules\chain')]
final class GeneratorReuseTest extends TestCase
{
    public function testRuleWithDirectGeneratorCanBeReused(): void
    {
        // Simulate what might happen if user passes generator directly
        $actions = chain(new LogAction());

        $rules = [
            rule(
                name: "Test Rule",
                when: any(),
                then: $actions  // Generator passed directly, not wrapped in closure
            ),
        ];

        $mockMailbox = $this->createStub(Mailbox::class);
        $mockMessage1 = $this->createStub(Message::class);
        $mockMessage2 = $this->createStub(Message::class);

        // Rules should be created normally
        $ruleSystem = new \MailboxRules\Model\Rules($mockMailbox, $rules);

        // First rule invocation should work
        $rule = $rules[0];
        $result1 = $rule($mockMessage1);

        // Exhaust the generator
        $actions1 = iterator_to_array($result1, false);
        $this->assertCount(1, $actions1);

        // Second rule invocation should also work (not throw "generator already closed")
        $result2 = $rule($mockMessage2);

        // Should be able to iterate without error
        $actions2 = iterator_to_array($result2, false);
        $this->assertCount(1, $actions2);
    }

    public function testRuleWithClosureWrappedGeneratorCanBeReused(): void
    {
        // This is the recommended approach
        $rules = [
            rule(
                name: "Test Rule",
                when: any(),
                then: static fn () => chain(new LogAction())  // Fresh generator each time
            ),
        ];

        $mockMailbox = $this->createStub(Mailbox::class);
        $mockMessage1 = $this->createStub(Message::class);
        $mockMessage2 = $this->createStub(Message::class);

        $rule = $rules[0];

        // First invocation
        $result1 = $rule($mockMessage1);
        iterator_to_array($result1, false);

        // Second invocation - should get fresh generator
        $result2 = $rule($mockMessage2);
        $actions = iterator_to_array($result2, false);
        $this->assertCount(1, $actions);
    }

    public function testRuleWithClosureReturningPremadeGeneratorFixedAutomatically(): void
    {
        // This pattern used to cause "closed generator" warnings
        // but now is fixed automatically by materializing the generator
        $premadeGenerator = chain(new LogAction());

        $rules = [
            rule(
                name: "Test Rule",
                when: any(),
                then: fn () => $premadeGenerator  // Closure captures generator, returns same instance
            ),
        ];

        $mockMessage1 = $this->createStub(Message::class);
        $mockMessage2 = $this->createStub(Message::class);

        $rule = $rules[0];

        // First invocation works - generator is materialized
        $result1 = $rule($mockMessage1);
        $actions1 = iterator_to_array($result1, false);
        $this->assertCount(1, $actions1);

        // Second invocation - should return materialized array without error
        $result2 = $rule($mockMessage2);
        $actions2 = iterator_to_array($result2, false);
        $this->assertCount(1, $actions2);
    }
}
