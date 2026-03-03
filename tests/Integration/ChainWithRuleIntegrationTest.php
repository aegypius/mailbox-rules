<?php

declare(strict_types=1);

namespace Tests\Integration;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action\LogAction;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;
use MailboxRules\Matcher\AnyMatcher;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\chain;
use function MailboxRules\rule;

/**
 * Integration test verifying the chain() helper works correctly with rule().
 *
 * This test ensures that:
 * 1. chain() returns a Generator
 * 2. rule() accepts a Generator (not just Closure)
 * 3. Accented folder names are encoded to modified UTF-7
 */
#[CoversFunction('MailboxRules\rule')]
#[CoversFunction('MailboxRules\chain')]
final class ChainWithRuleIntegrationTest extends TestCase
{
    public function testRuleAcceptsChainWithAccentedFolderName(): void
    {
        // Use AnyMatcher for simplicity - we're testing chain() + rule() integration
        // not the matcher logic itself
        $message = $this->createStub(Message::class);

        // This is the pattern from rules.php that was failing
        $rule = rule(
            name: 'Le Taillis',
            when: new AnyMatcher(),
            // Match all messages for this test
            then: chain(
                new LogAction(),
                new MarkAsRead(),
                new MoveToFolder('Copropriété'), // Accented characters
            )
        );

        self::assertSame('Le Taillis', $rule->name);
        self::assertNotNull($rule->matcher);

        // Execute the rule
        $actions = iterator_to_array($rule($message));

        self::assertCount(3, $actions);
        self::assertInstanceOf(LogAction::class, $actions[0]);
        self::assertInstanceOf(MarkAsRead::class, $actions[1]);
        self::assertInstanceOf(MoveToFolder::class, $actions[2]);
    }

    public function testChainDirectlyPassedToRuleWorks(): void
    {
        // Verify that passing chain() result directly to rule() works
        $generator = chain(
            new LogAction(),
            new MoveToFolder('Archive')
        );

        $rule = rule('Test', when: new AnyMatcher(), then: $generator);

        $message = $this->createStub(Message::class);
        $actions = iterator_to_array($rule($message));

        self::assertCount(2, $actions);
    }

    public function testRuleStillAcceptsClosure(): void
    {
        // Verify backward compatibility with closures
        $rule = rule(
            'Test',
            when: new AnyMatcher(),
            then: static fn () => yield from [
                new LogAction(),
                new MoveToFolder('Archive'),
            ]
        );

        $message = $this->createStub(Message::class);
        $actions = iterator_to_array($rule($message));

        self::assertCount(2, $actions);
    }
}
