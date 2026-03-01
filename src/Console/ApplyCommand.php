<?php

declare(strict_types=1);

namespace MailboxRules\Console;

use MailboxRules\Loader\RuleFileLoader;
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

        $rules = $this->ruleFileLoader->load($config);

        if ($input->getOption("dry-run")) {
            $results = $rules->preview();

            if (count($results) === 0) {
                $output->writeln("<info>No actions to execute</info>");
                return Command::SUCCESS;
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

            return Command::SUCCESS;
        }

        $rules->apply();

        return Command::SUCCESS;
    }
}
