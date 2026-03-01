# Implementation Tasks - Red-Green-Refactor

This document breaks down the Email Rule Matching Specification into small, testable tasks following Test-Driven Development (TDD) principles.

## Phase 1: Core Matcher Infrastructure ✅

### Task 1.1: Create Matcher Interface ✅
**Red:**
- Write test for `Matcher` interface with `matches(Message): bool` method
- Test should verify a concrete matcher implementation

**Green:**
- Create `src/Matcher/Matcher.php` interface
- Implement minimal interface with `matches()` method

**Refactor:**
- Add PHPDoc annotations
- Ensure proper namespace structure

---

### Task 1.2: Implement `any()` Matcher ✅
**Red:**
- Write test: `any()` matcher should match all messages
- Test with various message types

**Green:**
- Create `src/Matcher/AnyMatcher.php`
- Implement `matches()` to always return `true`
- Create global `any()` helper function in `src/functions.php`

**Refactor:**
- Optimize implementation if needed
- Add documentation

---

### Task 1.3: Implement Pattern Matching Utility ✅
**Red:**
- Write tests for pattern matching:
  - Exact match: `"user@example.com"`
  - Wildcard: `"*@example.com"`, `"user@*"`, `"*test*"`
  - Regex: `/pattern/i`

**Green:**
- Create `src/Matcher/PatternMatcher.php` (or utility class)
- Implement pattern matching logic
- Handle all three pattern types

**Refactor:**
- Extract pattern parsing logic
- Optimize wildcard to regex conversion
- Add edge case handling

---

## Phase 2: Enhanced Rule Function ✅

### Task 2.1: Update `rule()` Function Signature ✅
**Red:**
- Write tests for new `rule()` signatures:
  - `rule(name, when: matcher, then: callable)`
  - `rule(name, callback)` (backward compatibility)
  - Verify both signatures create valid `Rule` objects

**Green:**
- Modify `rule()` function in `src/functions.php`
- Support both old and new signatures
- Create appropriate `Rule` object

**Refactor:**
- Clean up parameter handling
- Add type hints and documentation

---

### Task 2.2: Update `Rule` Model ✅
**Red:**
- Write tests for `Rule` executing with matcher:
  - Rule matches and executes actions
  - Rule doesn't match and skips actions
  - Rule with `any()` always executes

**Green:**
- Modify `src/Model/Rule.php`
- Add matcher evaluation logic
- Integrate matcher into rule execution

**Refactor:**
- Ensure backward compatibility
- Clean up rule execution flow

---

## Phase 3: Basic Matchers ✅

### Task 3.1: Implement `from()` Matcher ✅
**Red:**
- Write tests for `from()` matcher:
  - Exact email match
  - Wildcard domain: `"*@chaosium.com"`
  - Wildcard user: `"admin@*"`
  - Regex pattern
  - Null/empty sender handling

**Green:**
- Create `src/Matcher/FromMatcher.php`
- Implement using pattern matching utility
- Create global `from()` helper function

**Refactor:**
- Clean up code
- Ensure consistent error handling

---

### Task 3.2: Implement `to()` Matcher ✅
**Red:**
- Write tests for `to()` matcher:
  - Single recipient match
  - Multiple recipients (any match)
  - Pattern matching (exact, wildcard, regex)

**Green:**
- Create `src/Matcher/ToMatcher.php`
- Handle array of recipients
- Create global `to()` helper function

**Refactor:**
- Share logic with `from()` if applicable
- Optimize recipient iteration

---

### Task 3.3: Implement `subject()` Matcher ✅
**Red:**
- Write tests for `subject()` matcher:
  - Exact match
  - Wildcard patterns: `"*[Important]*"`
  - Regex patterns
  - Null/empty subject handling

**Green:**
- Create `src/Matcher/SubjectMatcher.php`
- Implement using pattern matching utility
- Create global `subject()` helper function

**Refactor:**
- Consistent null handling across matchers

---

## Phase 4: Logical Combinators ✅

### Task 4.1: Implement `allOf()` Combinator (AND) ✅
**Red:**
- Write tests for `allOf()`:
  - Empty matchers (should match all?)
  - Single matcher
  - Multiple matchers all matching
  - Multiple matchers with one failing

**Green:**
- Create `src/Matcher/AllOfMatcher.php`
- Implement AND logic (all must match)
- Create global `allOf()` helper function

**Refactor:**
- Optimize short-circuit evaluation
- Handle edge cases

---

