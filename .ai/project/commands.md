### Environment
```bash
make init             # build images, install deps, create compose.override.yml
make up / make down   # start / stop the stack
                      #   http://sylius-slider.localhost  (via the shared Traefik, started by `make up`)
                      #   http://localhost:82             (proxy-free bypass to the same nginx)
make proxy-up / make proxy-down / make proxy-logs   # the shared :80 proxy on its own
                      # see docs/dev/local-domains.md — incl. why fixtures break the shop
make clean            # down -v

make database-init    # create the DB and run migrations
make database-reset   # drop + create + migrate
make load-fixtures            # full Sylius fixtures
make load-slider-fixtures     # vanssa_sylius_slider_demo only

make php-shell / make node-shell
make cc / make mig
```

The stack runs with `APP_ENV=${ENV:-dev}` and `APP_DEBUG=true` (see
`compose.override.dist.yml`; the Makefile also defaults `ENV` to `dev`), so a
plain `make up` gives you the **web debug toolbar** and the profiler. The
docs-media generators need a stack without it: start with `ENV=prod make up`
first (`docs/dev/docs-media.md`).

### Testing
```bash
make verify        # the fast loop: ECS --fix, PHPStan, PHPUnit (APP_ENV=test)
make phpunit       # PHPUnit only, APP_ENV=test
make behat         # Behat; the @javascript leg drives the `chrome` service
make e2e           # Playwright suite (tests/e2e) in the `playwright` service
make e2e-check SPEC=tests/e2e/shop/slider-behavior.spec.ts   # one spec, 3 viewports
make e2e-down      # stop the Playwright service when the task ends
```

`make verify` is `composer ai:verify` inside the container — that is the
command the `sylius-quality` skill means when it says "run `composer
ai:verify`". There is no `composer ai:e2e`: composer runs in the `php`
container and Playwright lives in a different one, so `make e2e` is the entry
point. See `docs/dev/testing.md` for when to reach for Behat vs Playwright.

### Code Quality
```bash
make phpstan       # level max + baseline (phpstan.neon)
make ecs           # Easy Coding Standard
make rector        # dry-run
make rector-fix    # apply
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
