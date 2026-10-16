<?php

declare(strict_types=1);

namespace Tests\Console;

use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\Mailbox;
use MailboxRules\Console\ApplyCommand;
use MailboxRules\Loader\RuleFileLoader;
use MailboxRules\MailboxFactoryInterface;
use MailboxRules\ValueObject\Dsn;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function sys_get_temp_dir;
use function tempnam;
use function unlink;

#[CoversClass(ApplyCommand::class)]
final class WatchCommandTest extends TestCase
{
    private string $tempFile = '';
    public static Mailbox $mockMailbox;

    protected function tearDown(): void
    {
        if ($this->tempFile !== '' && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    private function createTempRulesFile(): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'rules_') . '.php';
        file_put_contents(
            $tempFile,
            <<<'PHP'
<?php
use MailboxRules\Action\LogAction;
use MailboxRules\Model\Rule;
use MailboxRules\ValueObject\Dsn;
use MailboxRules\ValueObject\MailboxConfiguration;

$rule = new Rule(
    name: 'Test Rule',
    matcher: null,
    then: static fn () => yield new LogAction()
);

return new MailboxConfiguration(
    dsn: Dsn::fromString('imap://user:pass@localhost:993/INBOX'),
    rules: [$rule]
);
PHP
        );

        return $tempFile;
    }

    public function testWatchOptionIsDefinedOnTheCommand(): void
    {
        $command = new ApplyCommand();

        $this->assertTrue($command->getDefinition()->hasOption('watch'));
    }

    public function testWatchInvokesIdleOnInbox(): void
    {
        $mailbox = $this->createMock(Mailbox::class);
        $inbox = $this->createMock(Folder::class);

        $mailbox->expects($this->once())->method('connect');
        $mailbox->expects($this->once())->method('inbox')->willReturn($inbox);

        // idle() should be called (watch mode), not messages()->get()
        $inbox->expects($this->once())
            ->method('idle')
            ->with($this->isCallable());

        self::$mockMailbox = $mailbox;

        $factory = new class() implements MailboxFactoryInterface {
            public static function createMailbox(Dsn $dsn): Mailbox
            {
                return WatchCommandTest::$mockMailbox;
            }
        };

        $this->tempFile = $this->createTempRulesFile();
        $command = new ApplyCommand(new RuleFileLoader(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([
            'config' => $this->tempFile,
            '--watch' => true,
        ]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testWatchAndDryRunAreIncompatible(): void
    {
        $command = new ApplyCommand();
        $tester = new CommandTester($command);

        $this->tempFile = $this->createTempRulesFile();

        $tester->execute([
            'config' => $this->tempFile,
            '--watch' => true,
            '--dry-run' => true,
        ]);

        $this->assertNotSame(0, $tester->getStatusCode());
        $this->assertStringContainsString('--watch', $tester->getDisplay());
        $this->assertStringContainsString('--dry-run', $tester->getDisplay());
    }
}