### Task 4.2: Implement `anyOf()` Combinator (OR) ✅
**Red:**
- Write tests for `anyOf()`:
  - Empty matchers (should match none?)
  - Single matcher
  - Multiple matchers with at least one matching
  - All matchers failing

**Green:**
- Create `src/Matcher/AnyOfMatcher.php`
- Implement OR logic (at least one must match)
- Create global `anyOf()` helper function

**Refactor:**
- Optimize short-circuit evaluation

---

### Task 4.3: Implement `not()` Combinator (NOT) ✅
**Red:**
- Write tests for `not()`:
  - Negating a matching matcher
  - Negating a non-matching matcher
  - Double negation

**Green:**
- Create `src/Matcher/NotMatcher.php`
- Implement negation logic
- Create global `not()` helper function

**Refactor:**
- Ensure consistent behavior

---

## Phase 5: New Actions ✅

### Task 5.1: Implement `MoveToFolder` Action ✅
**Red:**
- Write tests for `MoveToFolder`:
  - Move message to specified folder
  - Handle invalid folder names
  - Verify IMAP move operation

**Green:**
- Create `src/Action/MoveToFolder.php`
- Implement `__invoke()` with folder move logic
- Use `Message` API for moving

**Refactor:**
- Error handling
- Add logging if appropriate

---

### Task 5.2: Implement `MarkAsRead` Action ✅
**Red:**
- Write tests for `MarkAsRead`:
  - Mark message as read
  - Verify flag is set

**Green:**
- Create `src/Action/MarkAsRead.php`
- Implement using IMAP flag operations

**Refactor:**
- Consistent action structure

---

### Task 5.3: Implement `Flag` Action ✅
**Red:**
- Write tests for `Flag`:
  - Set custom flag on message
  - Handle various flag types

**Green:**
- Create `src/Action/Flag.php`
- Implement flag setting logic

**Refactor:**
- Validate flag names

---

## Phase 6: Helper Functions ✅

### Task 6.1: Implement `chain()` Helper ✅
**Red:**
- Write tests for `chain()`:
  - Returns iterable of actions
  - Actions yielded in order
  - Works with variable number of actions

**Green:**
- Create `chain()` function in `src/functions.php`
- Implement: `yield` each action from variadic parameter

**Refactor:**
- Optimize if needed
- Add documentation

---

### Task 6.2: Implement `env()` Helper ✅
**Red:**
- Write tests for `env()`:
  - Returns value when variable exists
  - Throws exception when undefined
  - Throws exception when empty
  - Preserves whitespace

**Green:**
- Create `env()` function in `src/functions.php`
- Implement: validate and return environment variable

**Refactor:**
- Add descriptive error messages
- Add documentation

---

## Phase 7: Integration & Documentation ✅

### Task 7.1: Integration Tests ✅
**Red:**
- Write end-to-end tests:
  - Complete rule configuration
  - Multiple rules with different matchers
  - Complex matcher combinations
  - Verify actions execute in order

**Green:**
- Ensure all components work together
- Fix any integration issues

**Refactor:**
- Clean up test helpers
- Add test utilities

---

### Task 7.2: Update `rules.php` Example ✅
**Red:**
- Create test that loads and validates `rules.php`

**Green:**
- Update `rules.php` with new syntax:
  ```php
  rule(
      name: "Chaosium to Promotions",
      when: from("*@chaosium.com"),
      then: static fn(Message $message) => yield new MoveToFolder("Promotions")
  )
  ```

**Refactor:**
- Add more example rules
- Document patterns

---

### Task 7.3: Update Documentation ✅
- Update README with new syntax
- Add migration guide for existing rules
- Document all available matchers
- Document pattern matching syntax
- Add troubleshooting section

---

## Phase 8: Additional Matchers ✅

### Task 8.1: Implement Date/Time Matchers ✅
**Red:**
- Write tests for `receivedAfter()` matcher:
  - Match messages received after specific date/time
  - Support for Carbon instances and date strings
  - Edge cases with timezone handling
- Write tests for `receivedBefore()` matcher:
  - Match messages received before specific date/time
  - Same carbon/string support

**Green:**
- Create `src/Matcher/ReceivedAfterMatcher.php`
- Create `src/Matcher/ReceivedBeforeMatcher.php`
- Implement using `Message->date()` (Carbon instance)
- Create global `receivedAfter()` and `receivedBefore()` helper functions

**Refactor:**
- Consistent date handling across matchers
- Add documentation and examples

---

