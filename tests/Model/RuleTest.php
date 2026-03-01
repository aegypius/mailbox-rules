<?php

declare(strict_types=1);

namespace Tests\Model;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;
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
        $matcher = new AnyMatcher();
        $action1 = $this->createStub(Action::class);
        $action2 = $this->createStub(Action::class);
        $callback = fn (Message $message) => [$action1, $action2];
        $rule = new Rule('test-rule', $matcher, $callback);
        $message = $this->createStub(Message::class);

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
            public function matches(Message $message): bool
            {
                return false;
            }
        };
        $action1 = $this->createStub(Action::class);
        $action2 = $this->createStub(Action::class);
        $callback = fn (Message $message) => [$action1, $action2];
        $rule = new Rule('test-rule', $matcher, $callback);
        $message = $this->createStub(Message::class);

        // Act
        $actions = $rule($message);

        // Assert
        $this->assertSame([], $actions);
    }

    #[Test]
    public function it_executes_legacy_rule_without_matcher(): void
    {
        // Arrange: null matcher (legacy behavior)
        $action1 = $this->createStub(Action::class);
        $action2 = $this->createStub(Action::class);
        $callback = fn (Message $message) => [$action1, $action2];
        $rule = new Rule('legacy-rule', null, $callback);
        $message = $this->createStub(Message::class);

        // Act
        $actions = $rule($message);

        // Assert
        $this->assertSame([$action1, $action2], $actions);
    }

    #[Test]
    public function it_evaluates_matcher_before_executing_callback(): void
    {
        // Arrange: matcher that doesn't match
        $matcher = new class() implements Matcher {
            public function matches(Message $message): bool
            {
                return false;
            }
        };
        $callbackExecuted = false;
        $action = $this->createStub(Action::class);
        $callback = function (Message $message) use (&$callbackExecuted, $action) {
            $callbackExecuted = true;
            return [$action];
        };
        $rule = new Rule('test-rule', $matcher, $callback);
        $message = $this->createStub(Message::class);

        // Act
        $rule($message);

        // Assert: callback should NOT be executed when matcher fails
        $this->assertFalse($callbackExecuted, 'Callback should not execute when matcher does not match');
    }
}
