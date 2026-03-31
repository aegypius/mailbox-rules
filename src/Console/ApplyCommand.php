<?php

declare(strict_types=1);

namespace MailboxRules\Console;

use MailboxRules\Loader\RuleFileLoader;
use MailboxRules\MailboxFactory;
use MailboxRules\Service\MailboxProcessor;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: "apply")]
final class ApplyCommand extends Command
{
    private readonly RuleFileLoader $ruleFileLoader;

    public function __construct(RuleFileLoader|null $ruleFileLoader = null)
    {
        parent::__construct();
        $this->ruleFileLoader = $ruleFileLoader ?? new RuleFileLoader();
    }

    protected function configure(): void
    {
        $this->setDescription("Apply the mailbox rules")
            ->setHelp(
                "This command allows you to apply the mailbox rules defined in the configuration file."
            )
            ->addArgument(
                "config",
                InputArgument::OPTIONAL,
                "The configuration file to load rules from",
                "./rules.php"
            )
            ->addOption(
                "dry-run",
                null,
                InputOption::VALUE_NONE,
                "Preview actions without executing them"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $input->getArgument("config");
        assert(is_string($config));

        $configurations = $this->ruleFileLoader->load($config);

        // Create shared logger
        $handler = new StreamHandler("php://stdout", Level::Info);
        $handler->setFormatter(new LineFormatter(
            format: "%datetime% [%level_name%] %message% %context% %extra%\n",
            dateFormat: "Y-m-d H:i:s",
            allowInlineLineBreaks: true,
            ignoreEmptyContextAndExtra: true
        ));

        $logger = new Logger(
            name: "app",
            handlers: [$handler],
            processors: [new PsrLogMessageProcessor(dateFormat: "Y-m-d H:i:s")]
        );

        // Create processor with shared logger and factory
        $processor = new MailboxProcessor(new MailboxFactory(), $logger);

        if ($input->getOption("dry-run")) {
            $hasResults = false;

            foreach ($configurations as $mailboxConfig) {
                $results = $processor->preview($mailboxConfig);

                if ($results === []) {
                    continue;
                }

                $hasResults = true;

                if ($mailboxConfig->name !== null) {
                    $output->writeln(sprintf("<info>Mailbox: %s</info>", $mailboxConfig->name));
                }

                foreach ($results as $result) {
                    $output->writeln(sprintf(
                        "<comment>Rule:</comment> %s",
                        $result->ruleName
                    ));
                    $output->writeln(sprintf(
                        "<comment>Message:</comment> %s",
                        $result->message->subject() ?? '(no subject)'
                    ));
                    $output->writeln("<comment>Actions:</comment>");
                    foreach ($result->actions as $action) {
                        $output->writeln(sprintf("  - %s", $action::class));
                    }

                    $output->writeln("");
                }
            }

            if (!$hasResults) {
                $output->writeln("<info>No actions to execute</info>");
            }

            return Command::SUCCESS;
        }

        foreach ($configurations as $mailboxConfig) {
            $processor->process($mailboxConfig);
        }

        return Command::SUCCESS;
    }
}
