# Git policy

`ai-git-guard` enforces the mechanical half of this file in every repository.
The rest is discipline.

## Refused mechanically

- force push, including `--force-with-lease`;
- deleting a remote branch;
- rewriting history (`filter-branch`, `filter-repo`, mirror push);
- pushing or merging directly into a protected branch (`main`, `master`,
  `production`, `release/*` by default);
- `gh pr merge`;
- `--no-verify` on commit or push;
- `git reset --hard` while a protected branch is checked out;
- staging a file that looks like a credential or a dump;
- running a production deployment command.

## Discipline

- One logical change per commit. A commit that needs "and" in its subject is two
  commits.
- The subject says what changed and why, in the imperative. The body says what a
  reviewer cannot see from the diff: the constraint, the ticket, the decision.
- Commit when the human asks. An agent does not commit on its own initiative,
  and it never commits a change the human has not seen.
- Never disable a hook, a test or a lint rule to get a commit through.
- Secrets never enter the history. A credential committed once stays in the
  history after it is deleted, and must be rotated instead.

## Branches

Work on a branch named for the task. Merge through a pull request with a human
approval. The agent may open the pull request and write its description; it does
not merge it.
