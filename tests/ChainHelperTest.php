<?php

declare(strict_types=1);

namespace Tests;

use MailboxRules\Action\LogAction;
use MailboxRules\Action\MarkAsRead;
use MailboxRules\Action\MoveToFolder;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\TestCase;

use function MailboxRules\chain;

#[CoversFunction('MailboxRules\chain')]
final class ChainHelperTest extends TestCase
{
    public function testChainReturnsGenerator(): void
    {
        $result = chain(
            new LogAction(),
            new MarkAsRead(),
            new MoveToFolder('Archive')
        );

        self::assertInstanceOf(\Generator::class, $result);
    }

    public function testChainYieldsAllActionsInOrder(): void
    {
        $action1 = new LogAction();
        $action2 = new MarkAsRead();
        $action3 = new MoveToFolder('Archive');

        $result = chain($action1, $action2, $action3);

        $actions = iterator_to_array($result);

        self::assertCount(3, $actions);
        self::assertSame($action1, $actions[0]);
        self::assertSame($action2, $actions[1]);
        self::assertSame($action3, $actions[2]);
    }

    public function testChainWithSingleAction(): void
    {
        $action = new LogAction();
        $result = chain($action);

        $actions = iterator_to_array($result);

        self::assertCount(1, $actions);
        self::assertSame($action, $actions[0]);
    }

    public function testChainWithNoActions(): void
    {
        $result = chain();

        $actions = iterator_to_array($result);

        self::assertCount(0, $actions);
    }
}