### Task 8.2: Implement Size Matchers ✅
**Red:**
- Write tests for `largerThan()` matcher:
  - Match messages larger than byte size
  - Handle various message sizes
- Write tests for `smallerThan()` matcher:
  - Match messages smaller than byte size
  - Edge cases (zero size, null size)

**Green:**
- Create `src/Matcher/LargerThanMatcher.php`
- Create `src/Matcher/SmallerThanMatcher.php`
- Implement using `Message->size()` (bytes)
- Create global `largerThan()` and `smallerThan()` helper functions

**Refactor:**
- Validate size parameters (non-negative)
- Add documentation

---

### Task 8.3: Implement Attachment Matchers ✅
**Red:**
- Write tests for `hasAttachment()` matcher:
  - Match messages with any attachment
  - Match messages without attachments
- Write tests for `attachmentType()` matcher:
  - MIME type matching: "image/jpeg", "image/*"
  - Extension matching: ".pdf", "*.doc"
  - Multiple attachments (any match)

**Green:**
- Create `src/Matcher/HasAttachmentMatcher.php`
- Create `src/Matcher/AttachmentTypeMatcher.php`
- Implement using `Message->hasAttachments()` and `Message->attachments()`
- Create global `hasAttachment()` and `attachmentType()` helper functions

**Refactor:**
- Pattern matching for MIME types and extensions
- Handle null filename cases

---

### Task 8.4: Implement CC/BCC/Recipient Matchers ✅
**Red:**
- Write tests for `cc()` matcher:
  - Match CC recipients with pattern
  - Wildcard and regex support
  - Multiple CC recipients (any match)
- Write tests for `bcc()` matcher:
  - Same as cc() for BCC field
- Write tests for `recipient()` matcher:
  - Match any recipient (To, CC, or BCC)
  - Check all three fields

**Green:**
- Create `src/Matcher/CcMatcher.php`
- Create `src/Matcher/BccMatcher.php`
- Create `src/Matcher/RecipientMatcher.php`
- Implement using `Message->cc()`, `Message->bcc()`
- Create global `cc()`, `bcc()`, and `recipient()` helper functions

**Refactor:**
- Consistent pattern matching with `to()` and `from()`
- Optimize recipient iteration

---

### Task 8.5: Implement Body Content Matcher ✅
**Red:**
- Write tests for `body()` matcher:
  - Match plain text body content
  - Match HTML body content
  - Wildcard patterns: "*invoice*"
  - Regex patterns: "/\d{6}/"
  - Case-insensitive matching
  - Null body handling

**Green:**
- Create `src/Matcher/BodyMatcher.php`
- Implement using `Message->text()` and `Message->html()`
- Check both text and HTML body
- Create global `body()` helper function

**Refactor:**
- Consistent pattern matching behavior
- Document wildcard requirement for substring matching

---

## Phase 9: Advanced Actions ✅

### Task 9.1: Implement CopyToFolder Action ✅
**Red:**
- Write tests for `CopyToFolder`:
  - Copy message to target folder
  - Original message remains in source folder
  - Support different folder names

**Green:**
- Create `src/Action/CopyToFolder.php`
- Implement using `Message->copy(folder)`
- Requires IMAP UIDPLUS capability
- Create global helper function (if needed)

**Refactor:**
- Error handling for unsupported UIDPLUS
- Documentation and examples

---

### Task 9.2: Implement Delete and MoveToTrash Actions ✅
**Red:**
- Write tests for `Delete`:
  - Permanent message deletion
  - Test with/without immediate expunge
  - Safety warning in documentation
- Write tests for `MoveToTrash`:
  - Move to trash folder (default: "Trash")
  - Support custom trash folder names
  - Test different provider conventions (Gmail, Exchange)

**Green:**
- Create `src/Action/Delete.php`
  - Uses `Message->delete(expunge)`
  - Default: `expunge = true` (immediate permanent deletion)
- Create `src/Action/MoveToTrash.php`
  - Uses `Message->move(trashFolder, expunge)`
  - Default: `trashFolder = "Trash"`, `expunge = false`
  - Configurable for different providers

**Refactor:**
- Safety warnings for Delete action
- Document trash folder naming conventions
- Add examples for different email providers

---

### Task 9.3: Implement MarkAsUnread and Unflag Actions ✅
**Red:**
- Write tests for `MarkAsUnread`:
  - Remove \Seen flag from message
  - Opposite of MarkAsRead
- Write tests for `Unflag`:
  - Remove \Flagged flag from message
  - Opposite of Flag

