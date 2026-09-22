# SDLC artefacts

Feature work in this repo follows the AI-Native SDLC flow: **intent → spec → plan → build**.
Each stage has a skill that produces one file here, and the next stage reads it.

```
 /sdlc-intent <topic>        →  docs/sdlc/intent/<slug>.md   (problem, outcome, constraints)
        │
 /sdlc-spec  intent/<slug>.md →  docs/sdlc/specs/<slug>.md    (requirements, design, flagged concerns)
        │
 /sdlc-plan  specs/<slug>.md  →  docs/sdlc/plans/<slug>.md    (files, order, risks, proof)
        │
      build (main session, one plan step at a time; `ai-reviewer` before commit)
```

| Folder | Produced by | Contains |
|---|---|---|
| `intent/` | `/sdlc-intent` | one file per idea; what and why, not how |
| `specs/` | `/sdlc-spec` | requirements + design; ends with **Flagged concerns** |
| `plans/` | `/sdlc-plan` | ordered implementation steps with verification |
| `adr/` | you / `architect` | Architecture Decision Records (MADR style) |

Each folder holds a `TEMPLATE.md`; the skills copy it and fill it in. Commit the artefacts with the
code they describe so the reasoning survives the pull request.
