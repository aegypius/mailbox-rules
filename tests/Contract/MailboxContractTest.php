<?php

declare(strict_types=1);

namespace Tests\Contract;

use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Contract\MailboxInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(InMemoryMailbox::class)]
final class MailboxContractTest extends TestCase
{
    private function mailbox(): InMemoryMailbox
    {
        $mailbox = new InMemoryMailbox('Work');
        $mailbox->deliver('INBOX', subject: 'one');
        $mailbox->deliver('INBOX', subject: 'two');
        $mailbox->deliver('Work', subject: 'three');

        return $mailbox;
    }

    #[Test]
    public function lists_every_folder_including_inbox(): void
    {
        self::assertSame(['INBOX', 'Work'], $this->mailbox()->folders());
    }

    #[Test]
    public function fetches_the_messages_of_a_folder(): void
    {
        $subjects = array_map(
            static fn ($m): ?string => $m->subject(),
            $this->mailbox()->messages('INBOX'),
        );

        self::assertSame(['one', 'two'], $subjects);
    }

    #[Test]
    public function fetches_only_messages_above_the_uid_floor(): void
    {
        $mailbox = $this->mailbox();
        $first = $mailbox->messages('INBOX')[0];

        $subjects = array_map(
            static fn ($m): ?string => $m->subject(),
            $mailbox->messages('INBOX', $first->uid()),
        );

        self::assertSame(['two'], $subjects);
    }

    #[Test]
    public function fetches_at_most_limit_messages_oldest_first(): void
    {
        $subjects = array_map(
            static fn ($m): ?string => $m->subject(),
            $this->mailbox()->messages('INBOX', limit: 1),
        );

        self::assertSame(['one'], $subjects);
    }

    #[Test]
    public function fetching_an_unknown_folder_fails(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->mailbox()->messages('Nope');
    }

    #[Test]
    public function has_no_trash_unless_a_folder_is_flagged_trash(): void
    {
        $mailbox = $this->mailbox();
        self::assertNull($mailbox->trash());

        $mailbox->addFolder('[Gmail]/Trash', '\\Trash');
        self::assertSame('[Gmail]/Trash', $mailbox->trash());
    }

    #[Test]
    public function move_creates_missing_folders_and_changes_the_messages_folder(): void
    {
        $mailbox = $this->mailbox();
        $message = $mailbox->messages('INBOX')[0];

        $message->move('A/B');

        self::assertSame(['INBOX', 'Work', 'A', 'A/B'], $mailbox->folders());
        self::assertSame('A/B', $message->folderPath());
        self::assertCount(1, $mailbox->messages('A/B'));
        self::assertCount(1, $mailbox->messages('INBOX'));
    }

    #[Test]
    public function copy_keeps_the_original(): void
    {
        $mailbox = $this->mailbox();

        $mailbox->messages('INBOX')[0]->copy('Work');

        self::assertCount(2, $mailbox->messages('INBOX'));
        self::assertCount(2, $mailbox->messages('Work'));
    }

    #[Test]
    public function is_usable_as_the_port(): void
    {
        self::assertInstanceOf(MailboxInterface::class, $this->mailbox());
    }
}
