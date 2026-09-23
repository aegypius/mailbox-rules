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
