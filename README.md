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

### Composer

requirements:
- PHP >= 7.4
- PHP extension AMQP
- PHP extension curl
- PHP extension json
- PHP extension redis

```bash
composer create-project philippe-vandermoere/skeleton {project name} 
```

### Docker

@todo

## Development

### Build Phar

```bash
phar/bin/console phar:build
```

```bash
composer create-project -s dev philippe-vandermoere/skeleton:dev-{branch name} {project name} 
```
