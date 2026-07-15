# Contributing

## Setup

```bash
composer install
(cd vendor/sylius/test-application && yarn install)
(cd vendor/sylius/test-application && yarn build)
vendor/bin/console assets:install
vendor/bin/console doctrine:database:create
vendor/bin/console doctrine:migrations:migrate -n
```

Optional demo data:

```bash
vendor/bin/console sylius:fixtures:load --suite=vanssa_sylius_slider_demo -n
```

## Quality checks

```bash
vendor/bin/phpunit --testsuite=unit
vendor/bin/phpunit --testsuite=functional   # needs the test database
vendor/bin/behat --strict --tags='@slider_admin'
vendor/bin/behat --strict --tags='@slider_frontend'
vendor/bin/phpstan analyse -c phpstan.neon
vendor/bin/ecs check
vendor/bin/rector process --dry-run
```

All of these also run in CI (`.github/workflows/build.yaml`) against Sylius
`~2.1.0` and `~2.2.0`. Docker equivalents: `make phpunit`, `make behat`,
`make phpstan`, `make ecs`, `make rector`.

PHPStan uses a baseline (`phpstan-baseline.neon`) for pre-existing findings —
new code must analyse clean; do not add new entries to the baseline.

## Pull request rules

- Keep changes focused and atomic.
- Add or update tests for behavior changes.
- Update docs when adding fields/routes/features.
- Keep fixture media license-safe and documented.

## Coding conventions

- Follow Sylius and Symfony conventions.
- Use Twig Hooks for admin/shop UI composition.
- Prefer backward-compatible settings evolution in JSON fields.
