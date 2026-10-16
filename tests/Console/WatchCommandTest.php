<?php

declare(strict_types=1);

namespace Tests\Console;

use MailboxRules\Adapter\InMemoryMailbox;
use MailboxRules\Console\ApplyCommand;
use MailboxRules\Contract\MailboxInterface;
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

    public function testWatchCoversEveryMailbox(): void
    {
        $factory = new class() implements MailboxFactoryInterface {
            /**
             * @var array<string, InMemoryMailbox>
             */
            public static array $boxes = [];

            public static function createMailbox(Dsn $dsn): MailboxInterface
            {
                return self::$boxes[$dsn->user];
            }
        };
        $one = new InMemoryMailbox();
        $two = new InMemoryMailbox();
        $factory::$boxes = [
            'u1' => $one,
            'u2' => $two,
        ];
        $messageOne = $one->deliver('INBOX');
        $messageTwo = $two->deliver('INBOX');

        $this->tempFile = tempnam(sys_get_temp_dir(), 'rules_') . '.php';
        file_put_contents($this->tempFile, <<<'PHP'
<?php
use MailboxRules\Action\Flag;
use function MailboxRules\any;
use function MailboxRules\mailbox;
use function MailboxRules\mailboxes;
use function MailboxRules\rule;

return mailboxes(
    mailbox('imap://u1:p@localhost:993/INBOX', [rule(name: 'R', when: any(), then: static fn () => [new Flag()])]),
    mailbox('imap://u2:p@localhost:993/INBOX', [rule(name: 'R', when: any(), then: static fn () => [new Flag()])]),
);
PHP);

        $tester = new CommandTester(new ApplyCommand(null, $factory, 1));
        $tester->execute([
            'config' => $this->tempFile,
            '--watch' => true,
            '--interval' => '0',
        ]);

        $this->assertTrue($messageOne->isFlagged());
        $this->assertTrue($messageTwo->isFlagged());
    }
}
