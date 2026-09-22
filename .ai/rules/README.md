# Directory rules

One file per slug, `<slug>.md`, holding the rules that apply to a part of this
repository and nowhere else. `/project-update` renders each of them into a
managed block in the instruction file of every directory it names, so an agent
working there reads them without anything being loaded for a task that never
goes near that code.

```markdown
---
dirs: [src/Payment, tests/Payment]      # required: where the rules apply
paths: ["src/Payment/**"]               # optional: also a path-scoped rules file
---
# Payment rules
- Amounts are integer minor units; never a float.
- A refund never writes to the order; it emits an event.
```

- `dirs:` is the list of directories whose `CLAUDE.md` / `AGENTS.md` receive the
  block `<!-- claude-agentic:rule:<slug>:start -->` … `:end`, created if absent.
  Text outside the markers is yours and is never touched.
- The body is what an agent reads: rules, not rationale. Keep it under a screen.
- This file is the format, not a rule: no rule ships with the plugin. Delete a
  `<slug>.md` and the next `/project-update` offers to remove what it rendered.
