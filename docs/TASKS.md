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

## Future Enhancements

The following features are documented in `SPECIFICATION.md` but not yet implemented:

### Phase 8: Additional Matchers (Not Implemented)

#### Task 8.1: Implement Date/Time Matchers
- `receivedAfter(date)` - Match messages received after a specific date/time
- `receivedBefore(date)` - Match messages received before a specific date/time
- Support for relative dates: "1 hour ago", "yesterday", "last week"

#### Task 8.2: Implement Size Matchers
- `largerThan(bytes)` - Match messages larger than a specific size
- `smallerThan(bytes)` - Match messages smaller than a specific size
- Support for human-readable sizes: "1MB", "500KB"

#### Task 8.3: Implement Attachment Matchers
- `hasAttachment()` - Match messages with any attachment
- `attachmentType(pattern)` - Match messages with specific attachment types
- Support for MIME types and file extensions

#### Task 8.4: Implement CC/BCC Matchers
- `cc(pattern)` - Match messages where CC includes pattern
- `bcc(pattern)` - Match messages where BCC includes pattern
- Same pattern matching support as `from()` and `to()`

#### Task 8.5: Implement Body Content Matcher
- `body(pattern)` - Match messages based on body content
- Support for plain text and HTML body searching
- Same pattern matching support (exact, wildcard, regex)

### Phase 9: Advanced Actions (Not Implemented)

#### Task 9.1: Implement Copy Action
- `CopyToFolder(folder)` - Copy message to folder (vs. move)
- Keep original message in place

#### Task 9.2: Implement Delete/Trash Actions
- `Delete()` - Permanently delete message
- `MoveToTrash()` - Move to trash folder
- Add safety confirmation for permanent deletes

#### Task 9.3: Implement Unflag/Unread Actions
- `MarkAsUnread()` - Remove read flag
- `Unflag()` - Remove flag from message

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

### Completed (Phases 1-7)
- ✅ 20 tasks completed
- ✅ 141 tests, 178 assertions (100% passing)
- ✅ Core DSL fully functional
- ✅ Pattern matching (exact, wildcard, regex)
- ✅ Basic matchers: any(), from(), to(), subject()
- ✅ Logical combinators: allOf(), anyOf(), not()
- ✅ Actions: MoveToFolder, MarkAsRead, Flag
- ✅ Helper functions: chain(), env()
- ✅ Integration tests and documentation
- ✅ Backward compatibility maintained

### Not Implemented (Future Enhancements)
- ❌ Date/time matchers: receivedAfter(), receivedBefore()
- ❌ Size matchers: largerThan(), smallerThan()
- ❌ Attachment matchers: hasAttachment(), attachmentType()
- ❌ Additional recipient matchers: cc(), bcc()
- ❌ Body content matcher: body()
- ❌ Copy action: CopyToFolder()
- ❌ Delete/trash actions: Delete(), MoveToTrash()
- ❌ Mark as unread/unflag: MarkAsUnread(), Unflag()
- ❌ Dry-run/preview mode
- ❌ Rule priorities and short-circuiting
- ❌ Rule pre/post conditions

---

## Notes

- Each task should take 15-30 minutes
- Run full test suite after each green phase
- Commit after each refactor phase
- Keep changes small and focused
- Maintain backward compatibility throughout
