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

## Phase 2: Enhanced Rule Function

### Task 2.1: Update `rule()` Function Signature
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

### Task 2.2: Update `Rule` Model
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

## Phase 3: Basic Matchers

### Task 3.1: Implement `from()` Matcher
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

### Task 3.2: Implement `to()` Matcher
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

### Task 3.3: Implement `subject()` Matcher
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

## Phase 4: Logical Combinators

### Task 4.1: Implement `allOf()` Combinator (AND)
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

### Task 4.2: Implement `anyOf()` Combinator (OR)
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

### Task 4.3: Implement `not()` Combinator (NOT)
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

## Phase 5: New Actions

### Task 5.1: Implement `MoveToFolder` Action
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

### Task 5.2: Implement `MarkAsRead` Action
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

### Task 5.3: Implement `Flag` Action
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

## Phase 6: Helper Function

### Task 6.1: Implement `chain()` Helper
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

## Phase 7: Integration & Documentation

### Task 7.1: Integration Tests
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

### Task 7.2: Update `rules.php` Example
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

### Task 7.3: Update Documentation
- Update README with new syntax
- Add migration guide for existing rules
- Document all available matchers
- Document pattern matching syntax
- Add troubleshooting section

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

## Notes

- Each task should take 15-30 minutes
- Run full test suite after each green phase
- Commit after each refactor phase
- Keep changes small and focused
- Maintain backward compatibility throughout
