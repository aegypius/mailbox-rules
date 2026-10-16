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
use function tempnam;
use function unlink;

#[CoversClass(ApplyCommand::class)]
final class PreviewLimitCommandTest extends TestCase
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

    public function testPreviewLimitOptionIsDefinedOnTheCommand(): void
    {
        $command = new ApplyCommand();
        $this->assertTrue($command->getDefinition()->hasOption('preview-limit'));
    }

    public function testPreviewLimitOptionDefaultsTo10(): void
    {
        $command = new ApplyCommand();
        $option = $command->getDefinition()->getOption('preview-limit');
        $this->assertSame('10', $option->getDefault());
    }

    private function previewCount(string $limit): int
    {
        $mailbox = new InMemoryMailbox();
        for ($i = 0; $i < 30; ++$i) {
            $mailbox->deliver('INBOX', subject: 'm' . $i);
        }
        InMemoryMailboxFactory::$mailbox = $mailbox;

        $this->tempFile = $this->createTempRulesFile();
        $tester = new CommandTester(new ApplyCommand(new RuleFileLoader(), new InMemoryMailboxFactory()));
        $tester->execute([
            'config' => $this->tempFile,
            '--dry-run' => true,
            '--preview-limit' => $limit,
        ]);

        $this->assertSame(0, $tester->getStatusCode());

        return substr_count($tester->getDisplay(), 'Rule: Test Rule');
    }

    public function testDryRunUsesCustomPreviewLimit(): void
    {
        $this->assertSame(25, $this->previewCount('25'));
    }

    public function testDryRunWithZeroLimitFetchesAllMessages(): void
    {
        $this->assertSame(30, $this->previewCount('0'));
    }
}
