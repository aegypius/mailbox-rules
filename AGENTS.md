# AGENTS.md

Guide for AI agents working in the mailbox-rules codebase.

## Project Overview

**mailbox-rules** is a PHP 8.3+ application for applying automated rules to IMAP mailboxes. It uses a declarative configuration approach where users define rules in PHP configuration files that determine actions to take on email messages.

**Key Technologies:**
- PHP 8.3+ (configured for PHP 8.5 via Rector)
- Symfony Components (Console, DI, Config, Runtime)
- DirectoryTree/ImapEngine for IMAP operations
- Monolog for logging
- PHPUnit for testing
- PHPStan (level max) for static analysis
- ECS (Easy Coding Standard) for code style
- Rector for automated refactoring and PHP upgrades
- Dagger for CI/CD pipeline

**Documentation:**
- **[Specification](docs/SPECIFICATION.md)**: Declarative DSL design for email rule matching with matchers and actions
- **[Implementation Tasks](docs/TASKS.md)**: TDD-based breakdown of implementation tasks following Red-Green-Refactor principles

## Essential Commands

### Development

```bash
# Install dependencies
composer install

# Run the application (apply rules to mailbox)
./bin/mailbox-rules apply [config-file]
# Default config: ./rules.php

# Run with custom rules file
./bin/mailbox-rules apply path/to/custom-rules.php
```

### Testing & Quality

```bash
# Run tests
./vendor/bin/phpunit

# Run specific test suite
./vendor/bin/phpunit --testsuite=default

# Static analysis (PHPStan level max)
./vendor/bin/phpstan analyze

# Check coding standards
./vendor/bin/ecs check

# Fix coding standards automatically
./vendor/bin/ecs check --fix

# Automated refactoring and code upgrades (Rector)
./vendor/bin/rector process --dry-run

# Apply Rector changes
./vendor/bin/rector process
```

### CI/CD with Dagger

```bash
# Check coding standards via Dagger
dagger call check-coding-standards

# Run tests via Dagger
dagger call test

# Run specific test suite via Dagger
dagger call test --test-suite=default
```

### Git Hooks

```bash
# Install pre-commit hooks (required after clone)
pre-commit install --install-hooks -t pre-commit -t commit-msg
```

## Project Structure

```
.
├── bin/
│   └── mailbox-rules          # CLI entry point (Symfony Console app)
├── src/
│   ├── Action.php             # Action interface (marker interface)
│   ├── Action/                # Concrete action implementations
│   │   ├── LogAction.php      # Logs message details
│   │   ├── MoveToTrash.php    # Moves message to trash
│   │   └── WithCondition.php  # Trait for conditional actions
│   ├── Console/
│   │   └── ApplyCommand.php   # Main CLI command
│   ├── Loader/
│   │   └── RuleFileLoader.php # Loads rules from PHP config files
│   ├── Model/
│   │   ├── Rule.php           # Represents a single rule
│   │   └── Rules.php          # Collection of rules + application logic
│   ├── ValueObject/
│   │   └── Dsn.php            # IMAP DSN value object
│   ├── MailboxFactory.php     # Creates IMAP mailbox connections
│   ├── functions.php          # Helper functions (mailbox, rule)
│   └── services.php           # Symfony DI configuration
├── tests/
│   ├── bootstrap.php          # Test bootstrap
│   └── fixtures/              # Test fixtures (sample rules)
├── .dagger/                   # Dagger module for CI/CD
│   └── src/PhpProject.php     # Dagger functions
├── rules.php                  # Example/default rules configuration
└── composer.json              # Dependencies and autoload config
```

## Code Organization

### Namespace Structure

- **MailboxRules**: Root namespace (PSR-4 autoloaded from `src/`)
- **Tests**: Test namespace (PSR-4 autoloaded from `tests/`)

### Key Concepts

