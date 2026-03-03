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
        $logAction = new LogAction();
        $markAsRead = new MarkAsRead();
        $moveToFolder = new MoveToFolder('Archive');

        $result = chain($logAction, $markAsRead, $moveToFolder);

        $actions = iterator_to_array($result);

        self::assertCount(3, $actions);
        self::assertSame($logAction, $actions[0]);
        self::assertSame($markAsRead, $actions[1]);
        self::assertSame($moveToFolder, $actions[2]);
    }

    public function testChainWithSingleAction(): void
    {
        $logAction = new LogAction();
        $result = chain($logAction);

        $actions = iterator_to_array($result);

        self::assertCount(1, $actions);
        self::assertSame($logAction, $actions[0]);
    }

    public function testChainWithNoActions(): void
    {
        $result = chain();

        $actions = iterator_to_array($result);

        self::assertCount(0, $actions);
    }
}
