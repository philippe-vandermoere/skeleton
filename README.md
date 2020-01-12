# Skeleton

## Description

- Symfony 5:
  - Doctrine ORM 
  - Symfony Messenger
  - Symfony HTTP client
- Docker stack:
  - Nginx  
  - PHP-fpm
  - PHP messenger consumer 
  - MySql
  - Redis
  - RabbitMq 
- Tests:
  - PHP Unit
  - PHP Code sniffer
  - PHP Stan
- CircleCi

## Install

### phar

requirements:
- PHP >= 7.4
- PHP extension AMQP
- PHP extension curl
- PHP extension json
- PHP extension redis

```bash
sudo curl -sl {url phar} -o /usr/local/bin/philou
sudo chmod +x 
philou project:create {project directory} --project-name {project name} --project-url {project url}
```

### PHP

requirements:
- PHP >= 7.4
- PHP extension AMQP
- PHP extension curl
- PHP extension json
- PHP extension redis

```bash
composer install
bin/console project:create {project directory} --project-name {project name} --project-url {project url}
```

### Docker

@todo

## Development

### Build Phar

```bash
bin/console phar:build
```

```bash
composer create-project -s dev philippe-vandermoere/skeleton:dev-{branch name} {project name} 
```
