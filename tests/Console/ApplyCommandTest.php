<?php

declare(strict_types=1);

namespace Tests\Console;

use DirectoryTree\ImapEngine\Collections\MessageCollection;
use DirectoryTree\ImapEngine\Folder;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use DirectoryTree\ImapEngine\MessageQuery;
use MailboxRules\Console\ApplyCommand;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

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
     * @param list<Message> $messages
     */
    private function createTempRulesFile(array $messages): string
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

        $hash = spl_object_hash($mailbox);
        $GLOBALS['mailbox_' . $hash] = $mailbox;

        $tempFile = tempnam(sys_get_temp_dir(), 'rules_') . '.php';
        file_put_contents(
            $tempFile,
            <<<PHP
<?php
use MailboxRules\Action\MoveToFolder;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;

\$mailbox = \$GLOBALS['mailbox_{$hash}'];
\$rule = new Rule(
    name: 'Test Rule',
    matcher: null,
    callback: static fn () => yield new MoveToFolder('Archive')
);

return new Rules(\$mailbox, [\$rule]);
PHP
        );

        return $tempFile;
    }

    public function testDryRunDisplaysNoActionsWhenNoMessages(): void
    {
        $this->tempFile = $this->createTempRulesFile([]);

        $applyCommand = new ApplyCommand();
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

        $this->tempFile = $this->createTempRulesFile([$message]);

        $applyCommand = new ApplyCommand();
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

        $this->tempFile = $this->createTempRulesFile([$message]);

        $applyCommand = new ApplyCommand();
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