1. **Rules Configuration (`rules.php`)**: Entry point where users define mailbox DSN and rules
2. **Rule**: Named callable that receives a `Message` and returns an `Action`
3. **Action**: Marker interface for operations to perform on messages
4. **Rules (Model)**: Collection that applies rules to mailbox messages
5. **DSN**: Value object for IMAP connection strings

### Helper Functions

Two global helper functions defined in `src/functions.php`:

```php
mailbox(string|Dsn $dsn, iterable $rules): Rules
rule(string $name, callable $callback): Rule
```

## Coding Conventions

### Code Style

- **Standard**: PSR-12 + additional rules from ECS (array, docblock, namespaces, comments, clean code sets)
- **Strict Types**: All PHP files must start with `declare(strict_types=1);`
- **Final by Default**: Classes should be `final readonly` unless designed for extension
- **Type Hints**: Always use full type hints (parameters, return types, properties)
- **Named Arguments**: Used extensively (see `mailbox()` function, Logger instantiation)

### Sensitive Data

- Mark sensitive parameters with `#[\SensitiveParameter]` attribute (see `Dsn.php`)
- Never log passwords or credentials

### Symfony Attributes

- Use Symfony attributes for console commands: `#[AsCommand(name: "apply")]`
- Use autowiring and autoconfiguration in service definitions

### PHPStan

- **Level**: `max` (strictest possible)
- **Paths**: `src/` and `tests/`
- All code must pass PHPStan analysis without errors

### Rector

- **Configuration**: `rector.php`
- **Target**: PHP 8.5
- **Paths**: `src/`, `tests/`, `.dagger/src/`
- **Sets**: Dead code removal, code quality, coding style, type declarations, privatization, naming, instanceof, early return, strict booleans, PHPUnit 12.0
- Run with `--dry-run` first to preview changes before applying

## Architectural Patterns

### Dependency Injection

- Uses Symfony DI Container
- Services configured in `src/services.php`
- Defaults: autowire + autoconfigure enabled
- Application service is public (entry point)

### Command Pattern

- Actions implement the `Action` interface (marker interface)
- Actions are invoked via `Zenstruck\Callback` which handles parameter injection
- Actions can receive `Message` and `LoggerInterface` parameters

### Configuration as Code

- Rules are defined in PHP files that return `Rules` objects
- Type-safe configuration with IDE support
- Example pattern:

```php
return mailbox("imap://user:pass@host:port/INBOX", [
    rule("name", static fn(Message $m): Action => new SomeAction()),
]);
```

### Functional Approach

- Rules use callables/closures
- Helper functions for fluent API (`mailbox()`, `rule()`)
- Generators can be used (see `tests/fixtures/rules.php` for `yield` example)

## Testing Approach

### PHPUnit Configuration

- **Bootstrap**: `tests/bootstrap.php`
- **Cache**: `.phpunit.cache/`
- **Strict Mode**: Enabled (fails on risky tests, warnings, deprecations)
- **Coverage Metadata**: Required and strictly enforced
- **Execution Order**: Dependencies first, then defects

### Test Patterns

Currently minimal test coverage. When adding tests:
- Place in `tests/` directory
- Use PSR-4 namespace `Tests\`
- Follow PHPUnit 12.x conventions
- Ensure coverage metadata is present

## Important Gotchas

### Environment Variables

- **MAILBOX_DSN**: Required environment variable for IMAP connection
- Set via `.envrc` (direnv integration) using `pass aegypius/emails/primary/dsn`
- Format: `imap://user:password@host:port/path`

### Symfony Runtime

- Application uses Symfony Runtime for bootstrapping
- Entry point (`bin/mailbox-rules`) returns a closure that creates the Application
- Memory limit set to 250M in entry point

### IMAP Connection

- Mailbox auto-connects when `Rules::apply()` is called
- Two modes:
  - `apply()`: Process existing messages once
  - `watch()`: IMAP IDLE mode for real-time processing

### Actions and Callbacks

