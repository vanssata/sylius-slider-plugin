## AI tooling

`.claude/`, `mcp.json` / `.mcp.json`, `mate/` and `CLAUDE.local.md` are
**gitignored local tooling**: functional on disk, absent from the repository.
Nothing below is required to build or test the plugin.

| Layer | What it is | Entry point |
|---|---|---|
| `symfony-ai-mate` MCP | Introspects the running Sylius kernel (`sylius/sylius-ai-dev-tools`) | `.claude/scripts/mate-mcp.sh`, referenced from `mcp.json` |
| `playwright` MCP | Browser driving inside the `playwright` container | `.claude/scripts/playwright-mcp.sh` |
| `sylius-dev` skill | Official Sylius skill (plugin `sylius-dev@sylius-ai-dev-skills`) | enabled in `.claude/settings.json` |
| `symfony-ux-skills` | The seven Symfony UX skills (stimulus, turbo, twig-component, live-component, ux-icons, ux-map, symfony-ux) | enabled at user scope |
| `sylius-quality` | Local skill + `sylius-reviewer` / `sylius-bc-guard` / `sylius-e2e-author` agents | `.claude/skills`, `.claude/agents` |
| Guard hooks | `vendor-guard`, `container-guard`, `bash-guard`, `assets-guard` | `.claude/hooks/`, wired in `.claude/settings.json` |
| Frontend mate tools | Local mate extension: `frontend_map` (grouped index + build-integrity checks) and `frontend_read` (grouped file bodies) over `assets/`, `templates/`, `src/Twig/`, `config/twig_hooks/`, `tests/e2e/` | `mate/src/`, registered in `mate/config.php`; docs in `mate/INSTRUCTIONS.md` |

Both MCP launchers `docker compose exec` into an already-running service. Never
start them with `docker compose run` and never pass `mate serve
--force-keep-alive`: that combination creates a container per session and keeps
the process alive after the client closes stdin, which is what once leaked 22
`syliusslider-php-run-*` containers.

Regenerate the mate tree with `make mate-init` / `make mate-discover`.

## Adopted from AGENTS.md, lines 10-23

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
