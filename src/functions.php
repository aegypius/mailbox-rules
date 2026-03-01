<?php

declare(strict_types=1);

namespace MailboxRules;

use Carbon\CarbonInterface;
use DirectoryTree\ImapEngine\Message;
use MailboxRules\Matcher\AllOfMatcher;
use MailboxRules\Matcher\AnyMatcher;
use MailboxRules\Matcher\AnyOfMatcher;
use MailboxRules\Matcher\AttachmentTypeMatcher;
use MailboxRules\Matcher\BccMatcher;
use MailboxRules\Matcher\CcMatcher;
use MailboxRules\Matcher\FromMatcher;
use MailboxRules\Matcher\HasAttachmentMatcher;
use MailboxRules\Matcher\LargerThanMatcher;
use MailboxRules\Matcher\Matcher;
use MailboxRules\Matcher\NotMatcher;
use MailboxRules\Matcher\ReceivedAfterMatcher;
use MailboxRules\Matcher\ReceivedBeforeMatcher;
use MailboxRules\Matcher\RecipientMatcher;
use MailboxRules\Matcher\SmallerThanMatcher;
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

/**
 * Create a matcher that inverts the result of another matcher (logical NOT).
 *
 * @param Matcher $matcher The matcher to invert
 * @return Matcher A matcher that returns the opposite result.
 */
function not(Matcher $matcher): Matcher
{
    return new NotMatcher($matcher);
}

/**
 * Create an iterable chain of actions.
 *
 * Provides a convenient way to yield multiple actions in sequence
 * as shorthand for manually yielding each action.
 *
 * @param Action ...$actions Actions to chain together
 * @return \Generator<Action> Generator yielding actions in order
 */
function chain(Action ...$actions): \Generator
{
    foreach ($actions as $action) {
        yield $action;
    }
}

/**
 * Get an environment variable value or throw an exception if not defined.
 *
 * @param string $name The environment variable name
 * @return string The environment variable value
 * @throws \RuntimeException If the environment variable is not defined or empty
 */
function env(string $name): string
{
    $value = \getenv($name);

    if ($value === false || $value === '') {
        throw new \RuntimeException(sprintf('"%s" environment variable must be defined', $name));
    }

    return $value;
}

/**
 * Create a matcher that matches messages received after a specific date/time.
 *
 * Supports absolute dates ("2024-01-15 12:00:00") and relative dates ("3 hours ago").
 *
 * @param CarbonInterface|string $datetime The cutoff date/time
 * @return Matcher A matcher for messages received after the specified time.
 */
function receivedAfter(CarbonInterface|string $datetime): Matcher
{
    return new ReceivedAfterMatcher($datetime);
}

/**
 * Create a matcher that matches messages received before a specific date/time.
 *
 * Supports absolute dates ("2024-01-15 12:00:00") and relative dates ("3 hours ago").
 *
 * @param CarbonInterface|string $datetime The cutoff date/time
 * @return Matcher A matcher for messages received before the specified time.
 */
function receivedBefore(CarbonInterface|string $datetime): Matcher
{
    return new ReceivedBeforeMatcher($datetime);
}

/**
 * Create a matcher that matches messages larger than a specific size.
 *
 * Supports bytes (int) or human-readable sizes ("1KB", "5MB", "2GB").
 *
 * @param int|string $size The minimum size (exclusive)
 * @return Matcher A matcher for messages larger than the specified size.
 */
function largerThan(int|string $size): Matcher
{
    return new LargerThanMatcher($size);
}

/**
 * Create a matcher that matches messages smaller than a specific size.
 *
 * Supports bytes (int) or human-readable sizes ("1KB", "5MB", "2GB").
 *
 * @param int|string $size The maximum size (exclusive)
 * @return Matcher A matcher for messages smaller than the specified size.
 */
function smallerThan(int|string $size): Matcher
{
    return new SmallerThanMatcher($size);
}

/**
 * Match messages with attachments.
 *
 * @return Matcher A matcher for messages that have at least one attachment.
 */
function hasAttachment(): Matcher
{
    return new HasAttachmentMatcher();
}

/**
 * Match messages by attachment type (MIME type or extension).
 *
 * Supports:
 * - Exact MIME types: "image/jpeg", "application/pdf"
 * - MIME type wildcards: "image/*", "application/*"
 * - File extensions: ".pdf", "pdf", "*.pdf"
 *
 * Returns true if ANY attachment matches the pattern.
 *
 * @param string $pattern The pattern to match against attachment MIME type or extension.
 * @return Matcher A matcher for messages with matching attachments.
 */
function attachmentType(string $pattern): Matcher
{
    return new AttachmentTypeMatcher($pattern);
}

/**
 * Match messages by CC recipient email address.
 *
 * Supports exact matches, wildcards (*@example.com), and regex patterns.
 * Matches if ANY CC recipient matches the pattern (case-insensitive).
 *
 * @param string $pattern The pattern to match against CC recipient emails.
 * @return Matcher A matcher for CC recipients.
 */
function cc(string $pattern): Matcher
{
    return new CcMatcher($pattern);
}

/**
 * Match messages by BCC recipient email address.
 *
 * Supports exact matches, wildcards (*@example.com), and regex patterns.
 * Matches if ANY BCC recipient matches the pattern (case-insensitive).
 *
 * @param string $pattern The pattern to match against BCC recipient emails.
 * @return Matcher A matcher for BCC recipients.
 */
function bcc(string $pattern): Matcher
{
    return new BccMatcher($pattern);
}

/**
 * Match messages by any recipient (To, CC, or BCC).
 *
 * Convenience matcher that checks if the pattern matches any recipient
 * in the To, CC, or BCC fields.
 *
 * Supports exact matches, wildcards (*@example.com), and regex patterns.
 * Matches if ANY recipient in any field matches the pattern (case-insensitive).
 *
 * @param string $pattern The pattern to match against recipient emails.
 * @return Matcher A matcher for any recipient field.
 */
function recipient(string $pattern): Matcher
{
    return new RecipientMatcher($pattern);
}
