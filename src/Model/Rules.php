<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use MailboxRules\Contract\MailboxInterface;
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
        private MailboxInterface $mailbox,
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
            Parameter::typed(MailboxInterface::class, $this->mailbox),
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
            $actionCount = 0;
            $failureCount = 0;

            $actionsList = $rule($message);
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
        $this->processFolders($this->groupRulesByFolder(), []);
        $this->logger->debug('Done processing messages');
    }

    /**
     * Apply once, then re-poll every folder for messages above the highest UID seen.
     *
     * @param int|null $passes Stop after this many passes; null runs forever.
     */
    public function watch(int $interval = 60, ?int $passes = null): void
    {
        $this->statsCollector->reset();
        $rulesByFolder = $this->groupRulesByFolder();
        $marks = [];

        for ($pass = 0; $passes === null || $pass < $passes; $pass++) {
            if ($pass > 0) {
                sleep($interval);
            }
            $marks = $this->processFolders($rulesByFolder, $pass === 0 ? [] : $marks);
        }
    }

    /**
     * @param array<string, list<Rule>> $rulesByFolder
     * @param array<string, int> $afterUids highest UID seen per folder; empty means everything
     * @return array<string, int> highest UID seen per folder
     */
    private function processFolders(array $rulesByFolder, array $afterUids): array
    {
        $marks = $afterUids;
        $existing = $this->mailbox->folders();

        foreach ($rulesByFolder as $folderPath => $rulesForFolder) {
            $actualPath = $folderPath === '__INBOX__' ? 'INBOX' : $folderPath;

            if (!in_array($actualPath, $existing, true)) {
                $this->logger->warning('Folder not found: {folder}, skipping rules', [
                    'folder' => $actualPath,
                ]);
                continue;
            }

            $this->logger->info('Processing folder: {folder}', [
                'folder' => $actualPath,
            ]);

            $messages = $this->mailbox->messages($actualPath, $afterUids[$actualPath] ?? null);

            $this->logger->info('Found {messages_count} message(s) in {folder}', [
                'messages_count' => count($messages),
                'folder' => $actualPath,
            ]);

            foreach ($messages as $message) {
                $marks[$actualPath] = max($marks[$actualPath] ?? 0, $message->uid());
                $this->doApplyRules($message, $rulesForFolder);
            }
        }

        return $marks;
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
        $existing = $this->mailbox->folders();
        $results = [];

        foreach ($this->groupRulesByFolder() as $folderPath => $rulesForFolder) {
            $actualPath = $folderPath === '__INBOX__' ? 'INBOX' : $folderPath;

            if (!in_array($actualPath, $existing, true)) {
                continue;
            }

            $messages = $this->mailbox->messages($actualPath);
            if ($limit > 0) {
                $messages = array_slice($messages, 0, $limit);
            }

            foreach ($messages as $message) {
                foreach ($rulesForFolder as $rule) {
                    $actions = $rule($message);

                    if ($actions !== []) {
                        $results[] = new PreviewResult(
                            message: $message,
                            ruleName: $rule->name,
                            actions: $actions,
                        );
                    }
                }
            }
        }

        return $results;
    }
}
