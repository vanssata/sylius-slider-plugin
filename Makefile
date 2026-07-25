.PHONY: init run dev debug up down clean php-shell node-shell node-watch node-watch-logs node-watch-stop node-build \
	docker-compose-check database-init database-reset load-fixtures load-slider-fixtures cc mig \
	phpstan ecs rector rector-fix phpunit behat rename run-github-tests \
	mate-init mate-discover verify e2e e2e-up e2e-check e2e-down docs-media

DOCKER_COMPOSE ?= docker compose
DOCKER_USER ?= "$(shell id -u):$(shell id -g)"
ENV ?= "dev"

# Playwright runs in its own profiled service; `exec` (never `run -d`) so a
# never-exiting command cannot leak an orphan container.
E2E_EXEC = $(DOCKER_COMPOSE) --profile e2e exec -T playwright
# Webpack Encore output of the bootable kernel — used by e2e-check to tell
# whether the watcher has caught up with the latest assets/ edit.
BUILD_DIR = vendor/sylius/test-application/public/build

init:
	@make -s docker-compose-check
	@if [ ! -e compose.override.yml ]; then \
		cp compose.override.dist.yml compose.override.yml; \
	fi
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php composer install --no-interaction --no-scripts --no-plugins
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm nodejs
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) up -d

run:
	@make -s dev

dev:
	@make -s up

# `compose.debug.yml` is not in this repository and never has been — it is a
# per-developer overlay. The php service already runs Xdebug in debug mode
# (compose.override.dist.yml), so this target is only for extra overrides on
# top of that; fail with an explanation instead of a compose "no such file".
debug:
	@test -f compose.debug.yml || { \
		echo "compose.debug.yml does not exist. It is a personal overlay, not part of the repository."; \
		echo "The php service already sets XDEBUG_MODE=debug and PHP_IDE_CONFIG — plain 'make up' is usually enough."; \
		echo "Create compose.debug.yml with your extra overrides if you need this target."; \
		exit 1; \
	}
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) -f compose.yml -f compose.override.yml -f compose.debug.yml up -d

up:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) up -d

down:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) down

clean:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) down -v

php-shell:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) exec php sh

node-shell:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -i nodejs sh

# Long-lived `encore dev --watch` as a managed compose service. Detached on purpose:
# `docker compose run` with a never-exiting process leaks an orphan container.
# The service also symlinks the plugin package into node_modules, so controller
# edits actually reach the watcher (yarn classic copies `file:` deps otherwise).
node-watch:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) --profile watch up -d nodejs-watch
	@echo "Watching. Follow with: make node-watch-logs   Stop with: make node-watch-stop"

node-watch-logs:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) --profile watch logs -f nodejs-watch

node-watch-stop:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) --profile watch rm -sf nodejs-watch

node-build:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -i nodejs "(cd vendor/sylius/test-application && yarn install && yarn build)"


docker-compose-check:
	@$(DOCKER_COMPOSE) version >/dev/null 2>&1 || (echo "Please install docker compose binary or set DOCKER_COMPOSE=\"docker-compose\" for legacy binary" && exit 1)
	@echo "You are using \"$(DOCKER_COMPOSE)\" binary"
	@echo "Current version is \"$$($(DOCKER_COMPOSE) version)\""

database-init:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:database:create -n --if-not-exists
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:migrations:migrate -n

database-reset:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:database:drop -n --force --if-exists
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:database:create -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console doctrine:migrations:migrate -n

load-fixtures:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console sylius:fixtures:load -n

load-slider-fixtures:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console sylius:fixtures:load vanssa_sylius_slider_demo -n

cc:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console c:c -n

mig:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console d:m:mNa -n

phpstan:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/phpstan analyse -c phpstan.neon

ecs:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/ecs check src

rector:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/rector process --dry-run

rector-fix:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/rector process

phpunit:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -e APP_ENV=test php vendor/bin/phpunit

