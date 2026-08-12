COMPOSE = UID=$$(id -u) GID=$$(id -g) docker compose

BACK_SERVICE  = back-task
NGINX_SERVICE = back-nginx
FRONT_DEV     = front-dev
FRONT_PROD    = front-prod

.PHONY: build up down restart logs ps shell composer artisan migrate migrate-fresh front-shell front-install front-build nginx-logs

build:
	$(COMPOSE) build

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

restart: down up

logs:
	$(COMPOSE) logs -f --tail=150

nginx-logs:
	$(COMPOSE) logs -f --tail=150 $(NGINX_SERVICE)

ps:
	$(COMPOSE) ps

shell:
	$(COMPOSE) exec --user "$$(id -u):$$(id -g)" $(BACK_SERVICE) sh

composer:
	$(COMPOSE) exec --user "$$(id -u):$$(id -g)" $(BACK_SERVICE) composer $(CMD)

artisan:
	$(COMPOSE) exec --user "$$(id -u):$$(id -g)" $(BACK_SERVICE) php artisan $(CMD)

migrate:
	$(COMPOSE) exec --user "$$(id -u):$$(id -g)" $(BACK_SERVICE) php artisan migrate --force

migrate-fresh:
	$(COMPOSE) exec --user "$$(id -u):$$(id -g)" $(BACK_SERVICE) php artisan migrate:fresh --force

front-shell:
	$(COMPOSE) exec $(FRONT_DEV) sh

front-install:
	$(COMPOSE) exec $(FRONT_DEV) pnpm install

front-build:
	$(COMPOSE) build $(FRONT_PROD)