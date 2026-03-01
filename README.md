# Mailbox Rules - Declarative Email Filtering DSL

A declarative PHP DSL for defining email rule matching and automated actions using IMAP.

## Features

- **Declarative syntax** for defining email rules
- **Flexible matchers** for sender, recipient, and subject filtering
- **Pattern matching** with wildcards and regular expressions
- **Logical combinators** (AND, OR, NOT) for complex rule logic
- **Built-in actions** for common email operations
- **Composable helpers** for chaining multiple actions
- **Type-safe** with full PHP 8.3+ type hints

## Installation

```bash
composer require aegypius/mailbox-rules
```

## Quick Start

Create a `rules.php` file:

```php
<?php

use MailboxRules\Action\MoveToFolder;
use MailboxRules\Action\MarkAsRead;
use function MailboxRules\{mailbox, rule, from, chain};

return mailbox('imap://user:pass@imap.example.com', [
    rule(
        name: "Archive Newsletters",
        when: from("*@newsletters.com"),
        then: static fn () => chain(
            new MoveToFolder("Newsletters"),
            new MarkAsRead()
        )
    ),
]);
```

## Core Concepts

### Rules

Rules are defined using the `rule()` function with three parameters:

```php
rule(
    name: "Rule Name",           // Human-readable rule name
    when: $matcher,              // Matcher that evaluates the message
    then: static fn () => ...    // Closure that yields actions
)
```

### Matchers

Matchers evaluate whether a message matches specific criteria.

#### `any()` - Match All Messages

```php
rule(
    name: "Log Everything",
    when: any(),
    then: static fn () => yield new LogAction()
)
```

#### `from()` - Match Sender Email

```php
// Exact match
from("sender@example.com")

// Wildcard domain
from("*@example.com")

// Wildcard local part
from("newsletter-*@site.com")

// Regex pattern
from("/^admin@.*/i")
```

#### `to()` - Match Recipient Email

```php
// Matches if any recipient matches the pattern
to("support@example.com")
to("*@team.example.com")
to("/^.*@(support|help)\.com$/i")
```

#### `subject()` - Match Subject Line

```php
// Exact match
subject("Important Message")

// Wildcard
subject("*[Newsletter]*")
subject("Order #*")

// Regex
subject("/^\\[URGENT\\].*/i")
```

### Logical Combinators

Combine multiple matchers with logical operators.

#### `allOf()` - AND Logic

All matchers must match:

```php
rule(
    name: "Important Team Emails",
    when: allOf(
        from("*@company.com"),
        subject("*[Team]*")
    ),
    then: static fn () => yield new Flag()
)
```

#### `anyOf()` - OR Logic

At least one matcher must match:

```php
rule(
    name: "Multiple Senders",
    when: anyOf(
        from("*@vendor1.com"),
        from("*@vendor2.com"),
        from("*@vendor3.com")
    ),
    then: static fn () => yield new MoveToFolder("Vendors")
)
```

#### `not()` - NOT Logic

Negates a matcher:

```php
rule(
    name: "Not From Spam",
    when: not(from("*@spam.com")),
    then: static fn () => yield new MoveToFolder("Inbox")
)
```

#### Nested Combinators

Combine logical operators for complex rules:

```php
rule(
    name: "Spam Filter",
    when: allOf(
        anyOf(
            from("*@spam.com"),
            subject("*Get rich quick*")
        ),
        not(subject("*Order Confirmation*"))
    ),
    then: static fn () => yield new MoveToFolder("Spam")
)
```

### Actions

Actions are executed when a rule matches. They implement the `Action` interface with an `__invoke(Message $message): void` method.

#### `MoveToFolder` - Move Message

```php
new MoveToFolder("Archive")
new MoveToFolder("Archive", expunge: true)
```

Moves the message to the specified folder. Set `expunge: true` to permanently remove from the source folder.

#### `MarkAsRead` - Mark as Read

```php
new MarkAsRead()
```

Marks the message as read (sets the `\Seen` flag).

#### `Flag` - Flag Message

```php
new Flag()
```

Flags the message (sets the `\Flagged` flag).

