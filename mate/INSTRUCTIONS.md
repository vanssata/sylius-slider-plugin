# SyliusSlider Project Notes

## Runtime
- Run locally with Docker Compose files in the project root: `./compose.yml` and `./compose.override.yml`.
- Use relative paths in MCP config (`mcp.json` and `.ai/mcp/mcp.json`) to avoid JetBrains resolving `/home/.../compose.yml`.
- Start development with:
  1. `make up`
  2. `make mate-serve`

## MCP
- Server name: `symfony-ai-mate`.
- Command pattern for MCP: Docker Compose `run --rm --no-deps php vendor/bin/mate serve --force-keep-alive`.

## Plugin Admin Architecture
- Admin CRUD routes are `type: sylius.resource` and use `vars.hook_prefix`:
  - `vanssa_sylius_slider_admin.slider`
  - `vanssa_sylius_slider_admin.slide`
- Forms are customized with Twig Hooks under `config/twig_hooks/admin/`.
- Use Sylius-style split forms with side navigation and tab panes (general/options/media/translations).
- Translation section uses Sylius accordion helper (`@SyliusAdmin/shared/helper/translations.html.twig`) and hook-driven field rendering.

## Translatable Models
- For Doctrine collection fields used with `CollectionType` and `by_reference: false`, both methods are required:
  - `addTranslation(...)`
  - `removeTranslation(...)`
- Missing `removeTranslation` causes form mapping errors in create/update.
- Locale fallback strategy:
  - Use localized translation first.
  - Fall back to channel default locale.
  - Fall back to base entity fields.

## Media UX
- Use preview-enabled upload fields in admin for slide media (desktop/mobile/tablet).
- Keep media grouped in tabs for clarity.
- Keep translation-level media separate from base media.

## Optional Integrations
- CMS integration templates: `templates/shop/integration/cms/`.
- Rich editor integration is optional and auto-detected via class existence checks.
