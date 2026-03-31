<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
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
        private LoggerInterface $logger = new NullLogger()
    ) {
    }

    private function doApply(Message $message): void
    {
        foreach ($this->rules as $rule) {
            try {
                $actions = $rule($message);

                // Materialize generator into array to avoid "already closed generator" error
                // when actions modify the message (e.g., move/delete)
                $actionsList = is_array($actions) ? $actions : iterator_to_array($actions, false);

                foreach ($actionsList as $actionList) {
                    // Cast to callable for PHPStan - all Actions implement __invoke
                    /** @var callable $callableAction */
                    $callableAction = $actionList;
                    Callback::createFor($callableAction)->invokeAll(
                        Parameter::union(...$this->useParameters($message))
                    );
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'closed generator')) {
                    $this->logger->warning(sprintf("Skipping rule '%s' for message: generator already closed. This may happen if the message was moved/deleted by a previous action.", $rule->name));
                    continue;
                }

                throw $e;
            }
        }
    }

    /**
     * @return list<Parameter>
     */
    private function useParameters(Message $message): array
    {
        return [
            Parameter::typed(Message::class, $message),
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
    private function doApplyRules(Message $message, array $rules): void
    {
        foreach ($rules as $rule) {
            try {
                $actions = $rule($message);

                // Materialize generator into array to avoid "already closed generator" error
                // when actions modify the message (e.g., move/delete)
                $actionsList = is_array($actions) ? $actions : iterator_to_array($actions, false);

                foreach ($actionsList as $action) {
                    // Cast to callable for PHPStan - all Actions implement __invoke
                    /** @var callable $callableAction */
                    $callableAction = $action;
                    Callback::createFor($callableAction)->invokeAll(
                        Parameter::union(...$this->useParameters($message))
                    );
                }
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'closed generator')) {
                    $this->logger->warning(sprintf("Skipping rule '%s' for message: generator already closed. This may happen if the message was moved/deleted by a previous action.", $rule->name));
                    continue;
                }

                throw $e;
            }
        }
    }

    public function apply(): void
    {
        $this->logger->debug('Connecting to mailbox...');
        $this->mailbox->connect();

        // Group rules by folder to optimize IMAP queries
        $rulesByFolder = $this->groupRulesByFolder();

        foreach ($rulesByFolder as $folderPath => $rulesForFolder) {
            // Decode the special inbox key
            $actualPath = $folderPath === '__INBOX__' ? null : $folderPath;
            $this->logger->debug('Fetching messages from folder: {folder}', [
                'folder' => $actualPath ?? 'INBOX',
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

            $this->logger->debug('Processing {messages_count} messages', [
                'messages_count' => count($messages),
            ]);

            // Apply only the rules for this folder
            foreach ($messages as $message) {
                assert($message instanceof Message);
                $this->doApplyRules($message, $rulesForFolder);
            }
        }

        $this->logger->debug('Done processing messages');
    }

    public function watch(): void
    {
        $this->mailbox->inbox()->idle($this->doApply(...));
    }

    /**
     * Previews what actions would be executed for each message without actually executing them.
     *
     * This is a dry-run mode useful for testing rule configurations before applying them.
     *
     * @return list<PreviewResult> List of preview results showing messages, rules, and actions
     */
    public function preview(): array
    {
        $this->mailbox->connect();

        // Group rules by folder to optimize IMAP queries
        $rulesByFolder = $this->groupRulesByFolder();
        $results = [];

        foreach ($rulesByFolder as $folderPath => $rulesForFolder) {
            // Decode the special inbox key
            $actualPath = $folderPath === '__INBOX__' ? null : $folderPath;

            // Get folder and messages (limit 10 per folder)
            $folder = $actualPath === null
                ? $this->mailbox->inbox()
                : $this->mailbox->folders()->find($actualPath);

            if ($folder === null) {
                continue;
            }

            $messageQuery = $folder->messages()->withHeaders()->limit(10);

            foreach ($messageQuery->get() as $message) {
                assert($message instanceof Message);

                // Only check rules for this folder
                foreach ($rulesForFolder as $rule) {
                    // Evaluate the rule to get actions (but don't execute them)
                    $actions = $rule($message);
                    $actionsList = [];

                    // Collect actions without executing
                    foreach ($actions as $action) {
                        $actionsList[] = $action;
                    }

                    // Only add result if rule matched (has actions)
                    if ($actionsList !== []) {
                        $results[] = new PreviewResult(
                            message: $message,
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
