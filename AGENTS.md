See @CLAUDE.md

## MCP Server

Use the `symfony-ai-mate` MCP server for this project.

- Server name: `symfony-ai-mate`
- Config files: `mcp.json` and `.ai/mcp/mcp.json`
- Runtime: Docker Compose (`./compose.yml` + `./compose.override.yml`)
- Local development startup rule: whenever you start the site for development, also run `make mate-serve`.

## Rules

- Never change code in `vendor/` or `node_modules/`.

<!-- BEGIN AI_MATE_INSTRUCTIONS -->
AI Mate Summary:
- Role: MCP-powered, project-aware coding guidance and tools.
- Required action: Read and follow `mate/AGENT_INSTRUCTIONS.md` before taking any action in this project, and prefer MCP tools over raw CLI commands whenever possible.
- Installed extensions: sylius/sylius-mate-extension, symfony/ai-mate, symfony/ai-symfony-mate-extension.
<!-- END AI_MATE_INSTRUCTIONS -->
