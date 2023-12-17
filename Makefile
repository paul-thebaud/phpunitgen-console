# Executables
DOCKER_COMPOSE?=docker compose
DOCKER_EXEC?=$(DOCKER_COMPOSE) exec -it
COMPOSER?=$(DOCKER_EXEC) php composer

# Misc
default: help

##
## —— Setup ————————————————————————————————————————————————————————————————————

.PHONY: build
build: ## Build and start containers.
	@$(DOCKER_COMPOSE) up --build --no-recreate -d

.PHONY: rebuild
rebuild: ## Force rebuild and start all containers.
	@$(DOCKER_COMPOSE) up --build --force-recreate --remove-orphans -d

.PHONY: up
up: ## Start containers without building.
	@$(DOCKER_COMPOSE) up -d

.PHONY: stop
stop: ## Stop containers.
	@$(DOCKER_COMPOSE) stop

.PHONY: down
down: ## Stop and remove containers.
	@$(DOCKER_COMPOSE) down --remove-orphans --timeout=2

##
## —— Executables ——————————————————————————————————————————————————————————————

.PHONY: composer
composer: ## Run a Composer command (e.g. make composer c="update").
	@$(COMPOSER) $(c)

##
## —— Tests ————————————————————————————————————————————————————————————————————

.PHONY: test
test: ## Run tests.
	@$(DOCKER_EXEC) php php -d xdebug.mode=coverage vendor/bin/phpunit -c phpunit.xml.dist

##
## —— Utilities ————————————————————————————————————————————————————————————————

.PHONY: sh
sh: ## Run BASH on PHP container.
	@$(DOCKER_EXEC) php bash

.PHONY: help
help: ## Show help for each of the Makefile recipes.
	@grep -E '(^[a-zA-Z0-9\./_-]+:.*?##.*$$)|(^##)' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}{printf "\033[32m%-30s\033[0m %s\n", $$1, $$2}' | sed -e 's/\[32m##/[33m/'
