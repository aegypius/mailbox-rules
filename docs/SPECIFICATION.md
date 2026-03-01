# Email Rule Matching Specification

## Overview

This specification defines a declarative DSL for configuring email matching rules and actions in `rules.php`. The goal is to provide an intuitive, readable syntax for common email filtering scenarios.

## Requirements

### Functional Requirements

1. **Match email metadata** - Test messages against sender, recipient, subject, body, and other properties
2. **Apply actions conditionally** - Execute actions only when matchers succeed
3. **Compose complex conditions** - Combine multiple matchers with logical operators (AND, OR, NOT)
4. **Support multiple actions** - Apply multiple actions to a single matched message
5. **Maintain backward compatibility** - Existing closure-based rules must continue working

### Non-Functional Requirements

1. **Readable** - Configuration should read like natural language
2. **Type-safe** - Leverage PHP's type system for compile-time safety
3. **Testable** - Components should be unit-testable in isolation
4. **Extensible** - Users can create custom matchers and actions
5. **Performant** - Minimize overhead when evaluating rules

## Core Concepts

### 1. Matchers

Matchers are predicates that test email message properties. They return `true` when a message matches the criteria.

**Common matcher types:**
- Sender matching: `from(pattern)`
- Recipient matching: `to(pattern)`, `cc(pattern)`, `bcc(pattern)`
- Subject matching: `subject(pattern)`
- Body content matching: `body(pattern)`
- Metadata matching (date, size, flags)

**Pattern matching support:**
All matchers support the same pattern syntax:
- Exact match: `from("user@example.com")`, `subject("Meeting tomorrow")`
- Wildcard: `from("*@chaosium.com")`, `subject("*[Important]*")`
- Regex: `from(/^newsletter-.*@example\.com$/i)`, `subject(/\[.*\]/)`

**Logical composition:**
- AND - All matchers must match
- OR - At least one matcher must match
- NOT - Matcher must not match
- ANY - Always matches (catch-all)

### 2. Actions

Actions define what happens when a message matches. Multiple actions can be applied to a single message.

**Common action types:**
- Move/copy to folders
- Mark as read/unread
- Flag/unflag
- Delete/trash
- Log metadata

### 3. Rules

Rules combine matchers with actions. A rule has:
- **Name** - Human-readable identifier
- **Matcher** - Condition to evaluate (use `any()` to match all messages)
- **Then** - Callable that receives the message and returns iterable<Action>

The `then` parameter must be a callable with signature: `fn(Message $message): iterable<Action>`

## Desired API

### Example: Simple Rule

```php
rule(
    name: "Chaosium to Promotions",
    when: from("*@chaosium.com"),
    then: static fn(Message $message) => yield new MoveToFolder("Promotions")
)
```

### Example: Multiple Conditions (AND)

```php
rule(
    name: "Important newsletters",
    when: allOf(
        from("*@substack.com"),
        subject("*[Important]*")
    ),
    then: static function(Message $message) {
        yield new Flag("Important");
        yield new MoveToFolder("Newsletters");
    }
)
```

### Example: Multiple Conditions (OR)

```php
rule(
    name: "Social media notifications",
    when: anyOf(
        from("*@twitter.com"),
        from("*@facebook.com"),
        from("*@linkedin.com")
    ),
    then: static function(Message $message) {
        yield new MoveToFolder("Social");
        yield new MarkAsRead();
    }
)
```

### Example: Negation

```php
rule(
    name: "Non-work emails",
    when: not(from("*@company.com")),
    then: static fn(Message $message) => yield new MoveToFolder("Personal")
)
```

### Example: Closure-Based (Complex Logic)

For complex matching logic not expressible with matchers, combine with `any()`:

```php
rule(
    name: "Complex rule",
    when: any(),
    then: static function(Message $message) {
        if ($message->from()?->email() === "specific@example.com"
            && strlen($message->subject() ?? "") > 50) {
            yield new MoveToFolder("LongEmails");
        }
    }
)
```

## Complete Configuration Example

```php
<?php

use MailboxRules\Action\{LogAction, MoveToFolder, MarkAsRead, Flag};

return mailbox(\getenv("MAILBOX_DSN") ?: throw new \RuntimeException('MAILBOX_DSN required'), [
    // Chaosium emails to Promotions
    rule(
        name: "Chaosium to Promotions",
        when: from("*@chaosium.com"),
        then: static fn(Message $message) => yield new MoveToFolder("Promotions")
    ),

    // Social media to Social folder
    rule(
        name: "Social media",
        when: anyOf(
            from("*@twitter.com"),
            from("*@facebook.com")
        ),
        then: static function(Message $message) {
            yield new MoveToFolder("Social");
            yield new MarkAsRead();
        }
    ),

    // Log everything (fallback)
    rule(
        name: "Log all",
        when: any(),
        then: static fn(Message $message) => yield new LogAction()
    ),
]);
```

## Benefits

1. **Readable** - Rules read like natural language
2. **Composable** - Matchers can be combined with logical operators
3. **Type-safe** - Matchers and Actions are strongly typed
4. **Backward compatible** - Existing closure-based rules still work
5. **Testable** - Matchers can be unit tested independently
6. **Extensible** - Custom matchers/actions can be easily added

## Future Enhancements

- Date/time based matchers: `receivedAfter()`, `receivedBefore()`
- Size matchers: `largerThan()`, `smallerThan()`
- Attachment matchers: `hasAttachment()`, `attachmentType()`
- Action helper: `chain(Action ...$actions): iterable<Action>` as shortcut for yielding multiple actions
- Dry-run mode: `preview()` to test rules without applying
