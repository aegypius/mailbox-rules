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
            $actions = $rule($message);
            foreach ($actions as $action) {
                // Cast to callable for PHPStan - all Actions implement __invoke
                /** @var callable $callableAction */
                $callableAction = $action;
                Callback::createFor($callableAction)->invokeAll(
                    Parameter::union(...$this->useParameters($message))
                );
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
        $this->mailbox->connect();
        $query = $this->mailbox->inbox()->messages()->withHeaders();
        $chunkSize = 100;
        $page = 1;

        // Process messages in chunks using pagination
        do {
            $messages = $query->limit($chunkSize, $page)->get();

            foreach ($messages as $message) {
                assert($message instanceof Message);
                $this->doApply($message);
            }

            $page++;
        } while ($messages->isNotEmpty());
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
        $query = $this->mailbox->inbox()->messages()->withHeaders()->limit(10);
        $results = [];

        foreach ($query->get() as $message) {
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
