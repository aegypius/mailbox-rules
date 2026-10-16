<?php

declare(strict_types=1);

namespace Tests\Action;

use MailboxRules\Action\MoveToTrash;
use MailboxRules\Adapter\InMemoryMailbox;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(MoveToTrash::class)]
final class MoveToTrashTest extends TestCase
{
    public function testMovesToTheSpecialUseTrashFolder(): void
    {
        $mailbox = new InMemoryMailbox();
        $mailbox->addFolder('[Gmail]/Trash', '\\Trash');
        $message = $mailbox->deliver('INBOX');

        (new MoveToTrash())($message, $mailbox);

        self::assertSame('[Gmail]/Trash', $message->folderPath());
        self::assertSame(['INBOX', '[Gmail]/Trash'], $mailbox->folders());
    }

    public function testFailsAndCreatesNothingWhenThereIsNoTrashFolder(): void
    {
        $mailbox = new InMemoryMailbox();
        $message = $mailbox->deliver('INBOX');

        try {
            (new MoveToTrash())($message, $mailbox);
            self::fail('Expected a RuntimeException');
        } catch (\RuntimeException) {
            self::assertSame('INBOX', $message->folderPath());
            self::assertSame(['INBOX'], $mailbox->folders());
        }
    }

    public function testExplicitFolderOverridesTheSpecialUseTrash(): void
    {
        $mailbox = new InMemoryMailbox();
        $mailbox->addFolder('Bin', '\\Trash');
        $message = $mailbox->deliver('INBOX');

        (new MoveToTrash(trashFolder: 'Deleted Items'))($message, $mailbox);

        self::assertSame('Deleted Items', $message->folderPath());
    }
}
