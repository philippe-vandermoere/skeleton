ifndef VERBOSE
	MAKEFLAGS += --no-print-directory
endif

default:

####################### GIT #######################
git_hooks:
	@git config core.hooksPath .githooks

####################### Tests #######################
tests: phpcs phpstan phpunit security

phpcs: check_requirements
	vendor/bin/phpcs

phpstan: check_requirements
	APP_ENV=test bin/console cache:clear
	vendor/bin/phpstan analyse

phpunit: check_requirements
ifeq (, $(shell which phpdbg))
	$(error "You must install phpdbg")
endif
	phpdbg -qrr vendor/bin/phpunit $(options)

security:
ifeq (, $(shell which security-checker))
	$(error "You must install security-checker (https://github.com/sensiolabs/security-checker)")
endif
	security-checker security:check composer.lock

####################### Application #######################
check_requirements: git_hooks
ifeq (, $(shell which composer))
	$(error "You must install composer (https://getcomposer.org/download/)")
endif
	@composer check-platform-reqs --no-interaction --quiet || composer check-platform-reqs --no-interaction --ansi

install: check_requirements
	composer install --no-interaction --no-progress --ansi

migrate: migrate_mysql migrate_rabbitmq

migrate_mysql:
	bin/console doctrine:migrations:migrate --no-interaction --query-time --allow-no-migration

migrate_rabbitmq:
	bin/console messenger:setup-transports

####################### Docker #######################
ifndef php-fpm
override php-fpm = 2
endif

ifndef php-messenger-consumer
override php-messenger-consumer = 3
endif

.out_docker: git_hooks
ifeq (, $(shell which docker))
	$(error "You must run this command outside the docker container")
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
	@cd docker/dev && docker-compose run --rm php-fpm make migrate
	@make .up

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

####################### Docker CLI #######################
shell: .out_docker configure
	@cd docker/dev && docker-compose run --rm php-fpm /bin/bash

mysql_cli: .out_docker
	@cd docker/dev && docker-compose exec mysql /bin/bash -c 'mysql -uroot -p$${MYSQL_ROOT_PASSWORD} $${MYSQL_DATABASE}'

redis_cli: .out_docker
	@cd docker/dev && docker-compose exec redis redis-cli

####################### Docker logs #######################
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
