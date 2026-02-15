See @CLAUDE.md

## MCP Server

Use the `symfony-ai-mate` MCP server for this project.

- Server name: `symfony-ai-mate`
- Config files: `mcp.json` and `.ai/mcp/mcp.json`
- Runtime: Docker Compose (`./compose.yml` + `./compose.override.yml`)
- Local development startup rule: whenever you start the site for development, also run `make mate-serve`.

## Rules

- Never change code in `vendor/` or `node_modules/`.