- Actions are invoked using `Zenstruck\Callback`
- Automatic parameter injection based on type hints
- Available parameters: `Message`, `LoggerInterface`

### Service Loading

- Services auto-loaded from `src/` except:
  - `src/Action/` (actions are not services)
  - `src/functions.php` and `src/services.php` (config files)

## CI/CD Pipeline

### GitHub Actions

Workflow: `.github/workflows/continuous-integration.yaml`

**Jobs:**
1. `coding-standards`: Runs `dagger call check-coding-standards`
2. `test`: Runs `dagger call test`

Both use Dagger for GitHub v8.0.0.

### Dagger Module

Located in `.dagger/src/PhpProject.php`

**Functions:**
- `check-coding-standards`: Runs ECS in PHP 8.3 container
- `test`: Runs PHPUnit in PHP 8.3 container
- Both use `composer:2` to install vendors first

## Git Commit Conventions

- **Format**: Conventional Commits (via commitlint)
- **Config**: `.commitlintrc.ts` extends `@commitlint/config-conventional`
- **Enforcement**: Pre-commit hook checks commit messages
- **Types**: feat, fix, docs, style, refactor, test, chore, etc.

### Pre-commit Hooks

Configured in `.pre-commit-config.yaml`:
- Trailing whitespace removal
- End-of-file fixer
- YAML validation
- Large file check
- Commitlint message validation

## Dependencies

### Runtime Dependencies

- `php`: ^8.3
- `directorytree/imapengine`: IMAP client library
- `monolog/monolog`: Logging
- `symfony/config`: Configuration system
- `symfony/console`: CLI framework
- `symfony/dependency-injection`: DI container
- `symfony/runtime`: Application runtime
- `zenstruck/callback`: Callback parameter injection

### Development Dependencies

- `phpstan/phpstan`: Static analysis
- `phpunit/phpunit`: Testing framework (v12.1+)
- `symplify/easy-coding-standard`: Code style checking
- `rector/rector`: Automated refactoring and PHP upgrades
- `symfony/var-dumper`: Debugging

## Adding New Features

### Adding a New Action

1. Create class in `src/Action/` implementing `Action` interface
2. Add type-hinted constructor/methods for any dependencies
3. Use `#[\SensitiveParameter]` for sensitive data
4. Make class `final readonly` if possible
5. Optional: Use `WithCondition` trait for conditional execution

Example:
```php
final readonly class MyAction implements Action
{
    public function __invoke(Message $message, LoggerInterface $logger): void
    {
        // Action logic
    }
}
```

### Adding a New Console Command

1. Create class in `src/Console/`
2. Extend `Symfony\Component\Console\Command\Command`
3. Add `#[AsCommand(name: "command-name")]` attribute
4. Register in `src/services.php` via Application's `add()` call
5. Autowiring will handle dependency injection

### Modifying Rules

- Edit `rules.php` (or create new config file)
- Rules receive `Message` objects from DirectoryTree\ImapEngine
- Return `Action` instances or generators that yield actions
- Use static closures where possible for performance

## Security Considerations

- **Credentials**: Never commit MAILBOX_DSN or credentials
- **Sensitive Parameters**: Always use `#[\SensitiveParameter]` attribute
- **Logging**: Monolog processors strip sensitive data via PSR log message processor
- **DSN Handling**: Credentials in DSN are marked sensitive and won't appear in stack traces

## Performance Notes

- Memory limit: 250M (set in `bin/mailbox-rules`)
- IDLE mode (`watch()`) keeps connection open for real-time processing
- Batch mode (`apply()`) processes all messages then exits
- Composer: Optimized autoloader enabled in production

## Additional Context

- `.envrc`: Uses direnv for environment management
- `.editorconfig`: Editor configuration for consistent formatting
- `.phpactor.json`: Phpactor LSP configuration (present but content not specified)
- License: MIT
- Author: aegypius (git@aegypius.com)
