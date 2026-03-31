<?php

declare(strict_types=1);

namespace Tests\Console;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\MessageQuery;
use MailboxRules\Console\ApplyCommand;
use MailboxRules\Loader\RuleFileLoader;
use MailboxRules\MailboxFactoryInterface;
use MailboxRules\ValueObject\Dsn;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function sys_get_temp_dir;
use function unlink;

#[CoversClass(ApplyCommand::class)]
#[AllowMockObjectsWithoutExpectations]
class ApplyCommandTest extends TestCase
{
    private string $tempFile = '';
    public static Mailbox $mockMailbox;

    protected function tearDown(): void
    {
        if ($this->tempFile !== '' && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    /**
     * @param list<Message> $messages
     * @return array{tempFile: string, command: ApplyCommand}
     */
    private function createTempRulesFileAndCommand(array $messages): array
    {
        $mailbox = $this->createMock(Mailbox::class);
        $folder = $this->createMock(Folder::class);
        $query = $this->createMock(MessageQuery::class);

        $mailbox->expects($this->once())->method('connect');
        $mailbox->expects($this->once())->method('inbox')->willReturn($folder);
        $folder->expects($this->once())->method('messages')->willReturn($query);
        $query->expects($this->once())->method('withHeaders')->willReturn($query);
        $query->expects($this->once())->method('limit')->with(10)->willReturn($query);
        $query->expects($this->once())->method('get')->willReturn(new MessageCollection($messages));

        $tempFile = tempnam(sys_get_temp_dir(), 'rules_') . '.php';
        file_put_contents(
            $tempFile,
            <<<'PHP'
<?php
use MailboxRules\Action\MoveToFolder;
use MailboxRules\Model\Rule;
use MailboxRules\ValueObject\Dsn;
use MailboxRules\ValueObject\MailboxConfiguration;

$rule = new Rule(
    name: 'Test Rule',
    matcher: null,
    then: static fn () => yield new MoveToFolder('Archive')
);

return new MailboxConfiguration(
    dsn: Dsn::fromString('imap://user:pass@localhost:993/INBOX'),
    rules: [$rule]
);
PHP
        );

        // Store mock mailbox in static property for factory to return
        self::$mockMailbox = $mailbox;

        // Create anonymous class that implements the interface
        $factoryInstance = new class() implements MailboxFactoryInterface {
            public static function createMailbox(Dsn $dsn): Mailbox
            {
                return ApplyCommandTest::$mockMailbox;
            }
        };

        // Create command with mock factory instance
        $command = new ApplyCommand(new RuleFileLoader(), $factoryInstance);

        return [
            'tempFile' => $tempFile,
            'command' => $command,
        ];
    }

    public function testDryRunDisplaysNoActionsWhenNoMessages(): void
    {
        ['tempFile' => $this->tempFile, 'command' => $applyCommand] = $this->createTempRulesFileAndCommand([]);

        $commandTester = new CommandTester($applyCommand);

        $commandTester->execute([
            'config' => $this->tempFile,
            '--dry-run' => true,
        ]);

        $this->assertStringContainsString('No actions to execute', $commandTester->getDisplay());
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testDryRunDisplaysPreviewResults(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('subject')->willReturn('Test Subject');

        ['tempFile' => $this->tempFile, 'command' => $applyCommand] = $this->createTempRulesFileAndCommand([$message]);

        $commandTester = new CommandTester($applyCommand);

        $commandTester->execute([
            'config' => $this->tempFile,
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Rule: Test Rule', $output);
        $this->assertStringContainsString('Message: Test Subject', $output);
        $this->assertStringContainsString('Actions:', $output);
        $this->assertStringContainsString(\MailboxRules\Action\MoveToFolder::class, $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }

    public function testDryRunHandlesNoSubject(): void
    {
        $message = $this->createStub(Message::class);
        $message->method('subject')->willReturn(null);

        ['tempFile' => $this->tempFile, 'command' => $applyCommand] = $this->createTempRulesFileAndCommand([$message]);

        $commandTester = new CommandTester($applyCommand);

        $commandTester->execute([
            'config' => $this->tempFile,
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Message: (no subject)', $output);
        $this->assertSame(0, $commandTester->getStatusCode());
    }
}
