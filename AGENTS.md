See @CLAUDE.md

## Rules

- Never change code in `vendor/` or `node_modules/`.
- **Nothing runs on the host.** This machine has no PHP and no Node — every
  `php` / `composer` / `vendor/bin/*` / `yarn` / `node` invocation goes through
  `docker compose run --rm php …`, `docker compose exec …` or a `make` target.
  See `CLAUDE.md` → *AI tooling* for the full command table.
- The `symfony-ai-mate` MCP server **is** configured, over stdio, via
  `.claude/scripts/mate-mcp.sh` (referenced from the gitignored `mcp.json`,
  which `.mcp.json` symlinks to). The launcher `exec`s into the running `php`
  service — never `docker compose run`, which leaks a container per session.
  Regenerate the gitignored `mate/` tree with `make mate-init` /
  `make mate-discover`.

<!-- BEGIN AI_MATE_INSTRUCTIONS -->
AI Mate Summary:
- Role: MCP-powered, project-aware coding guidance and tools.
- Required action: Read and follow `mate/AGENT_INSTRUCTIONS.md` before taking any action in this project, and prefer MCP tools over raw CLI commands whenever possible.
- Installed extensions: sylius/sylius-mate-extension, symfony/ai-mate, symfony/ai-symfony-mate-extension.
<!-- END AI_MATE_INSTRUCTIONS -->
