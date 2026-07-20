# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Development Commands

### Docker Environment (Recommended)
```bash
# Initialize Docker environment and install dependencies
make init

# Initialize database and run migrations
make database-init

# Load fixtures (optional)
make load-fixtures

# Start/stop containers
make up
make down

# Access containers
make php-shell
make node-shell
```

### Traditional Development
```bash
# Frontend setup
(cd vendor/sylius/test-application && yarn install)
(cd vendor/sylius/test-application && yarn build)
vendor/bin/console assets:install

# Database setup
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
vendor/bin/console sylius:fixtures:load -n

# Start server
symfony server:start -d
```

### Testing
```bash
# PHPUnit tests
vendor/bin/phpunit
make phpunit  # Docker

# Behat tests (non-JS)
vendor/bin/behat --strict --tags="~@javascript&&~@mink:chromedriver"
make behat  # Docker

# Behat tests (JS scenarios)
# Requires Chrome headless and symfony server
APP_ENV=test symfony server:start --port=8080 --daemon
vendor/bin/behat --strict --tags="@javascript,@mink:chromedriver"
```

### Code Quality
```bash
# PHPStan analysis (level max + baseline configured in phpstan.neon)
vendor/bin/phpstan analyse -c phpstan.neon
make phpstan  # Docker

# Rector (dry-run / apply)
make rector
make rector-fix

# Coding standards
vendor/bin/ecs check
make ecs  # Docker
```

### Docker Compose command quoting

`docker compose run --rm nodejs <cmd>` — the `nodejs` service already has `entrypoint: ["/bin/sh","-c"]`, so passing `sh -lc "..."` as `<cmd>` double-wraps it and the command silently does nothing useful. Pass the raw shell command as a single string argument instead:

```bash
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
```

### Composer Scripts
```bash
# Database reset with fixtures
composer run database-reset

# Frontend rebuild
composer run frontend-clear

# Complete test app initialization
composer run test-app-init
```

## Architecture

This is a **Sylius Plugin Skeleton** - a template for creating Sylius e-commerce plugins. It provides a complete development environment with both traditional and Docker setups.

### Core Structure
- **Main Plugin Class**: `src/VanssaSyliusSliderPlugin.php` - Entry point using `SyliusPluginTrait`
- **DI Extension**: `src/DependencyInjection/VanssaSyliusSliderExtension.php` - Handles service loading and Doctrine migrations
- **Services**: `config/services.xml` - Service definitions with XML configuration
- **Routes**: `config/routes/` - Separate admin and shop route definitions
- **Templates**: `templates/` - Twig templates for admin and shop with Twig hooks support

### Key Features
- **Test Application**: Uses `sylius/test-application` for plugin testing in isolation
- **Asset Management**: Webpack Encore for frontend asset compilation
- **Database**: Doctrine migrations with proper namespace handling
- **Testing**: Full Behat + PHPUnit setup with browser testing support
- **Code Quality**: PHPStan, ECS (Easy Coding Standard), and Rector integration

### Development Environment
- **Docker**: Complete containerized environment with PHP, Node.js, and database
- **Traditional**: Local Symfony server with manual dependency management
- **Frontend**: Yarn-based asset pipeline through test application

### Testing Strategy
- **Unit/Integration**: PHPUnit for isolated component testing
- **Functional**: Behat for feature testing with browser automation
- **Static Analysis**: PHPStan for type checking and code quality
- **Standards**: ECS for coding standard enforcement

### Database Configuration
Database credentials should be configured in:
- `tests/TestApplication/.env` (for development)
- `tests/TestApplication/.env.test` (for testing)

## Stimulus Controller Manifests (Important Gotchas)

The plugin is a proper Symfony UX package now: controllers register **only**
through the `@symfony/stimulus-bridge` manifest, inside the consuming app's own
`startStimulusApp()`. When adding, renaming, or removing a Stimulus controller
in `assets/admin/controllers/` or `assets/shop/controllers/`, **four** places
must stay in sync (identical key sets, all 14 controllers), or the webpack
build breaks or runs stale code:

- `assets/package.json`'s embedded `"symfony": { "controllers": {...} }`
  section — the authoritative source (`main` file path, registered `name`,
  `fetch`, `enabled`, `autoimport`) that Flex copies into a fresh consumer
  project and that `@symfony/stimulus-bridge` resolves for npm-package
  installs.
- `assets/controllers.json` — this repo's own top-level dev manifest, mirrors
  the package.json defaults (`enabled: true` throughout).
- `assets/admin/controllers.json` — per-context manifest for
  sylius/test-application's admin Encore build (all 14 enabled, all fetched
  eagerly for admin dev convenience).
- `assets/shop/controllers.json` — per-context manifest for the shop Encore
  build (shop `slider`/`slide-video` pair enabled+eager; the 12 admin
  controllers listed but `enabled: false`).

**Shallow-merge trap:** sylius/test-application's webpack merges each
`controllers.json` into the bridge manifest with a **shallow spread per
package key** — a per-context file's `@vanssa/sylius-slider-plugin` object
*replaces* the whole thing, it does not deep-merge per controller. That means
`assets/admin/controllers.json` and `assets/shop/controllers.json` must each
list **all 14** controllers (even the ones a context disables) — omitting one
silently drops it from that context's build instead of falling back to a
default. A PostToolUse hook enforces identical key sets across
`assets/package.json`'s `symfony.controllers`, `assets/controllers.json`,
`assets/admin/controllers.json` and `assets/shop/controllers.json` for AI
edits — if it fires, one of the four fell out of sync.

