# Database policy

A migration that works locally has proven nothing about production. Local has no
traffic, no data volume, and no second application version running at the same
time.

## Analyse before writing

- **Backward compatibility** — can the currently deployed application still run
  against the new schema? It will, for the length of the deploy.
- **Forward compatibility** — can the new application run against the old schema?
  It will, if the deploy is ordered the other way.
- **Deployment ordering** — schema first or code first? Say which, and why.
- **Locks** — what does this statement lock, and for how long? On which engine
  and version?
- **Table size** — how many rows in production, not in the test fixture?
- **Duration** — an estimate, and what happens if it exceeds the deploy timeout.
- **Rollback** — can it be undone? A dropped column cannot.
- **Existing data** — what does this do to rows that already violate the new
  assumption?

## The expand/contract shape

Prefer three deploys over one:

1. **expand** — add the new column or table, nullable, written by the new code
   and ignored by the old;
2. **migrate** — backfill in batches, outside the deploy;
3. **contract** — remove the old column, once nothing reads it.

## Destructive operations

`DROP`, `TRUNCATE`, a `DELETE` without a bounded `WHERE`, a type change that
loses precision, a `NOT NULL` on a populated column: these need explicit human
approval that names the operation, recorded in the task. An agent proposes them;
it never runs them.

## Project specifics

<!-- The migration tool and its commands, where migrations live, how they run in
     each environment, who runs them, and the tables that are large enough for
     locks to matter. -->
