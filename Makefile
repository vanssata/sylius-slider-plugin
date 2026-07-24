.PHONY: init run dev debug up down clean php-shell node-shell node-watch node-watch-logs node-watch-stop node-build \
	docker-compose-check database-init database-reset load-fixtures load-slider-fixtures cc mig \
	phpstan ecs rector rector-fix phpunit behat mate-init mate-discover mate-serve rename run-github-tests

DOCKER_COMPOSE ?= docker compose
DOCKER_USER ?= "$(shell id -u):$(shell id -g)"
ENV ?= "dev"

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
	@make -s mate-serve

debug:
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
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/phpunit

behat:
	@ENV=$(ENV) DOCKER_USER=root $(DOCKER_COMPOSE) run --rm php vendor/bin/behat

mate-init:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/mate init -n

mate-discover:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) run --rm php vendor/bin/mate discover -n

mate-serve:
	@ENV=$(ENV) DOCKER_USER=$(DOCKER_USER) $(DOCKER_COMPOSE) exec -T php vendor/bin/mate serve --force-keep-alive

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
