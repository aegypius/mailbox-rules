# Mailbox Rules

Declarative rules that sort, flag and clean up messages in IMAP mailboxes.

## Language

**Mailbox**:
A connection to one mail account plus the rules that apply to it.

**Rule**:
A named pair of one matcher and the actions to run on messages it matches.

**Matcher**:
A yes/no condition on a single message; matchers compose with `allOf`, `anyOf`, `not`.
_Avoid_: Filter, condition, predicate

**Action**:
An operation applied to a matched message (move, flag, mark read, delete…).

**Folder**:
A named container of messages inside a Mailbox, addressed by its path (e.g. `Promotions/Steam`).
_Avoid_: Label, directory

**Trash**:
The Folder the server designates for deleted messages (special-use `\Trash`), whatever its name.
_Avoid_: Deleted Items, Bin

**Folder scope**:
The set of Folders a Rule runs in. It defaults to INBOX; `folder()` patterns expand to every matching Folder, and composites combine scopes as sets (`allOf` intersects, `anyOf` unites, `not` subtracts from the default).

### Run modes

**Apply**:
One pass that runs every Rule over the messages already in its Folder scope, then exits.

**Preview**:
An Apply pass that reports which Actions would run, without running them.
_Avoid_: Dry run

**Watch**:
An Apply pass, then repeated passes at a fixed interval that run Rules only on messages that arrived since the previous pass.
_Avoid_: Idle, daemon
