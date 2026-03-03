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
        $moveToFolder = new MoveToFolder('Archive');
        $markAsRead = new MarkAsRead();

        $result = chain($moveToFolder, $markAsRead);
        $actions = iterator_to_array($result);

        $this->assertCount(2, $actions);
    }

    public function testYieldsActionsInOrder(): void
    {
        $moveToFolder = new MoveToFolder('Archive');
        $markAsRead = new MarkAsRead();
        $flag = new Flag();

        $result = chain($moveToFolder, $markAsRead, $flag);
        $actions = iterator_to_array($result);

        $this->assertCount(3, $actions);
        $this->assertSame($moveToFolder, $actions[0]);
        $this->assertSame($markAsRead, $actions[1]);
        $this->assertSame($flag, $actions[2]);
    }

    public function testWorksWithSingleAction(): void
    {
        $moveToFolder = new MoveToFolder('Spam');

        $result = chain($moveToFolder);
        $actions = iterator_to_array($result);

        $this->assertCount(1, $actions);
        $this->assertSame($moveToFolder, $actions[0]);
    }

    public function testWorksWithNoActions(): void
    {
        $result = chain();
        $actions = iterator_to_array($result);

        $this->assertCount(0, $actions);
    }

    public function testReturnsGenerator(): void
    {
        $moveToFolder = new MoveToFolder('Archive');
        $markAsRead = new MarkAsRead();

        $result = chain($moveToFolder, $markAsRead);

        $this->assertInstanceOf(\Generator::class, $result);
    }

    public function testCanBeIteratedMultipleTimes(): void
    {
        $moveToFolder = new MoveToFolder('Archive');
        $markAsRead = new MarkAsRead();

        // Generator can only be iterated once, so we need to call chain() twice
        $result1 = chain($moveToFolder, $markAsRead);
        $actions1 = iterator_to_array($result1);

        $result2 = chain($moveToFolder, $markAsRead);
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
