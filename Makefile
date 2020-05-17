DOCKER_COMPOSE=docker-compose --env-file=.docker/.env -f .docker/docker-compose.yml
WORKSPACE_CONTAINER=workspace

DEFAULT_GOAL := help
help:
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m<target>\033[0m\n"} /^[a-zA-Z0-9_-]+:.*?##/ { printf "  \033[36m%-27s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ [Docker] Build / Infrastructure
.PHONY: docker-rebuild
docker-rebuild: ## Build all docker images from scratch, without cache etc. Build a specific image by providing the service name via: make docker-build CONTAINER=<service>
	$(DOCKER_COMPOSE) rm -fs $(CONTAINER) && \
	$(DOCKER_COMPOSE) build --pull --no-cache --parallel $(CONTAINER) && \
	$(DOCKER_COMPOSE) up -d --force-recreate $(CONTAINER)

.PHONY: docker-build
docker-build: ## Build all docker images. Build a specific image by providing the service name via: make docker-build CONTAINER=<service>
	$(DOCKER_COMPOSE) build --parallel $(CONTAINER) && \
	$(DOCKER_COMPOSE) up -d --force-recreate $(CONTAINER)

.PHONY: docker-prune
docker-prune: ## Remove unused docker resources via 'docker system prune -a -f --volumes'
	docker system prune -a -f --volumes

.PHONY: docker-up
docker-up: ## Start all docker containers. To only start one container, use CONTAINER=<service>
	$(DOCKER_COMPOSE) up -d $(CONTAINER)

.PHONY: docker-down
docker-down: ## Stop all docker containers
	$(DOCKER_COMPOSE) down


##@ [Workspace] Project related tools (e.g. package manager)

.PHONY: composer-install
composer-install: ## install composer dependencies
	$(DOCKER_COMPOSE) run --rm -w //var/www/release-flow $(WORKSPACE_CONTAINER) composer install

.PHONY: composer-update
composer-update: ## update composer dependencies
	$(DOCKER_COMPOSE) run --rm -w //var/www/release-flow $(WORKSPACE_CONTAINER) composer update

.PHONY: composer-release
composer-release: ## create new release and build phar file of project
	$(DOCKER_COMPOSE) run --rm -w //var/www/release-flow $(WORKSPACE_CONTAINER) composer build
