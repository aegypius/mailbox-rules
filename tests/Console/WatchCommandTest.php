<?php

declare(strict_types=1);

namespace Tests\Console;

use MailboxRules\Console\ApplyCommand;
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
    matcher: new \MailboxRules\Matcher\AnyMatcher(),
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

    public function testIntervalDefaultsToSixtySeconds(): void
    {
        $option = (new ApplyCommand())->getDefinition()->getOption('interval');

        $this->assertSame('60', $option->getDefault());
    }
}