**Green:**
- Create `src/Action/MarkAsUnread.php`
  - Uses `Message->markUnread()`
  - Removes \Seen flag
- Create `src/Action/Unflag.php`
  - Uses `Message->unmarkFlagged()`
  - Removes \Flagged flag

**Refactor:**
- Consistent with existing flag actions
- Documentation and examples

---

## Future Enhancements

The following features are documented in `SPECIFICATION.md` but not yet implemented:

### Phase 10: Advanced Features (Not Implemented)

#### Task 10.1: Implement Dry-Run Mode
- `preview()` - Test rules without applying actions
- Show what would happen for each message
- Useful for debugging rule configurations

#### Task 10.2: Implement Rule Priorities
- Add priority/ordering to rules
- Allow short-circuit on first match
- Stop processing after specific rule matches

#### Task 10.3: Implement Rule Conditions
- Add pre-conditions to rules (e.g., only run on weekdays)
- Add post-conditions (e.g., verify action succeeded)

---

### Phase 11: Protocol Abstraction (Not Implemented)

#### Task 11.1: Create Message Abstraction Layer
**Purpose:** Decouple matchers and actions from IMAP-specific Message implementation

**Design Goals:**
- Protocol-agnostic message interface
- Support both IMAP (current) and JMAP (future)
- Maintain backward compatibility with existing code
- Zero-cost abstraction (no performance penalty)

**Red:**
- Write tests for abstract `MessageInterface`:
  - Test all message property accessors (from, to, cc, bcc, subject, date, size)
  - Test body content access (text, html)
  - Test attachment operations
  - Mock both IMAP and JMAP implementations
- Write tests for `ImapMessageAdapter`:
  - Wraps existing `DirectoryTree\ImapEngine\Message`
  - Implements `MessageInterface`
  - No behavior changes from current implementation

**Green:**
- Create `src/Contract/MessageInterface.php`:
  ```php
  interface MessageInterface {
    public function from(): ?Address;
    public function to(): array<Address>;
    public function cc(): array<Address>;
    public function bcc(): array<Address>;
    public function subject(): ?string;
    public function date(): CarbonInterface;
    public function size(): int;
    public function text(): ?string;
    public function html(): ?string;
    public function hasAttachments(): bool;
    public function attachments(): array<AttachmentInterface>;
    // Action methods
    public function move(string $folder): void;
    public function copy(string $folder): void;
    public function flag(string $flag): void;
    public function unflag(string $flag): void;
    public function markAsRead(): void;
    public function markAsUnread(): void;
    public function delete(): void;
  }
  ```
- Create `src/Adapter/ImapMessageAdapter.php`:
  - Wraps `DirectoryTree\ImapEngine\Message`
  - Delegates all calls to wrapped instance
  - No additional logic
- Update all matchers to use `MessageInterface` instead of concrete `Message` class
- Update all actions to use `MessageInterface`

**Refactor:**
- Ensure all tests pass with adapter
- Document adapter pattern
- Add migration guide for custom extensions

---

#### Task 11.2: Create Attachment Abstraction Layer
**Purpose:** Decouple attachment handling from IMAP-specific implementation

**Red:**
- Write tests for abstract `AttachmentInterface`:
  - Content type accessor
  - Filename accessor
  - Extension accessor
  - Size accessor
  - Content retrieval
- Write tests for `ImapAttachmentAdapter`:
  - Wraps existing `DirectoryTree\ImapEngine\Attachment`
  - Implements `AttachmentInterface`

**Green:**
- Create `src/Contract/AttachmentInterface.php`:
  ```php
  interface AttachmentInterface {
    public function contentType(): string;
    public function filename(): ?string;
    public function extension(): ?string;
    public function size(): int;
    public function content(): string;
  }
  ```
- Create `src/Adapter/ImapAttachmentAdapter.php`
- Update `AttachmentTypeMatcher` to use `AttachmentInterface`
- Update `MessageInterface->attachments()` to return `array<AttachmentInterface>`

**Refactor:**
- Ensure type consistency
- Update documentation

---

#### Task 11.3: Implement JMAP Support
**Purpose:** Add JMAP protocol support alongside existing IMAP support

**Prerequisites:**
- Task 11.1 and 11.2 completed (abstraction layer in place)
- Choose JMAP client library (research required)

**Red:**
- Write tests for `JmapMessageAdapter`:
  - Implements `MessageInterface`
  - Maps JMAP message properties to interface
  - Handles JMAP-specific quirks
- Write tests for `JmapAttachmentAdapter`:
  - Implements `AttachmentInterface`
  - Maps JMAP attachment properties
