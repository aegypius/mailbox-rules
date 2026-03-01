<?php

declare(strict_types=1);

namespace MailboxRules\Console;

use MailboxRules\Loader\RuleFileLoader;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
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
                null,
                "The configuration file to load rules from",
                "./rules.php"
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $input->getArgument("config");
        assert(is_string($config));
        $this->ruleFileLoader->load($config)->apply();

        return Command::SUCCESS;
    }
}