**Entrypoints never start Stimulus:** `assets/admin/entrypoint.js` and
`assets/shop/entrypoint.js` must **never** import `@symfony/stimulus-bridge`,
call `startStimulusApp()`, or `app.register(...)` a controller — the bridge
manifest is the only registrar now. The shop entrypoint is comment-only (kept
only because sylius/test-application hard-codes it as the `plugin-shop-entry`
webpack entry); the admin entrypoint keeps only what must run eagerly outside
Stimulus: `Turbo.session.drive = false` (critical — without it `@hotwired/turbo`
Drive hijacks every Sylius admin navigation, since the admin isn't built with
Turbo navigation in mind), the sidebar-focus behavior, and the admin
stylesheet imports. If a controller class ever creeps back into an
`app.register(...)` call in either entrypoint, expect every action/event
handler on that controller to fire **twice** per page — see below for why.

**Why `enabled: true` is correct now (it wasn't before):** an earlier version
of this plugin's own manifest deliberately shipped `enabled: false` because
the entrypoints *also* called `startStimulusApp()` and registered controllers
explicitly — with the bridge manifest also enabled, sylius/test-application's
merged config for `app-admin-entry` + `plugin-admin-entry` created TWO
Stimulus applications, double-firing everything. Now that the entrypoints
never start a Stimulus app or register anything themselves, the bridge
manifest is the *only* registrar, so `enabled: true` is required, not
optional — this mirrors what Symfony Flex seeds into a fresh consumer
project's `assets/controllers.json` on `composer require` (see README). The
`live` controller (`@symfony/ux-live-component`, registered by the test app's
own `controllers.json`) is unaffected by any of this.

If the manifests disagree, expect "Controller ... does not exist in the
package" or "contains a reference to the file ..." build errors, or (worse) a
controller silently missing from one context's build because the shallow
merge dropped it.

**LiveComponent morphing:** ux-live-component 2.31 morphs with idiomorph, which matches nodes by real `id` attributes only — `data-live-id` does nothing. Any list a LiveComponent re-renders while outside code mutates its DOM (drag reorder, modals re-parented to `<body>`) needs a unique `id` on every row (and stable ids on sibling anchors), or re-renders duplicate rows. `data-model` selects also need explicit `selected` attributes rendered from the server prop.

Additionally, `vendor/sylius/test-application/package.json` depends on this plugin's assets via `"@vanssa/sylius-slider-plugin": "file:../../../assets"`. Yarn classic (v1) **copies** this into `node_modules/@vanssa/sylius-slider-plugin` rather than symlinking it, and a plain `yarn install` does **not** refresh that copy when only source files change (lockfile unaffected). After editing `assets/package.json` or controller source files, refresh with:

```bash
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn install --force"
docker compose run --rm nodejs "cd vendor/sylius/test-application && yarn build"
```

If Playwright/browser testing doesn't reflect a fresh JS build, also restart the `chrome` container (`docker compose restart chrome`) — it can hold a stale bundle in memory.

## AI Development Guides

This project includes specialized AI guides to assist with common plugin development tasks:

- **CLEANUP_GUIDE.md** - Guidelines for cleaning up and organizing plugin code
- **RENAME_GUIDE.md** - Step-by-step instructions for renaming plugins and components
- **COMPATIBILITY_GUIDE.md** - Best practices for maintaining compatibility across different Sylius versions

These guides provide detailed instructions and automated workflows to help maintain code quality and ensure proper plugin structure.
# Symfony UX Frontend Stack

This project uses the Symfony UX frontend stack. Seven agent skills are installed to help you work with it.

## Which tool to use

- **Pure JS behavior, no server round-trip** -- use the `stimulus` skill
- **Navigation, partial page updates** -- use the `turbo` skill
- **Reusable static UI component** -- use the `twig-component` skill
- **Reactive component that re-renders on user input** -- use the `live-component` skill
- **SVG icons (local or Iconify)** -- use the `ux-icons` skill
- **Interactive maps (Leaflet / Google Maps)** -- use the `ux-map` skill
- **Not sure which one fits** -- use the `symfony-ux` skill (orchestrator / decision tree)

## Key rules

- Always render `{{ attributes }}` on the root element of a LiveComponent
- Prefer HTML syntax (`<twig:Alert />`) over Twig syntax (`{% component 'Alert' %}`)
- Use `data-model="debounce(300)|field"` for text inputs in LiveComponents
- Stimulus controllers must clean up listeners and observers in `disconnect()`
- Turbo Frame IDs must match between the page and the server response
- Use Turbo Streams when updating multiple page sections; use Frames for a single section
- `<twig:Turbo:Stream:Append>` syntax is available since Symfony UX 2.22+
- Prefer `<twig:ux:icon name="..." />` over `{{ ux_icon('...') }}` for consistency
- Map containers must have an explicit height (`style="height: 400px;"`)
- Use `fitBoundsToMarkers()` instead of manually calculating center/zoom
- Lock on-demand icons before deploying: `php bin/console ux:icons:lock`
- Use Playwright to get what how pages is like. 
- Use Playwright to get browsers test. 
