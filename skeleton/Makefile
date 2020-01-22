ifndef VERBOSE
	MAKEFLAGS += --no-print-directory
endif

default:

phpcs:
	vendor/bin/phpcs

phpstan:
	APP_ENV=test bin/console cache:clear
	vendor/bin/phpstan analyse

phpunit:
	phpdbg -qrr vendor/bin/phpunit $(options)

tests: phpcs phpstan phpunit

install:
	composer install
	bin/console doctrine:database:create --no-interaction --if-not-exists
	bin/console doctrine:migrations:migrate --no-interaction --query-time --allow-no-migration

.out_docker:
ifeq (, $(shell which docker))
	$(error "You must run this command outside the docker container. ")
endif

configure: .out_docker
	$(shell docker/dev/configure.sh)

reset_configuration: .out_docker
	rm -f docker/dev/.env

start: .out_docker configure
	@cd docker/dev && docker-compose build --pull --parallel --quiet
	@make .up php-fpm=0 php-messenger-consumer=0
	@cd docker/dev && docker-compose run --rm php-fpm make .waiting_for_dependency
	@cd docker/dev && docker-compose run --rm php-fpm make install
	@make .up

ifndef php-fpm
override php-fpm = 2
endif

ifndef php-messenger-consumer
override php-messenger-consumer = 3
endif

.up: .out_docker
	@cd docker/dev && docker-compose up \
	    --detach \
	    --remove-orphans \
	    --quiet-pull \
	    --scale nginx=2 \
	    --scale php-fpm=$(php-fpm) \
	    --scale php-messenger-consumer=$(php-messenger-consumer)

.waiting_for_dependency:
	@make .waiting_for service=redis port=6379 timeout=30
	@make .waiting_for service=rabbitmq port=5672 timeout=30
	@make .waiting_for service=mysql port=3306 timeout=30

.waiting_for:
	@echo -e "\e[1;33mWaiting for $(service) is Ready\e[0m"
	@/bin/sh -c 'for i in `seq 1 $(timeout)`;do nc $(service) $(port) -w 1 -z && exit 0;sleep 1;done;exit 1'
	@echo -e "\e[1;32m$(service) is ready\e[0m"

stop: .out_docker
	@cd docker/dev && docker-compose stop

remove: .out_docker
	@cd docker/dev && docker-compose down --remove-orphans --volumes

restart: stop start

ps: .out_docker
	@cd docker/dev && docker-compose ps

shell: .out_docker
	@cd docker/dev && docker-compose run --rm php-fpm /bin/bash

mysql_cli: .out_docker
	@cd docker/dev && docker-compose exec mysql /bin/bash -c 'mysql -uroot -p$${MYSQL_ROOT_PASSWORD} $${MYSQL_DATABASE}'

redis_cli: .out_docker
	@cd docker/dev && docker-compose exec redis redis-cli

logs: .out_docker
	@cd docker/dev && docker-compose logs --timestamps --follow --tail=50 $(service)

logs_nginx:
	@make logs service=nginx

logs_php-fpm:
	@make logs service=php-fpm

logs_messenger-consumer:
	@make logs service=php-messenger-consumer

logs_mysql:
	@make logs service=mysql

logs_redis:
	@make logs service=redis

logs_rabbitmq:
	@make logs service=rabbitmq
