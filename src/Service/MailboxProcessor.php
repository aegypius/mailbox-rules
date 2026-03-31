<?php

declare(strict_types=1);

namespace MailboxRules\Service;

use MailboxRules\MailboxFactory;
use MailboxRules\Model\Rules;
use MailboxRules\ValueObject\MailboxConfiguration;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Processes a single mailbox configuration by applying its rules.
 *
 * This service separates the execution logic from configuration,
 * enabling better testability and parallel processing.
 */
final readonly class MailboxProcessor
{
    public function __construct(
        private MailboxFactory $mailboxFactory,
        private LoggerInterface $logger = new NullLogger(),
    ) {
    }

    /**
     * Process a mailbox by applying all its rules.
     *
     * @param MailboxConfiguration $config The mailbox configuration to process
     */
    public function process(MailboxConfiguration $config): void
    {
        if ($config->name !== null) {
            $this->logger->info('Processing mailbox: {mailbox}', [
                'mailbox' => $config->name,
            ]);
        }

        $mailbox = $this->mailboxFactory->createMailbox($config->dsn);
        $rules = new Rules($mailbox, $config->rules, $this->logger);
        $rules->apply();
    }

    /**
     * Preview actions for a mailbox without executing them.
     *
     * @param MailboxConfiguration $config The mailbox configuration to preview
     * @return list<\MailboxRules\Model\PreviewResult> List of preview results
     */
    public function preview(MailboxConfiguration $config): array
    {
        if ($config->name !== null) {
            $this->logger->info('Previewing mailbox: {mailbox}', [
                'mailbox' => $config->name,
            ]);
        }

        $mailbox = $this->mailboxFactory->createMailbox($config->dsn);
        $rules = new Rules($mailbox, $config->rules, $this->logger);
        return $rules->preview();
    }
}
