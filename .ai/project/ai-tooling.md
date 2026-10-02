## AI tooling

`.claude/`, `mcp.json` / `.mcp.json`, `mate/` and `CLAUDE.local.md` are
**gitignored local tooling**: functional on disk, absent from the repository.
Nothing below is required to build or test the plugin.

| Layer | What it is | Entry point |
|---|---|---|
| `symfony/ai-mate` CLI | Introspects the running Sylius kernel (`sylius/sylius-ai-dev-tools`); since 0.13 a CLI, no MCP server | `docker compose exec -T php vendor/bin/mate tools:list` / `tools:call <tool>`; usage in `mate/AGENT_INSTRUCTIONS.md` |
| `playwright` MCP | Browser driving inside the `playwright` container | `.claude/scripts/playwright-mcp.sh` |
| `sylius-dev` skill | Official Sylius skill (plugin `sylius-dev@sylius-ai-dev-skills`) | enabled in `.claude/settings.json` |
| `symfony-ux-skills` | The seven Symfony UX skills (stimulus, turbo, twig-component, live-component, ux-icons, ux-map, symfony-ux) | enabled at user scope |
| `sylius-quality` | Local skill + `sylius-reviewer` / `sylius-bc-guard` / `sylius-e2e-author` agents | `.claude/skills`, `.claude/agents` |
| Guard hooks | `vendor-guard`, `container-guard`, `bash-guard`, `assets-guard` | `.claude/hooks/`, wired in `.claude/settings.json` |
| Frontend mate tools | Local mate extension: `frontend_map` (grouped index + build-integrity checks) and `frontend_read` (grouped file bodies) over `assets/`, `templates/`, `src/Twig/`, `config/twig_hooks/`, `tests/e2e/` | `mate/src/` (`#[MateTool]` on `__invoke`), registered in `mate/config.php`; docs in `mate/INSTRUCTIONS.md` |

The `playwright` MCP launcher `docker compose exec`s into an already-running
service; never start it with `docker compose run`, which creates a container
per session (the old mate MCP launcher once leaked 22 `syliusslider-php-run-*`
containers that way). The mate CLI is invoked the same way:
`docker compose exec -T php vendor/bin/mate …`, set as `mate.invocation` in
`mate/config.php`.

Mate-managed skills (`mate skills:list`) are disabled in `mate/extensions.php`:
`sylius-dev` and `sylius-quality` already cover them, and installing them would
also create an untracked `.agents/skills/`.

Regenerate the mate tree with `make mate-init` / `make mate-discover` — also
after adding, removing or upgrading a mate extension (a `composer update` that
bumps one counts), since nothing does it automatically:
`symfony/ai-mate-composer-plugin` is installed (a dependency of
`symfony/ai-mate`) but disallowed in `composer.json` `config.allow-plugins`, so
`composer install`/`update` never run `mate discover`. Mate always writes an AI
Mate block into `AGENTS.md`, and both `mate init` and `mate discover` rewrite
`CLAUDE.md` unless it already contains the string `AGENTS.md` (today it does,
via `.ai/AGENTS.md`). Both targets snapshot the two files and restore them from
an EXIT trap, so success, failure and Ctrl-C all leave them untouched. Only a
bare `mate init`/`mate discover` still leaves the block behind.

## Adopted from AGENTS.md, lines 10-23

- Superseded 2026-09-22: symfony/ai-mate 0.13 removed the MCP server, so the
  `symfony-ai-mate` entry and `.claude/scripts/mate-mcp.sh` are gone. `mcp.json`
  and `.mcp.json` are two separate gitignored files, not a symlink; Claude Code
  reads `.mcp.json`. Regenerate the gitignored `mate/` tree with
  `make mate-init` / `make mate-discover`.

<!-- BEGIN AI_MATE_INSTRUCTIONS -->
AI Mate Summary:
- Role: project-aware coding guidance and tools, run through the mate CLI.
- Required action: Read and follow `mate/AGENT_INSTRUCTIONS.md` before taking any action in this project, and prefer `mate tools:call` over raw CLI commands whenever possible.
- Installed extensions: sylius/sylius-mate-extension, symfony/ai-mate, symfony/ai-symfony-mate-extension.
<!-- END AI_MATE_INSTRUCTIONS -->
