.PHONY: up down restart build logs shell artisan composer npm test

CMD ?=

up:
	docker compose up -d --build

down:
	docker compose down

restart:
	docker compose down
	docker compose up -d

build:
	docker compose build

logs:
	docker compose logs -f --tail=100

shell:
	docker compose exec app bash

artisan:
	docker compose exec app php artisan $(CMD)

composer:
	docker compose exec app composer $(CMD)

npm:
	docker compose exec node npm $(CMD)

test:
	docker compose exec app php artisan test
