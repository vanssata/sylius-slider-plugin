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

When adding, renaming, or removing a Stimulus controller in `assets/admin/controllers/` or `assets/shop/controllers/`, **three** places must stay in sync, or the webpack build breaks or runs stale code:

- `assets/admin/entrypoint.js` / `assets/shop/entrypoint.js` — the ONLY place plugin controllers are registered (`app.register('vanssa-...', Controller)`).
- `assets/controllers.json` — top-level project manifest (`controllers["@vanssa/sylius-slider-plugin"][name] = {enabled, fetch}`). **Every plugin controller here is deliberately `"enabled": false`** — see below.
- `assets/package.json`'s embedded `"symfony": { "controllers": {...} }` section — the source for `@symfony/stimulus-bridge`'s npm-package resolution (it carries the `main` file path and the registered `name`).

**Why `enabled: false`:** the test application's webpack merges this plugin's `controllers.json` into the bridge manifest used by BOTH `app-admin-entry` and `plugin-admin-entry` (same Encore config). Each entry calls `startStimulusApp()`, creating TWO Stimulus applications — with `enabled: true` every plugin controller (and every action/event handler) ran **twice** per page. Disabling bridge registration leaves exactly one registration: the explicit `app.register(...)` calls in our entrypoints. The `live` controller (`@symfony/ux-live-component`, registered by the test app's own `controllers.json`) still exists once — do NOT disable it there, and do not "fix" our manifest back to `enabled: true` or live-component actions start double-firing again (symptoms: toggles cancel themselves, LiveComponent lists duplicate rows).

If the manifests disagree, expect "Controller ... does not exist in the package" or "contains a reference to the file ..." build errors, or (worse) two divergent versions of the "same" controller running simultaneously on one page.

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
