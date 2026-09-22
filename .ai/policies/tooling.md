# Tooling and MCP policy

Every tool definition, every MCP server and every skill description is in the
system prompt of every turn, whether it is used or not. A connected MCP server
that nothing in this task calls is a permanent tax on a context that is re-read
on each turn — and a wider blast radius for a mistake.

**Default: off. Loaded on a named need, for the length of one task, then off
again.**

## The three layers

| Layer | What it decides | Where |
|---|---|---|
| machine | which MCP servers the runtime may start at all | `~/.claude/settings.json` · `~/.codex/config.toml` |
| project | which of them this repository needs | `.claude/settings.json` · `.mcp.json` · `.codex/config.toml` |
| task | which of them *this change* needs | the task record, in one line |

A server enabled at the project layer is enabled for every task in the
repository, so the project layer holds only what nearly every task needs. Give
it to a single task at the task layer instead.

## Project layer

The scaffolded `.claude/settings.json` ships with:

```json
{
  "enableAllProjectMcpServers": false,
  "enabledMcpjsonServers": [],
  "disabledMcpjsonServers": []
}
```

A `.mcp.json` checked into the repository is a *catalogue*, not an instruction
to start everything in it: `enableAllProjectMcpServers: false` keeps it that
way. Move a server into `enabledMcpjsonServers` only when it has earned it —
the project's own database, issue tracker or design system, used by most tasks
— and name the reason in this file. Everything else stays in
`disabledMcpjsonServers`, where it is one edit away, not one turn away.

List what this project actually enables, and why, under **Project specifics**
below. A server nobody can justify in one line is a server to disable.

## Task layer

When a task needs a tool the project does not enable by default, say so **once**,
at triage, in the task record:

```
tools_for_this_task: <server-or-tool> — <why> — <when it goes off again>
```

Enable it, do the work, disable it in the same task. A browser, a design tool
or a docs connector left running because it was handy last week is exactly the
cost this policy exists to remove.

Rules:

- Name the need before enabling, not after. "It might be useful" is not a need.
- One server per need. Do not enable a bundle to get one tool out of it.
- A task that does not touch the browser, the design system or external
  documentation runs with none of them.
- Deferred tools (loaded on demand by the runtime) are fetched in **one**
  batched call listing every tool the task will need — never one call per tool.
- If the runtime supports it, prefer starting a task with a restricted tool set
  (`--strict-mcp-config`, a per-project config) over disabling servers by hand
  afterwards.

## Agents

Each agent definition carries an explicit `tools:` allowlist, and it is the
minimum that role can work with — the readers get `Read, Grep, Glob`, the
reviewers get no `Edit` or `Write`, and only the implementer may write. This is
not a formality: a reviewer that can edit will eventually edit.

- Never widen an agent's tool list to unblock one task. Amend the step instead.
- A subagent inherits none of the main session's MCP servers by choice: give it
  the files it needs, not a connector it could use to go looking.
- An agent that needs a tool it does not have returns `SCOPE_CHANGE_REQUIRED`
  rather than working around the gap.

## Reading large files

A file that is too large to read whole is read by the **cheapest model**, never
by the session and never by a STRONG agent:

- `grep -n` first; read only the ranges that matched.
- Over ~4000 lines or ~250KB a hook refuses an unbounded `Read`. That is the
  signal to narrow, not to pass a larger limit.
- When the interesting part cannot be located with `grep` alone, send a FAST
  reader — `Explore` for code, `log-reader` for logs and test output — and take
  back **only the excerpt that matters**: the ranges, with `file:line`, and one
  line on why each one is there. Never the file, never the log.
- Whatever comes back is what enters the context. If a reader returns the whole
  file, that answer is discarded and the question is asked again, narrower.

The rest of the rules are in `context-management.md`; this is the part that is
about who reads, not about what is kept.

## Project specifics

<!-- Which MCP servers this repository enables and why, which ones are
     deliberately disabled, and any tool a task must ask for explicitly.
     One line each. -->
