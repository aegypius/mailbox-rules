<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use MailboxRules\Contract\MailboxInterface;
use MailboxRules\Contract\MessageInterface;
use MailboxRules\Matcher\Matcher;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/** Walks the Folders targeted by Rules; each mode supplies only its per-message step. */
final class Traversal
{
    /**
     * @var list<Rule>
     */
    private readonly array $rules;

    /**
     * @var array<string, int> highest UID seen per Folder, in memory only
     */
    private array $marks = [];

    /**
     * @param iterable<Rule> $rules
     */
    public function __construct(
        private readonly MailboxInterface $mailbox,
        iterable $rules,
        private readonly LoggerInterface $logger = new NullLogger(),
    ) {
        $this->rules = array_values([...$rules]);
    }

    /**
     * Visit every message of every targeted Folder.
     *
     * @param callable(MessageInterface, list<Rule>): void $step
     * @param int|null $limit Messages per Folder; null for all.
     */
    public function pass(callable $step, ?int $limit = null): void
    {
        $this->walk($step, false, $limit);
    }

    /**
     * Visit only messages above each Folder's mark; a Folder without a mark is visited whole.
     *
     * @param callable(MessageInterface, list<Rule>): void $step
     */
    public function poll(callable $step): void
    {
        $this->walk($step, true, null);
    }

    /**
     * @param callable(MessageInterface, list<Rule>): void $step
     */
    private function walk(callable $step, bool $aboveMarks, ?int $limit): void
    {
        $existing = $this->mailbox->folders();

        $rulesByFolder = [];
        foreach ($this->rules as $rule) {
            $scope = $rule->matcher->folderScope($existing) ?? [Matcher::DEFAULT_FOLDER];
            if ($scope === []) {
                $this->logger->warning('No folder in scope for rule: {rule}, skipping', [
                    'rule' => $rule->name,
                ]);
            }

            foreach ($scope as $folder) {
                if (!in_array($folder, $existing, true)) {
                    $this->logger->warning('Folder not found: {folder}, skipping rule: {rule}', [
                        'folder' => $folder,
                        'rule' => $rule->name,
                    ]);
                    continue;
                }

                $rulesByFolder[$folder][] = $rule;
            }
        }

        foreach ($rulesByFolder as $folder => $rules) {
            $this->logger->info('Processing folder: {folder}', [
                'folder' => $folder,
            ]);

            $messages = $this->mailbox->messages($folder, $aboveMarks ? ($this->marks[$folder] ?? null) : null, $limit);

            $this->logger->info('Found {messages_count} message(s) in {folder}', [
                'messages_count' => count($messages),
                'folder' => $folder,
            ]);

            foreach ($messages as $message) {
                $this->marks[$folder] = max($this->marks[$folder] ?? 0, $message->uid());
                $step($message, $rules);
            }
        }
    }
}
