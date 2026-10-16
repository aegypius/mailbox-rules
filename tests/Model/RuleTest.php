<?php

declare(strict_types=1);

namespace Tests\Model;

use MailboxRules\Action;
use MailboxRules\Action\LogAction;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\Matcher;
use MailboxRules\Model\Rule;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Rule::class)]
final class RuleTest extends TestCase
{
    #[Test]
    public function it_executes_actions_when_matcher_matches(): void
    {
        // Arrange: matcher that always matches
        $anyMatcher = new AnyMatcher();
        $action1 = $this->createStub(Action::class);
        $action2 = $this->createStub(Action::class);
        $callback = fn (MessageInterface $message): array => [$action1, $action2];
        $rule = new Rule('test-rule', $anyMatcher, $callback);
        $message = $this->createStub(MessageInterface::class);

        // Act
        $actions = $rule($message);

        // Assert
        $this->assertSame([$action1, $action2], $actions);
    }

    #[Test]
    public function it_returns_empty_when_matcher_does_not_match(): void
    {
        // Arrange: matcher that never matches
        $matcher = new class() implements Matcher {
            use \MailboxRules\Matcher\UnscopedMatcher;

            public function matches(MessageInterface $message): bool
            {
                return false;
            }
        };
        $action1 = $this->createStub(Action::class);
        $action2 = $this->createStub(Action::class);
        $callback = fn (MessageInterface $message): array => [$action1, $action2];
        $rule = new Rule('test-rule', $matcher, $callback);
        $message = $this->createStub(MessageInterface::class);

        // Act
        $actions = $rule($message);

        // Assert
        $this->assertSame([], $actions);
    }

    #[Test]
    public function it_evaluates_matcher_before_executing_callback(): void
    {
        // Arrange: matcher that doesn't match
        $matcher = new class() implements Matcher {
            use \MailboxRules\Matcher\UnscopedMatcher;

            public function matches(MessageInterface $message): bool
            {
                return false;
            }
        };
        $callbackExecuted = false;
        $action = $this->createStub(Action::class);
        $callback = function (MessageInterface $message) use (&$callbackExecuted, $action): array {
            $callbackExecuted = true;
            return [$action];
        };
        $rule = new Rule('test-rule', $matcher, $callback);
        $message = $this->createStub(MessageInterface::class);

        // Act
        $rule($message);

        // Assert: callback should NOT be executed when matcher fails
        $this->assertFalse($callbackExecuted, 'Callback should not execute when matcher does not match');
    }

    #[Test]
    public function it_replays_the_same_actions_when_the_closure_yields(): void
    {
        $rule = new Rule('r', new AnyMatcher(), static fn (): \Generator => yield new LogAction());
        $message = $this->createStub(MessageInterface::class);

        $this->assertCount(1, $rule($message));
        $this->assertCount(1, $rule($message));
    }

    #[Test]
    public function it_returns_a_list_of_actions(): void
    {
        $rule = new Rule('r', new AnyMatcher(), static fn (): \Generator => yield 'k' => new LogAction());

        $this->assertSame([0], array_keys($rule($this->createStub(MessageInterface::class))));
    }
}
