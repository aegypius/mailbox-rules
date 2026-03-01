<?php

declare(strict_types=1);

namespace Tests;

use MailboxRules\Action;
use MailboxRules\Action\Flag;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;
use function MailboxRules\chain;

#[CoversFunction('MailboxRules\chain')]
final class ChainFunctionTest extends TestCase
{
    public function testReturnsIterableOfActions(): void
    {
        $action1 = new MoveToFolder('Archive');
        $action2 = new MarkAsRead();

        $result = chain($action1, $action2);
        $actions = iterator_to_array($result);

        $this->assertCount(2, $actions);
    }

    public function testYieldsActionsInOrder(): void
    {
        $action1 = new MoveToFolder('Archive');
        $action2 = new MarkAsRead();
        $action3 = new Flag();

        $result = chain($action1, $action2, $action3);
        $actions = iterator_to_array($result);

        $this->assertCount(3, $actions);
        $this->assertSame($action1, $actions[0]);
        $this->assertSame($action2, $actions[1]);
        $this->assertSame($action3, $actions[2]);
    }

    public function testWorksWithSingleAction(): void
    {
        $action = new MoveToFolder('Spam');

        $result = chain($action);
        $actions = iterator_to_array($result);

        $this->assertCount(1, $actions);
        $this->assertSame($action, $actions[0]);
    }

    public function testWorksWithNoActions(): void
    {
        $result = chain();
        $actions = iterator_to_array($result);

        $this->assertCount(0, $actions);
    }

    public function testReturnsGenerator(): void
    {
        $action1 = new MoveToFolder('Archive');
        $action2 = new MarkAsRead();

        $result = chain($action1, $action2);

        $this->assertInstanceOf(\Generator::class, $result);
    }

    public function testCanBeIteratedMultipleTimes(): void
    {
        $action1 = new MoveToFolder('Archive');
        $action2 = new MarkAsRead();

        // Generator can only be iterated once, so we need to call chain() twice
        $result1 = chain($action1, $action2);
        $actions1 = iterator_to_array($result1);

        $result2 = chain($action1, $action2);
        $actions2 = iterator_to_array($result2);

        $this->assertEquals($actions1, $actions2);
    }

    public function testAcceptsActionInterface(): void
    {
        $action = $this->createStub(Action::class);

        $result = chain($action);
        $actions = iterator_to_array($result);

        $this->assertCount(1, $actions);
        $this->assertSame($action, $actions[0]);
    }
}
