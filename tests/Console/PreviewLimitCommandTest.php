<?php

declare(strict_types=1);

namespace Tests\Console;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\MessageQuery;
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
final class PreviewLimitCommandTest extends TestCase
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

    public function testDryRunUsesCustomPreviewLimit(): void
    {
        $mailbox = $this->createStub(Mailbox::class);
        $folder = $this->createStub(Folder::class);
        $query = $this->createMock(MessageQuery::class);

        $mailbox->method('inbox')->willReturn($folder);
        $folder->method('messages')->willReturn($query);
        $query->method('withHeaders')->willReturn($query);
        $query->expects($this->once())->method('limit')->with(25)->willReturn($query);
        $query->method('get')->willReturn(new MessageCollection([]));

        self::$mockMailbox = $mailbox;

        $factory = new class() implements MailboxFactoryInterface {
            public static function createMailbox(Dsn $dsn): Mailbox
            {
                return PreviewLimitCommandTest::$mockMailbox;
            }
        };

        $this->tempFile = $this->createTempRulesFile();
        $command = new ApplyCommand(new RuleFileLoader(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([
            'config' => $this->tempFile,
            '--dry-run' => true,
            '--preview-limit' => '25',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
    }

    public function testDryRunWithZeroLimitFetchesAllMessages(): void
    {
        $mailbox = $this->createStub(Mailbox::class);
        $folder = $this->createStub(Folder::class);
        $query = $this->createMock(MessageQuery::class);

        $mailbox->method('inbox')->willReturn($folder);
        $folder->method('messages')->willReturn($query);
        $query->method('withHeaders')->willReturn($query);
        $query->expects($this->never())->method('limit');
        $query->method('get')->willReturn(new MessageCollection([]));

        self::$mockMailbox = $mailbox;

        $factory = new class() implements MailboxFactoryInterface {
            public static function createMailbox(Dsn $dsn): Mailbox
            {
                return PreviewLimitCommandTest::$mockMailbox;
            }
        };

        $this->tempFile = $this->createTempRulesFile();
        $command = new ApplyCommand(new RuleFileLoader(), $factory);
        $tester = new CommandTester($command);

        $tester->execute([
            'config' => $this->tempFile,
            '--dry-run' => true,
            '--preview-limit' => '0',
        ]);

        $this->assertSame(0, $tester->getStatusCode());
    }
}
