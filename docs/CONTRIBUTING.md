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
vendor/bin/phpunit
vendor/bin/behat --strict --tags='@slider_admin'
vendor/bin/behat --strict --tags='@slider_frontend'
vendor/bin/phpstan analyse -c phpstan.neon -l max src/
vendor/bin/ecs check
```

## Pull request rules

- Keep changes focused and atomic.
- Add or update tests for behavior changes.
- Update docs when adding fields/routes/features.
- Keep fixture media license-safe and documented.

## Coding conventions

- Follow Sylius and Symfony conventions.
- Use Twig Hooks for admin/shop UI composition.
- Prefer backward-compatible settings evolution in JSON fields.
