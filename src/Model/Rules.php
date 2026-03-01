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
        $messageQuery = $this->mailbox->inbox()->messages()->withHeaders();
        foreach ($messageQuery->get() as $message) {
            assert($message instanceof Message);
            $this->doApply($message);
        }
    }

    public function watch(): void
    {
        $this->mailbox->inbox()->idle($this->doApply(...));
    }
}
