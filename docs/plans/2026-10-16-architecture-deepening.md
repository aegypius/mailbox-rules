# Architecture deepening plan

Source: architecture review of 2026-10-16 (hot spots: `Rules`, `functions.php`, `ApplyCommand`, `Contract/*`). Every decision below was settled in a grilling session.
Vocabulary: module, interface, depth, seam, adapter, leverage, locality (see `/codebase-design`). Domain terms: `CONTEXT.md` (Mailbox, Rule, Matcher, Action, Folder, Trash, Folder scope, Apply, Preview, Watch).

Every step follows TDD (Red → Green → Refactor), and the suite must stay at exit 0 with no notices or risky tests (see `AGENTS.md`).

## Suspected bugs (read in code, not reproduced)

| # | Bug | Where | Fixed by |
|---|-----|-------|----------|
| B1 | `watch()` calls the blocking `idle()` inside a foreach, so only the first Folder is watched. `ApplyCommand` loops Mailboxes the same way, so only the first Mailbox is watched too. | `src/Model/Rules.php:185-215`, `src/Console/ApplyCommand.php:103-110` | Phase 2 |
| B2 | `copy()` creates only the leaf Folder, so `A/B` fails when `A` is missing. `move()` creates every parent. | `src/Adapter/ImapMessageAdapter.php:96-103` | Phase 0 |
| B3 | `stats()` has no production reader, so the stats feature never reaches the user. | `src/Model/Rules.php:31`, `src/Service/MailboxProcessor.php` | Phase 4 |
| B4 | `folder()` wildcard/regex patterns are passed raw to `folders()->find()`, which returns the first LIST match only. | `src/Helper/FolderExtractor.php`, `src/Model/Rules.php:158` | Phase 3 |
| B5 | `MoveToTrash` is `MoveToFolder('Trash')`, and move auto-creates missing Folders. On Gmail (`[Gmail]/Trash`) or Outlook (`Deleted Items`) that creates a stray `Trash` Folder. | `src/Action/MoveToTrash.php:23` | Phase 1 |

## Decisions

| # | Decision |
|---|----------|
| D1 | The domain glossary lives in `CONTEXT.md`. |
| D2 | The Mailbox seam is **our own port**, even though JMAP (the second production adapter) is blocked on `aegypius/jmap-php`. Its adapters are ImapEngine for production and in-memory for tests. No ADR. |
| D3 | The port owns **listing Folders and fetching messages only**: fetch the `MessageInterface`s in a Folder, optionally only those with a UID above a given one, and find the special-use `\Trash` Folder. Polling cadence and UID marks live above the port. |
| D4 | Watch is **a single poll loop** across all Mailboxes and Folders. IDLE is removed. |
| D5 | Watch starts with a full Apply pass, then runs Rules only on messages with a UID above the **highest one seen per Folder**. Those marks are kept in memory only, so a restart begins again with a full Apply pass. |
| D6 | A new `--interval` option sets the pass cadence (default 60s). The Folder list is re-read on every pass. |
| D7 | No guard against messages that Rules move back and forth between watched Folders. This is documented as a problem in the Rules, not in the engine. |
| D8 | A Rule with no `folder()` Matcher has INBOX as its Folder scope. |
| D9 | A `folder()` pattern expands against the full Folder list (filtered with `PatternMatcher`). |
| D10 | Composite Folder scopes follow set rules: `allOf` intersects, `anyOf` unites, and `not` subtracts from the default scope (INBOX). |
| D11 | `MoveToTrash` targets the special-use `\Trash` Folder (RFC 6154). An explicit Folder name overrides it. If there is no `\Trash` Folder it fails with an error and **never** creates one. |
| D12 | `MailboxProcessor` is **promoted** to the single entry point for Apply, Preview and Watch across all Mailboxes. It owns the poll loop and returns stats. `ApplyCommand` only parses options and formats output. |
| D13 | Stats: Apply prints a summary at the end. Watch prints one after each pass that matched something, and stays quiet otherwise. |
| D14 | `Rule::__invoke` returns `list<Action>`, so a Rule's Actions are replayable whichever way the Rule was built. |
| D15 | `rule()` **requires** a Matcher, and `Rule::$matcher` becomes non-nullable (**breaking**: use `any()`; commit as `feat!`). |
| D16 | The domain term is Preview. The CLI flag stays `--dry-run`. |

## Order

0. Fix `copy` so it creates parent Folders (B2)
1. Mailbox port with two adapters, plus Trash resolution (B5)
2. One traversal for Apply, Preview and Watch, with the poll loop (B1)
3. Matchers own their Folder scope (B4)
4. `MailboxProcessor` as the single entry point, plus stats output (B3)
5. Rule guarantees replayable Actions and a required Matcher. It is independent and can run any time.

---

## Phase 0 — `copy` creates parent Folders

**Dependency:** mock (IMAP is true external)
**Files:** `src/Adapter/ImapMessageAdapter.php:91-135`

**Steps:**
- [ ] Red: an adapter test where `copy('A/B')` runs with `A` missing expects both `A` and `A/B` to be created.
- [ ] Green: `copy` goes through `ensureFolderExists`, as `move` does.

**Done when:** the B2 test passes and `move` and `copy` share one folder-ensure policy.

---

## Phase 1 — Mailbox port with two adapters

**Dependency:** ports & adapters (D2, D3)
**Files:** `src/Model/Rules.php:7-8,167,177,211,252`, `src/MailboxFactoryInterface.php`, `src/Adapter/ImapMessageAdapter.php`, `src/Action/MoveToTrash.php`

