<?php

declare(strict_types=1);

namespace MailboxRules;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\AnyOfMatcher;
use MailboxRules\Matcher\FromMatcher;
use MailboxRules\Matcher\Matcher;
use MailboxRules\Matcher\SubjectMatcher;
use MailboxRules\Matcher\ToMatcher;
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
 * Supports two signatures:
 * 1. Legacy: rule(name, callback) - backward compatibility
 * 2. Matcher-based: rule(name, when: matcher, then: callable)
 *
 * @param string $name The name of the rule.
 * @param \Closure(Message): iterable<Action>|null $callback The callback (legacy signature).
 * @param Matcher|null $when The matcher to evaluate (new signature).
 * @param \Closure(Message): iterable<Action>|null $then The action callback (new signature).
 * @return Rule The created Rule object.
 */
function rule(
    string $name,
    ?\Closure $callback = null,
    ?Matcher $when = null,
    ?\Closure $then = null
): Rule {
    // New signature: rule(name, when: matcher, then: callable)
    if ($when !== null && $then !== null) {
        return new Rule($name, $when, $then);
    }

    // Legacy signature: rule(name, callback)
    if ($callback !== null) {
        return new Rule($name, null, $callback);
    }

    throw new \InvalidArgumentException(
        'rule() requires either callback parameter or both when and then parameters'
    );
}

/**
 * Create a matcher that matches all messages.
 *
 * @return Matcher A matcher that always returns true.
 */
function any(): Matcher
{
    return new AnyMatcher();
}

/**
 * Create a matcher that matches messages from a specific sender.
 *
 * Supports exact matches, wildcards, and regex patterns (case-insensitive).
 *
 * @param string $pattern Email pattern to match (exact, wildcard, or regex)
 * @return Matcher A matcher for the sender's email address.
 */
function from(string $pattern): Matcher
{
    return new FromMatcher($pattern);
}

/**
 * Create a matcher that matches messages to specific recipients.
 *
 * Matches if ANY recipient matches the pattern.
 * Supports exact matches, wildcards, and regex patterns (case-insensitive).
 *
 * @param string $pattern Email pattern to match (exact, wildcard, or regex)
 * @return Matcher A matcher for recipient email addresses.
 */
function to(string $pattern): Matcher
{
    return new ToMatcher($pattern);
}

/**
 * Create a matcher that matches messages based on subject line.
 *
 * Supports exact matches, wildcards, and regex patterns (case-insensitive).
 *
 * @param string $pattern Subject pattern to match (exact, wildcard, or regex)
 * @return Matcher A matcher for the message subject.
 */
function subject(string $pattern): Matcher
{
    return new SubjectMatcher($pattern);
}

/**
 * Create a matcher that matches when ALL provided matchers match (logical AND).
 *
 * Short-circuits on first failure for efficiency.
 *
 * @param Matcher ...$matchers One or more matchers to combine with AND logic
 * @return Matcher A matcher that requires all conditions to be true.
 */
function allOf(Matcher ...$matchers): Matcher
{
    return new AllOfMatcher(...$matchers);
}

/**
 * Create a matcher that matches when ANY provided matcher matches (logical OR).
 *
 * Short-circuits on first success for efficiency.
 *
 * @param Matcher ...$matchers One or more matchers to combine with OR logic
 * @return Matcher A matcher that requires at least one condition to be true.
 */
function anyOf(Matcher ...$matchers): Matcher
{
    return new AnyOfMatcher(...$matchers);
}
