<?php

declare(strict_types=1);

namespace Tests\Console;

use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Console\ApplyCommand;
use MailboxRules\Loader\RuleFileLoader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tests\Support\InMemoryMailboxFactory;

use function sys_get_temp_dir;
use function unlink;

#[CoversClass(ApplyCommand::class)]
class ApplyCommandTest extends TestCase
{
    private string $tempFile = '';

    protected function tearDown(): void
    {
        if ($this->tempFile !== '' && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }
    }

    /**
     * @param list<?string> $subjects
     * @return array{tempFile: string, command: ApplyCommand}
     */
    private function createTempRulesFileAndCommand(array $subjects): array
    {
        $mailbox = new InMemoryMailbox();
        foreach ($subjects as $subject) {
            $mailbox->deliver('INBOX', subject: $subject);
        }
        InMemoryMailboxFactory::$mailbox = $mailbox;

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
    matcher: new \MailboxRules\Matcher\AnyMatcher(),
    then: static fn () => yield new MoveToFolder('Archive')
);

return new MailboxConfiguration(
    dsn: Dsn::fromString('imap://user:pass@localhost:993/INBOX'),
    rules: [$rule]
);
PHP
        );

        $factoryInstance = new InMemoryMailboxFactory();

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
        ['tempFile' => $this->tempFile, 'command' => $applyCommand] = $this->createTempRulesFileAndCommand(['Test Subject']);

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
        ['tempFile' => $this->tempFile, 'command' => $applyCommand] = $this->createTempRulesFileAndCommand([null]);

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