#### `LogAction` - Log Message

```php
new LogAction()
```

Logs message details to stdout.

### Helpers

#### `chain()` - Multiple Actions

Execute multiple actions in sequence:

```php
rule(
    name: "Process Newsletter",
    when: subject("*[Newsletter]*"),
    then: static fn () => chain(
        new MoveToFolder("Newsletters"),
        new MarkAsRead(),
        new LogAction()
    )
)
```

Without `chain()`, you would need to yield each action individually:

```php
then: static fn () {
    yield new MoveToFolder("Newsletters");
    yield new MarkAsRead();
    yield new LogAction();
}
```

## Pattern Matching

The DSL supports three pattern matching modes:

### Exact Match

```php
from("user@example.com")
```

Matches the exact string.

### Wildcard Match

```php
from("*@example.com")     // Any sender from example.com
from("newsletter-*@*.com") // newsletter- prefix, any .com domain
subject("*[Important]*")   // Contains [Important]
```

- `*` matches any sequence of characters
- Wildcards can appear anywhere in the pattern

### Regex Match

```php
from("/^admin@.*/i")              // Case-insensitive admin emails
subject("/^\\[URGENT\\]\\s.*/")   // Subject starts with [URGENT]
```

- Pattern must start and end with `/`
- Supports all PHP regex modifiers (i, m, s, etc.)

## Examples

### Basic Spam Filter

```php
rule(
    name: "Move Spam",
    when: anyOf(
        from("*@spam.com"),
        subject("*viagra*"),
        subject("*lottery*")
    ),
    then: static fn () => yield new MoveToFolder("Spam")
)
```

### Newsletter Management

```php
rule(
    name: "Archive and Read Newsletters",
    when: allOf(
        anyOf(
            from("*@newsletters.com"),
            subject("*[Newsletter]*")
        ),
        not(subject("*Unsubscribe*"))
    ),
    then: static fn () => chain(
        new MoveToFolder("Newsletters"),
        new MarkAsRead()
    )
)
```

### Team Communication

```php
rule(
    name: "Flag Team Mentions",
    when: allOf(
        to("team@example.com"),
        subject("*@yourname*")
    ),
    then: static fn () => chain(
        new Flag(),
        new LogAction()
    )
)
```

### Multiple Vendor Handling

```php
rule(
    name: "Vendor Emails",
    when: anyOf(
        from("*@vendor1.com"),
        from("*@vendor2.com"),
        from("*@vendor3.com")
    ),
    then: static fn () => yield new MoveToFolder("Vendors")
)
```

### Complex Spam with Exceptions

```php
rule(
    name: "Smart Spam Filter",
    when: allOf(
        anyOf(
            from("*@spam.com"),
            from("*@junk.net"),
            subject("*click here*")
        ),
        not(anyOf(
            subject("*Order Confirmation*"),
            subject("*Password Reset*"),
            from("*@trusted.com")
        ))
    ),
    then: static fn () => yield new MoveToFolder("Spam")
)
```

## Custom Actions

Create custom actions by implementing the `Action` interface:

```php
<?php

namespace MailboxRules\Action;

use DirectoryTree\ImapEngine\Message;
use MailboxRules\Action;

final readonly class CustomAction implements Action
{
    public function __construct(
        private string $parameter,
    ) {
    }

    public function __invoke(Message $message): void
    {
        // Your custom logic here
        // Access message methods: $message->subject(), $message->from(), etc.
    }
}
```

Use it in rules:

```php
rule(
    name: "Custom Processing",
    when: from("*@example.com"),
    then: static fn () => yield new CustomAction("param")
)
```

## Testing

Run the test suite:

```bash
composer test
```

Run PHPStan static analysis:

```bash
composer phpstan
```

Run code style checks:

```bash
composer cs-check
```

## Development Setup

Install git hooks:

```bash
pre-commit install --install-hooks -t pre-commit -t commit-msg
```

## Requirements

- PHP 8.3 or higher
- ext-imap
- DirectoryTree/ImapEngine

## License

MIT

## Contributing

Contributions are welcome! Please follow the existing code style and add tests for new features.