behat:
	@ENV=$(ENV) DOCKER_USER=root $(DOCKER_COMPOSE) run --rm php vendor/bin/behat

# --- Verify loop & end-to-end tests -----------------------------------------
#
# `make verify` is the fast deterministic loop (ECS --fix, PHPStan, PHPUnit) —
# seconds to a minute. `make e2e` is the slow gate and stays OUT of it.
# The sylius-quality skill calls these `composer ai:verify` / `composer ai:e2e`;
# on this host neither PHP nor Node exists, so the make targets are the entry
# points and they run everything in containers.

verify:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm -e APP_ENV=test php composer ai:verify

# Bring up the Playwright runner and install the e2e node deps once.
e2e-up:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) --profile e2e up -d playwright
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(E2E_EXEC) sh -c '[ -x node_modules/.bin/playwright ] || npm install --no-audit --no-fund'

# The three assertion projects, listed EXPLICITLY. `npx playwright test` with
# no --project filter selects every configured project — including
# `docs-media`, whose specs OVERWRITE the committed screenshots and GIFs. A
# plain `make e2e` must never do that.
E2E_PROJECTS = --project=desktop --project=tablet --project=mobile

e2e: e2e-up
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(E2E_EXEC) npx playwright test \
		$(if $(findstring --project,$(ARGS)),,$(E2E_PROJECTS)) $(ARGS)

# Fast visual loop while editing SCSS/CSS/JS: block until the compiled bundles
# are newer than the newest assets/ source (i.e. the watcher caught up), then
# run ONE spec on desktop + tablet + mobile. Seconds, not a full suite.
#
#   make e2e-check SPEC=tests/e2e/shop/responsive-overrides.spec.ts
e2e-check: e2e-up
	@test -n "$(SPEC)" || { echo "Usage: make e2e-check SPEC=tests/e2e/shop/<file>.spec.ts"; exit 1; }
	@i=0; \
	while [ $$i -lt 90 ]; do \
		a=$$(find assets -type f -printf '%T@\n' 2>/dev/null | sort -rn | head -1); \
		b=$$(find $(BUILD_DIR) -type f -printf '%T@\n' 2>/dev/null | sort -rn | head -1); \
		awk -v a="$$a" -v b="$$b" 'BEGIN { exit (a > b) ? 0 : 1 }' || break; \
		[ $$i -eq 0 ] && echo "assets/ is newer than the compiled bundles — waiting for the watcher (make node-watch)..."; \
		i=$$((i+1)); sleep 2; \
	done; \
	[ $$i -ge 90 ] && echo "WARNING: bundles still stale after 180s — is nodejs-watch running?" || true
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(E2E_EXEC) npx playwright test $(SPEC) \
		--project=desktop --project=tablet --project=mobile --reporter=list

# Regenerate every screenshot and GIF under docs/ (see docs/dev/docs-media.md).
# Not part of `make e2e`: these are generators, not assertions.
docs-media: e2e-up
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(E2E_EXEC) npx playwright test --project=docs-media --reporter=list

e2e-down:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) --profile e2e rm -sf playwright

# AI Mate (MCP server) — regenerate the local `mate/` tree. Both the tree and
# the MCP client configuration are gitignored local tooling: optional, not
# required to build or test the plugin.
# There is deliberately NO `mate-serve` target: `mate serve` speaks MCP over
# stdio and is started by the MCP client, never by a human or by `make dev`.
mate-init:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/mate init -n
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php composer dump-autoload

mate-discover:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/mate discover -n

rename:
	@php bin/rename-plugin.php

run-github-tests:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/phpunit --colors=always --testsuite=unit
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php composer validate --ansi --strict
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/ecs check
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/phpstan analyse -c phpstan.neon
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/console lint:container
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/phpunit --colors=always --testsuite=non-unit
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php sh -lc "vendor/bin/behat --colors --strict -vvv --no-interaction -f progress || vendor/bin/behat --colors --strict -vvv --no-interaction -f progress --rerun"