**Problem:** `MessageInterface` hides the Message, but `Rules` still drives the concrete `Mailbox`, `FolderRepository` and `MessageQuery` types, and `MailboxFactoryInterface` returns the concrete `Mailbox`.

**Steps:**
- [ ] Define the port with three operations: list Folders, fetch the messages in a Folder (optional UID floor), and find `\Trash`.
- [ ] Red, then green: the in-memory adapter, tested through the port interface.
- [ ] Green: an ImapEngine adapter. Use `Folder::flags()` for special-use and `Message::uid()` for the UID floor.
- [ ] Have `MailboxFactoryInterface` return the port.
- [ ] Red: `MoveToTrash` with no argument moves the message to the `\Trash` Folder; with no `\Trash` Folder it fails and creates nothing (B5, D11).
- [ ] Port the `tests/Model/Rules*Test.php` files to the in-memory adapter, asserting outcomes. Delete the mock-chain tests they replace.

**Done when:** no `DirectoryTree\ImapEngine` import remains outside `src/Adapter/` or the factory, and the `Rules` tests use no IMAP mocks.

---

## Phase 2 — One traversal for Apply, Preview and Watch

**Dependency:** ports & adapters (sits on the Phase 1 port) · D4–D7
**Files:** `src/Model/Rules.php:137-279`, `src/Console/ApplyCommand.php:52-110`

**Problem:** three copies of "group Rules by Folder, resolve the Folder, run" have drifted:
- the `__INBOX__` decode and lookup is repeated 3 times;
- Preview skips a missing Folder silently;
- Preview records no stats;
- B1.

**Steps:**
- [ ] Red: a characterization test pins the current Apply and Preview results on a two-Folder rule set (in-memory adapter).
- [ ] Red: a missing Folder is reported identically in every mode.
- [ ] Red: Watch's first pass equals Apply. Later passes run Rules only on messages above each Folder's UID mark, across every Folder of every Mailbox (B1, D5).
- [ ] Red: a Folder created between passes and matching a `folder()` pattern is picked up on the next pass (D6).
- [ ] Green: extract the shared traversal. Each mode supplies only its per-message step.
- [ ] Green: add the `--interval` option (default 60s). Remove the IDLE path, and drop "(IMAP IDLE)" from the `--watch` help text.
- [ ] Docs: the README notes that Rules moving messages back and forth between watched Folders will loop (D7).
- [ ] Refactor: delete the three inline copies.

**Done when:** the decode and lookup exist once, every mode behaves the same, and B1 is fixed.

---

## Phase 3 — Matchers own their Folder scope

**Dependency:** in-process · D8–D10
**Files:** `src/Helper/FolderExtractor.php:19-111`, `src/Matcher/QueryAwareMatcher.php`, `src/ValueObject/FolderScope.php`

**Problem:** `FolderExtractor` makes the routing decisions outside the Matchers, reading them by reflection on the private `matchers` property. The decisions themselves are ad hoc: `allOf` takes the first Folder, and `not` falls back to INBOX. Patterns go raw to `find()` (B4). `QueryAwareMatcher` and `FolderScope` have no users in `src/`.

**Steps:**
- [ ] Red: Folder-scope tests through the Matcher interface, given a Folder list:
  - no `folder()` gives INBOX;
  - a pattern gives every matching Folder;
  - `allOf` intersects (and may be empty);
  - `anyOf` unites;
  - `not` gives INBOX minus the excluded Folders.
- [ ] Green: implement the scope on the Matchers. The traversal resolves it against each pass's Folder list.
- [ ] Delete `FolderExtractor`, `QueryAwareMatcher` and `FolderScope`.

**Done when:** no reflection remains in `src/` and B4 is fixed.

---

## Phase 4 — `MailboxProcessor` as the single entry point

**Dependency:** in-process · **Depends on:** Phase 2 · D12, D13
**Files:** `src/Service/MailboxProcessor.php:36-66`, `src/Model/StatsCollector.php`, `src/Console/ApplyCommand.php:80-147`

**Steps:**
- [ ] Red: Apply prints a stats summary (B3).
- [ ] Red: Watch prints a summary after a pass that matched, and nothing after an empty pass.
- [ ] Green: `MailboxProcessor` runs Apply, Preview or Watch across all Mailboxes, owns the poll loop and returns stats.
- [ ] Refactor: `ApplyCommand` keeps only option parsing, logger setup and output formatting.

**Done when:** `Rules` is constructed in one place and stats reach the output.

---

## Phase 5 — Rule guarantees replayable Actions and requires a Matcher

**Dependency:** in-process · D14, D15
**Files:** `src/Model/Rule.php`, `src/functions.php:60-106`, `src/Model/Rules.php:85-87,113-117`, `src/Loader/RuleFileLoader.php:65-78`, `tests/fixtures/test-rules.php`

**Steps:**
- [ ] Red: a Rule whose closure returns a generator gives the same Actions on two invocations.
- [ ] Green: `Rule::__invoke` returns `list<Action>`.
- [ ] Delete the WeakMap in `rule()`, the `iterator_to_array` call and the `'closed generator'` catch in `Rules`, and the materialization in `RuleFileLoader`.
- [ ] Red: `rule()` without `$when` fails.
- [ ] Green: make `$when` required and `Rule::$matcher` non-nullable. Update `tests/fixtures/test-rules.php` to use `any()`.
- [ ] Fold `tests/Model/GeneratorReuseTest.php` into the `Rule` tests.
- [ ] Commit as `feat!`, with a breaking-change note.

**Done when:** a single site normalizes the Actions, no exception message text is matched, and every Rule has a Matcher.
