See @CLAUDE.md

## Rules

- Never change code in `vendor/` or `node_modules/`.
- There is **no MCP server** configured for this project. The `symfony-ai-mate`
  server, its `mcp.json` / `.ai/mcp/mcp.json` configs and the `mate/` directory
  (config, extensions, agent instructions) were removed on 2026-07-25 — use the
  plain CLI and Docker commands documented in `CLAUDE.md` instead.
- The `sylius/sylius-ai-dev-tools` dev dependency is still installed, so
  `composer install` prints an "AI Mate installed! Run `vendor/bin/mate init`"
  banner. It is a banner only — nothing is generated unless `mate init` is run
  deliberately, which is also how `mate/` would come back if it is ever wanted.
