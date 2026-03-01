<?php

declare(strict_types=1);

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;
use MailboxRules\MailboxFactory;
use MailboxRules\Model\Rule;
use MailboxRules\Model\Rules;
use MailboxRules\ValueObject\Dsn;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;

/**
 * Create a mailbox with rules.
 *
 * @param string|Dsn $dsn The DSN string or Dsn object.
 * @param iterable<Rule> $rules A list of rules to apply.
 * @return Rules The created Rules object.
 */
function mailbox(string|Dsn $dsn, iterable $rules): Rules
{
    if (is_string($dsn)) {
        $dsn = Dsn::fromString($dsn);
    }

    $logger = new Logger(
        name: "app",
        handlers: [new StreamHandler("php://stdout", Level::Info)],
        processors: [new PsrLogMessageProcessor(dateFormat: "Y-m-d H:i:s")]
    );

    return new Rules(
        mailbox: MailboxFactory::createMailbox($dsn),
        rules: $rules,
        logger: $logger
    );
}

/**
 * Create a rule.
 *
 * @param string $name The name of the rule.
 * @param \Closure(Message): iterable<Action> $callback The callback that returns an iterable of Actions.
 * @return Rule The created Rule object.
 */
function rule(string $name, \Closure $callback): Rule
{
    return new Rule($name, $callback);
}
