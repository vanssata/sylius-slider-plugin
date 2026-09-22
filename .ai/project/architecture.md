# Architecture

<!-- Filled by /ai-init. Describe what the code does, not what the framework's
     documentation says it should do. -->

## Style

<!-- Layered, hexagonal, modular monolith, service-oriented, or — most often —
     several of these in different parts of the code. Say which is where. -->

## Entry points

<!-- HTTP routes, console commands, message consumers, cron jobs, webhooks.
     KNOWN FACT with the file that defines each. -->

## Request and message flow

<!-- One paragraph per major flow: what comes in, what it touches, what it
     writes, what goes out. -->

## Module boundaries

<!-- Where the seams are, and where they leak. -->

## Where state lives

<!-- Database, cache, queue, session, external systems, filesystem. -->

## Cross-cutting mechanisms

<!-- Events, the command bus, state machines, workflows, the DI container,
     configuration and how it is layered per environment. -->

## Contradictions found

<!-- Documented behaviour: …
     Observed behaviour: …
     Evidence: …
     Risk: …
     One block per contradiction. Do not resolve it here. -->

## Architecture

A Sylius 2.x plugin for storefront sliders and banners. Full map:
`docs/dev/architecture.md`. The essentials:

- **Plugin class**: `src/VanssaSyliusSliderPlugin.php` (`SyliusPluginTrait`)
- **DI extension**: `src/DependencyInjection/VanssaSyliusSliderExtension.php` —
  service loading and Doctrine migration namespace
- **Services**: `config/services.xml`; **routes**: `config/routes/{admin,shop}.yaml`
- **Templates**: `templates/` — admin workspace, Twig components, Twig hooks
- **Migrations**: `src/Migrations/` (not `tests/`)

**The bootable kernel is `vendor/sylius/test-application`**, not
`tests/TestApplication`. `tests/TestApplication/` only contributes
config/templates/src that are merged into it, and `composer.json`
`extra.public-dir` points at the vendor path. Any tooling that assumes
`tests/TestApplication/public` is a document root is wrong here — that includes
the `sylius-dev` skill and `sylius-quality`'s stock Playwright config, both of
which are written for an application layout rather than a plugin one.

The console binary is `vendor/bin/console`; the PHP namespace is
`Vanssa\SyliusSliderPlugin\`.

### Database Configuration
Database credentials should be configured in:
- `tests/TestApplication/.env` (for development)
- `tests/TestApplication/.env.test` (for testing)

The compose stack overrides `DATABASE_URL` per environment
(`mysql://root@mysql/sylius_%kernel.environment%`), so `sylius_dev`,
`sylius_test` and `sylius_prod` are three separate databases. A test run
without `APP_ENV=test` hits the wrong one — `make phpunit` and `make verify`
set it for you.
