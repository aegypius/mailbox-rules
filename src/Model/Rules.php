<?php

declare(strict_types=1);

namespace MailboxRules\Model;

use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\Message;
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

    public function apply(): void
    {
        $this->logger->debug('Connecting to mailbox...');
        $this->mailbox->connect();

        $this->logger->debug('Fetching messages...');
        // Process all messages directly without manual pagination
        // The library handles this internally with its limit() method
        $messages = $this->mailbox->inbox()->messages()->withHeaders()->get();

        $this->logger->debug('Processing {messages_count} messages', [
            'messages_count' => count($messages),
        ]);
        foreach ($messages as $message) {
            assert($message instanceof Message);
            $this->doApply($message);
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
        $messageQuery = $this->mailbox->inbox()->messages()->withHeaders()->limit(10);
        $results = [];

        foreach ($messageQuery->get() as $message) {
            assert($message instanceof Message);

            foreach ($this->rules as $rule) {
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

        return $results;
    }
}
