<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Adapter\ImapMessageAdapter;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Helper\FolderExtractor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Zenstruck\Callback;
use Zenstruck\Callback\Parameter;

final readonly class Rules
{
    /**
     * @param iterable<Rule> $rules
     */
    public function __construct(
        private Mailbox $mailbox,
        private iterable $rules,
        private LoggerInterface $logger = new NullLogger(),
        private StatsCollector $statsCollector = new StatsCollector(),
    ) {
    }

    /**
     * @return list<RuleExecutionStats>
     */
    public function stats(): array
    {
        return $this->statsCollector->all();
    }

    /**
     * @return list<Parameter>
     */
    private function useParameters(MessageInterface $message): array
    {
        return [
            Parameter::typed(MessageInterface::class, $message),
            Parameter::typed(LoggerInterface::class, $this->logger),
        ];
    }

    /**
     * Group rules by their target folder path.
     * Rules with AnyOfMatcher containing multiple folders will be duplicated across folders.
     *
     * @return array<string, list<Rule>>
     */
    private function groupRulesByFolder(): array
    {
        $grouped = [];
        foreach ($this->rules as $rule) {
            $folderPaths = FolderExtractor::extractAllFolderPaths($rule->matcher);
            foreach ($folderPaths as $folderPath) {
                // Use special key '__INBOX__' for null (inbox)
                $key = $folderPath ?? '__INBOX__';
                $grouped[$key] ??= [];
                $grouped[$key][] = $rule;
            }
        }

        return $grouped;
    }

    /**
     * Apply specific rules to a message.
     *
     * @param list<Rule> $rules
     */
    private function doApplyRules(MessageInterface $message, array $rules): void
    {
        foreach ($rules as $rule) {
            $startTimeNs = hrtime(true);
            $matched = false;
            $actionCount = 0;
            $failureCount = 0;

            try {
                $actions = $rule($message);

                // Materialize generator into array to avoid "already closed generator" error
                // when actions modify the message (e.g., move/delete)
                $actionsList = is_array($actions) ? $actions : iterator_to_array($actions, false);
                $matched = $actionsList !== [];

                foreach ($actionsList as $action) {
                    // Cast to callable for PHPStan - all Actions implement __invoke
                    /** @var callable $callableAction */
                    $callableAction = $action;
                    try {
                        Callback::createFor($callableAction)->invokeAll(
                            Parameter::union(...$this->useParameters($message))
                        );
                        $actionCount++;
                    } catch (\Exception $actionException) {
                        $failureCount++;
                        $this->recordStats($rule->name, $matched, $actionCount, $failureCount, $startTimeNs);
                        throw $actionException;
                    }
                }

                if ($matched) {
                    $this->logger->info('Rule "{rule}" matched message: {subject}', [
                        'rule' => $rule->name,
                        'subject' => $message->subject() ?? '(no subject)',
                    ]);
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'closed generator')) {
                    $this->recordStats($rule->name, $matched, $actionCount, $failureCount, $startTimeNs);
                    $this->logger->warning(sprintf("Skipping rule '%s' for message: generator already closed. This may happen if the message was moved/deleted by a previous action.", $rule->name));
                    continue;
                }

                throw $e;
            }

            $this->recordStats($rule->name, $matched, $actionCount, $failureCount, $startTimeNs);
        }
    }

    private function recordStats(string $ruleName, bool $matched, int $actionCount, int $failureCount, int $startTimeNs): void
    {
        $this->statsCollector->recordEvaluation(
            $ruleName,
            $matched,
            $actionCount,
            $failureCount,
            (hrtime(true) - $startTimeNs) / 1_000_000,
        );
    }

    public function apply(): void
    {
        $this->statsCollector->reset();
        $this->logger->debug('Connecting to mailbox...');
        $this->mailbox->connect();

        // Group rules by folder to optimize IMAP queries
        $rulesByFolder = $this->groupRulesByFolder();

        foreach ($rulesByFolder as $folderPath => $rulesForFolder) {
            // Decode the special inbox key
            $actualPath = $folderPath === '__INBOX__' ? null : $folderPath;
            $folderLabel = $actualPath ?? 'INBOX';

            $this->logger->info('Processing folder: {folder}', [
                'folder' => $folderLabel,
            ]);

            // Get folder and messages
            $folder = $actualPath === null
                ? $this->mailbox->inbox()
                : $this->mailbox->folders()->find($actualPath);

            if ($folder === null) {
                $this->logger->warning('Folder not found: {folder}, skipping rules', [
                    'folder' => $actualPath,
                ]);
                continue;
            }

            $messages = $folder->messages()->withHeaders()->get();
            $messageCount = count($messages);

            $this->logger->info('Found {messages_count} message(s) in {folder}', [
                'messages_count' => $messageCount,
                'folder' => $folderLabel,
            ]);

            // Apply only the rules for this folder
            foreach ($messages as $message) {
                assert($message instanceof Message);
                $this->doApplyRules(new ImapMessageAdapter($message), $rulesForFolder);
            }
        }

        $this->logger->debug('Done processing messages');
    }

    public function watch(): void
    {
        $this->statsCollector->reset();
        $this->mailbox->connect();

        $rulesByFolder = $this->groupRulesByFolder();

        foreach ($rulesByFolder as $folderPath => $rulesForFolder) {
            $actualPath = $folderPath === '__INBOX__' ? null : $folderPath;
            $folderLabel = $actualPath ?? 'INBOX';

            $folder = $actualPath === null
                ? $this->mailbox->inbox()
                : $this->mailbox->folders()->find($actualPath);

            if ($folder === null) {
                $this->logger->warning('Folder not found: {folder}, skipping watch', [
                    'folder' => $actualPath,
                ]);
                continue;
            }

            $this->logger->info('Watching folder: {folder}', [
                'folder' => $folderLabel,
            ]);

            $folder->idle(function (Message $message) use ($rulesForFolder): void {
                $this->doApplyRules(new ImapMessageAdapter($message), $rulesForFolder);
            });
        }
    }

    /**
     * Previews what actions would be executed for each message without actually executing them.
     *
     * This is a dry-run mode useful for testing rule configurations before applying them.
     *
     * @return list<PreviewResult> List of preview results showing messages, rules, and actions
     */
    public function preview(int $limit = 10): array
    {
        $this->mailbox->connect();

        // Group rules by folder to optimize IMAP queries
        $rulesByFolder = $this->groupRulesByFolder();
        $results = [];

        foreach ($rulesByFolder as $folderPath => $rulesForFolder) {
            // Decode the special inbox key
            $actualPath = $folderPath === '__INBOX__' ? null : $folderPath;

            // Get folder and messages
            $folder = $actualPath === null
                ? $this->mailbox->inbox()
                : $this->mailbox->folders()->find($actualPath);

            if ($folder === null) {
                continue;
            }

            $messageQuery = $folder->messages()->withHeaders();

            if ($limit > 0) {
                $messageQuery = $messageQuery->limit($limit);
            }

            foreach ($messageQuery->get() as $message) {
                assert($message instanceof Message);
                $adapter = new ImapMessageAdapter($message);

                // Only check rules for this folder
                foreach ($rulesForFolder as $rule) {
                    // Evaluate the rule to get actions (but don't execute them)
                    $actions = $rule($adapter);
                    $actionsList = [];

                    // Collect actions without executing
                    foreach ($actions as $action) {
                        $actionsList[] = $action;
                    }

                    // Only add result if rule matched (has actions)
                    if ($actionsList !== []) {
                        $results[] = new PreviewResult(
                            message: $adapter,
                            ruleName: $rule->name,
                            actions: $actionsList,
                        );
                    }
                }
            }
        }

        return $results;
    }
}
