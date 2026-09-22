# Project memory

Project-scoped memory for the agent runtime this directory belongs to —
`.claude/memory/` for Claude Code, `.codex/memory/` for Codex. One fact per file,
same frontmatter as the global memory:

```markdown
---
name: <short-kebab-case-slug>
description: <one-line summary, used to decide relevance>
metadata:
  type: user | feedback | project | reference
---

<the fact; for feedback/project add **Why:** and **How to apply:** lines>
```

- Keep `MEMORY.md` as the index: one line per memory, no content.
- Put machine-local notes in `local/` (git-ignored).
- Do not record what the repo already says (code structure, git history, the
  project's `CLAUDE.md` or `AGENTS.md`).