- Write tests for `JmapMailboxFactory`:
  - Creates mailbox connection using JMAP
  - Similar API to existing `MailboxFactory`

**Green:**
- Research and add JMAP client library dependency
  - Evaluate: `jmap-client/jmap-client`, custom implementation, or other
- Create `src/Adapter/JmapMessageAdapter.php`
- Create `src/Adapter/JmapAttachmentAdapter.php`
- Create `src/JmapMailboxFactory.php`
- Update DSN parsing to support `jmap://` scheme:
  - `jmap://user:token@example.com`
  - JMAP uses tokens, not passwords
- Implement JMAP action methods (move, flag, delete, etc.)

**Refactor:**
- Abstract common mailbox operations
- Document JMAP-specific configuration
- Add JMAP examples to documentation
- Performance testing (JMAP should be faster than IMAP)

**Benefits:**
- Modern API (JMAP is stateless, JSON-based)
- Better performance (batch operations, less chattiness)
- Better mobile/web support
- Future-proof protocol

**Migration Path:**
1. Phase 11.1-11.2: Add abstraction layer (no breaking changes)
2. Phase 11.3: Add JMAP support (opt-in)
3. Users can choose protocol via DSN: `imap://` vs `jmap://`
4. All existing rules work unchanged with either protocol

---

### Phase 12: Performance & Optimization (Not Implemented)

#### Task 12.1: Implement Matcher Caching
- Cache compiled regex patterns in PatternMatcher
- Cache message property access (subject, from, to)
- Benchmark performance improvements

#### Task 12.2: Implement Batch Operations
- Process multiple messages in parallel
- Batch IMAP/JMAP commands
- Configurable concurrency limits

#### Task 12.3: Implement Rule Statistics
- Track rule execution counts
- Track matcher performance
- Track action success/failure rates
- Export metrics for monitoring

---

## Testing Strategy

### Unit Tests
- Each matcher in isolation
- Pattern matching utility
- Logical combinators
- Action classes
- Helper functions

### Integration Tests
- Complete rule configurations
- Matcher + action combinations
- Multiple rules processing
- Backward compatibility

### Edge Cases to Test
- Null/empty message fields
- Invalid patterns
- Empty matcher lists in combinators
- Non-existent folders in actions
- Special characters in patterns
- Case sensitivity

---

## Implementation Summary

### Completed (Phases 1-8)
- ✅ 25 tasks completed (20 core + 5 additional matchers)
- ✅ 225 tests, 267 assertions (100% passing)
- ✅ Core DSL fully functional
- ✅ Pattern matching (exact, wildcard, regex)
- ✅ Basic matchers: any(), from(), to(), subject()
- ✅ Date/time matchers: receivedAfter(), receivedBefore()
- ✅ Size matchers: largerThan(), smallerThan()
- ✅ Attachment matchers: hasAttachment(), attachmentType()
- ✅ Recipient matchers: cc(), bcc(), recipient()
- ✅ Body content matcher: body()
- ✅ Logical combinators: allOf(), anyOf(), not()
- ✅ Actions: MoveToFolder, MarkAsRead, Flag
- ✅ Helper functions: chain(), env()
- ✅ Integration tests and documentation
- ✅ Backward compatibility maintained

### Not Implemented (Future Enhancements)

**Phase 9: Advanced Actions**
- ❌ Copy action: CopyToFolder()
- ❌ Delete/trash actions: Delete(), MoveToTrash()
- ❌ Mark as unread/unflag: MarkAsUnread(), Unflag()

**Phase 10: Advanced Features**
- ❌ Dry-run/preview mode
- ❌ Rule priorities and short-circuiting
- ❌ Rule pre/post conditions

**Phase 11: Protocol Abstraction & JMAP Support**
- ❌ MessageInterface abstraction layer
- ❌ AttachmentInterface abstraction layer
- ❌ ImapMessageAdapter and ImapAttachmentAdapter
- ❌ JMAP protocol support (JmapMessageAdapter, JmapAttachmentAdapter)
- ❌ Multi-protocol support via DSN (imap:// and jmap://)

**Phase 12: Performance & Optimization**
- ❌ Matcher caching (regex patterns, message properties)
- ❌ Batch operations and parallel processing
- ❌ Rule execution statistics and metrics

---

## Notes

- Each task should take 15-30 minutes
- Run full test suite after each green phase
- Commit after each refactor phase
- Keep changes small and focused
- Maintain backward compatibility throughout
