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
# PHPStan analysis
vendor/bin/phpstan analyse -c phpstan.neon -l max src/
make phpstan  # Docker

# Coding standards
vendor/bin/ecs check
make ecs  # Docker
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
