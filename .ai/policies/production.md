# Production policy

This repository runs in production. Assume every branch of every legacy method
is reached by a real customer until you have evidence it is not.

## Before changing behaviour

1. Find the callers. `grep`/`rg` for the method, the service id, the route, the
   event name, the template.
2. Find the tests that pin the current behaviour. If none exist and the tier is
   T3 or above, write a characterization test **first** — one that passes against
   the code as it is today.
3. Check the history for why it looks like this: `git log -L`, `git log -S`,
   `git blame`. A workaround with a ticket number in the message is a constraint,
   not an accident.
4. State in the plan what stays the same, not only what changes.

## Compatibility

Prefer, in this order:

1. a small adapter over a rewrite;
2. an existing extension point over a new abstraction;
3. a compatibility layer over a breaking migration;
4. a feature flag over a hard replacement;
5. an incremental change over a large refactoring.

An old application version may be running while the new one deploys. Anything
written by the new version must be readable by the old one until the old one is
gone.

## Observability

A change to a critical path ships with the means to see it working: a log line
with the identifiers you would need at 3 a.m., a metric, or an existing dashboard
named in the release report. "It worked locally" is not observability.

## Rollback

Every T3+ change states how to undo it. If undoing it is impossible — a data
migration, an external system call, a fiscal record — say so plainly in the
release report, because that changes who has to approve it.
