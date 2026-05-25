-include .env
export

# UID/GID хоста — чтобы файлы, создаваемые в контейнере, не были под root.
# Используем DOCKER_UID/DOCKER_GID, потому что UID в bash — readonly-переменная.
DOCKER_UID := $(shell id -u)
DOCKER_GID := $(shell id -g)
export DOCKER_UID DOCKER_GID

.PHONY: help init install up down parse test

help: ## Показать список команд
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*##/ { printf "  \033[36m%-12s\033[0m %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

init: ## Первичная настройка: скопировать .env, собрать образы, запустить, установить зависимости
	cp -n .env.example .env || true
	docker compose build
	docker compose up -d
	make install

install: ## Установить зависимости composer внутри php-контейнера
	docker compose exec php composer install

up: ## Запустить контейнеры
	docker compose up -d

down: ## Остановить контейнеры
	docker compose down

parse: ## Запустить парсер RSS вручную
	docker compose exec php php bin/console.php

test: ## Запустить тесты PHPUnit
	docker compose exec php vendor/bin/phpunit
