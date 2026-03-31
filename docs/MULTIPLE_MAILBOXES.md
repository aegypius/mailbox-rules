# Multiple Mailboxes Support

As of this version, mailbox-rules supports processing multiple email accounts in a single configuration file with clean separation of concerns.

## Architecture

The new architecture introduces:

1. **`MailboxConfiguration`** - A value object that represents a mailbox with its rules
2. **`MailboxProcessor`** - A service that processes mailbox configurations independently
3. **Separation of Concerns** - Configuration is separate from execution

This architecture enables:
- ✅ Clean isolation between mailboxes
- ✅ Independent processing with separate loggers
- ✅ Parallel processing capabilities (future enhancement)
- ✅ Better testability

## Usage

### Single Mailbox (Backward Compatible)

```php
<?php
return mailbox(env('MAILBOX_DSN'), [
    rule(...),
    rule(...),
]);
```

### Multiple Mailboxes

```php
<?php
return mailboxes(
    mailbox(env('WORK_DSN'), [...], name: 'Work'),
    mailbox(env('PERSONAL_DSN'), [...], name: 'Personal'),
);
```

### With Named Mailboxes

The optional `name` parameter helps identify mailboxes in logs:

```php
<?php

use function MailboxRules\mailbox;
use function MailboxRules\mailboxes;
use function MailboxRules\rule;
use function MailboxRules\env;
use function MailboxRules\from;

return mailboxes(
    mailbox(
        dsn: env('WORK_MAILBOX_DSN'),
        rules: [
            rule(
                name: 'Work Rule 1',
                when: from('boss@company.com'),
                then: static fn () => [new MarkAsImportant()]
            ),
        ],
        name: 'Work Account'
    ),

    mailbox(
        dsn: env('PERSONAL_MAILBOX_DSN'),
        rules: [
            rule(
                name: 'Personal Rule 1',
                when: from('friend@example.com'),
                then: static fn () => [new MarkAsRead()]
            ),
        ],
        name: 'Personal Account'
    ),
);
```

## Environment Variables

For multiple mailboxes, configure multiple DSN environment variables:

```bash
# .envrc
export WORK_MAILBOX_DSN="imap://user@work.com:password@imap.work.com:993/INBOX"
export PERSONAL_MAILBOX_DSN="imap://user@personal.com:password@imap.personal.com:993/INBOX"
```

Or using password manager integration:

```bash
# .envrc
export WORK_MAILBOX_DSN="$(pass work/email/dsn)"
export PERSONAL_MAILBOX_DSN="$(pass personal/email/dsn)"
```

## Execution Behavior

When using multiple mailboxes:

1. Each mailbox configuration is processed sequentially (parallel support planned)
2. Each mailbox has isolated execution via `MailboxProcessor`
3. A shared logger is used across all mailboxes with contextual information
4. Connection to each mailbox is established independently as needed
5. All rules for a mailbox are processed before moving to the next

## CLI Usage

The CLI command works the same way with multiple mailboxes:

```bash
# Apply rules from config with multiple mailboxes
./bin/mailbox-rules apply rules-multiple.php

# Dry-run mode shows actions for all mailboxes
./bin/mailbox-rules apply rules-multiple.php --dry-run
```

In dry-run mode, mailbox names are displayed when processing multiple mailboxes.

## Example

See `rules-multiple-mailboxes.example.php` for a complete working example.

## API Reference

### `mailbox()`

```php
function mailbox(
    string|Dsn $dsn,
    iterable<Rule> $rules,
    ?string $name = null
): MailboxConfiguration
```

**Parameters:**
- `$dsn`: The IMAP connection DSN (string or Dsn object)
- `$rules`: An iterable of Rule objects
- `$name`: Optional name for logging/identification

**Returns:**
- A `MailboxConfiguration` value object

### `mailboxes()`

```php
function mailboxes(MailboxConfiguration ...$configurations): array<MailboxConfiguration>
```

**Parameters:**
- `...$configurations`: One or more `MailboxConfiguration` objects (variadic)

**Returns:**
- An array of `MailboxConfiguration` objects

**Example:**

```php
return mailboxes(
    mailbox($dsn1, $rules1, name: 'Account 1'),
    mailbox($dsn2, $rules2, name: 'Account 2'),
    mailbox($dsn3, $rules3, name: 'Account 3'),
);
```

## Architecture Benefits

### Clean Separation

Each mailbox configuration is a standalone value object containing:
- DSN (connection information)
- Rules (what to do)
- Name (for identification)

This makes it easy to:
- Test configurations independently
- Move configurations between files
- Validate configurations before execution

### Independent Processing

The `MailboxProcessor` service:
- Takes a configuration as input
- Creates its own `Rules` instance
- Processes the mailbox independently
- Can be called in parallel (future enhancement)

### Testability

You can now test:
- Configuration loading separately from processing
- Individual mailbox processing
- Rule logic without IMAP connections

## Future Enhancements

The architecture is designed to support:
- **Parallel Processing**: Process multiple mailboxes concurrently with a `--parallel` flag
- **Retry Logic**: Retry failed mailboxes independently
- **Progress Reporting**: Track progress per mailbox
- **Resource Limits**: Limit concurrent connections per mailbox
