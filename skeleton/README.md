# Skeleton_name

## Requirements

- [Docker](https://docs.docker.com/install/#supported-platforms) >= 18.06.0
- [Docker compose](https://docs.docker.com/compose/install) >= 1.25
- [Docker Proxy](https://github.com/philippe-vandermoere/docker-proxy)
- [Docker ELK](https://github.com/philippe-vandermoere/docker-elk)

## Configuration

Before you run the docker's, you need to configure then with the command:

```bash
make configure
```

List of configuration's options:

| Option                         | Description                                                                            | Default value |
|---                             |---                                                                                     |---            |
| GITHUB_TOKEN                   | Use by Docker proxy [see](https://github.com/philippe-vandermoere/docker-proxy#github) |               |
| GITHUB_CERTIFICATES_REPOSITORY | Use by Docker proxy [see](https://github.com/philippe-vandermoere/docker-proxy#github) |               |
| REDIS_PASSWORD                 | Define the password of redis Database                                                  | password      |
| TIMEZONE                       | Define the timezone (PHP + MySql)                                                      | Europe/Paris  |
| MYSQL_ROOT_PASSWORD            | Define MySql root password                                                             | root          |
| MYSQL_DATABASE                 | Define the name of MySql Database                                                      | test          |
| MYSQL_USER                     | Define the user of MySql Database                                                      | test          |
| MYSQL_PASSWORD                 | Define the password of MySql Database                                                  | test          |
| AMQP_USER                      | Define RabbitMq user                                                                   | test          |
| AMQP_PASSWORD                  | Define RabbitMq password                                                               | test          |
| AMQP_VHOST                     | Define RabbitMq virtual host                                                           | /             |

List of configuration's options automatically compute:

| Option               | Description                             |
|---                   |---                                      |
| COMPOSE_PROJECT_NAME | The name of docker stack: skeleton_name |
| HTTP_HOST            | The URL of application: skeleton_url    |
| DOCKER_UID           | The UID of php container (id -u)        |
| AMQP_VHOST_URLENCODE | RabbitMq urlencode                      |

## Installation

To Start the Docker's stack, execute:

```bash
make start
```

This command does:
- Build Docker's image
- Install PHP vendor
- Install or upgrade database

Your application is reachable at `https://skeleton_url`

## HTTP tools

Adminer is reachable at `https://skeleton_url/adminer`

RabbitMq admin management is reachable at `https://skeleton_url/rabbitmq`

## Makefile

### Application

| Command      | Description                                    |
|---           |---                                             |
| make install | Install PHP vendor and upgrade database schema |

### Docker

| Command                  | Description                                                                |
|---                       |---                                                                         |
| make configure           |                                                                            |
| make reset_configuration | Remove the user configuration of Docker stack                              |
| make start               | Build Docker images, start Docker stack and install application dependency |
| make stop                | Stop Docker's stack containers                                             |
| make restart             | Stop and start                                                             |
| make remove              | Stop Docker's stack containers and remove then                             |
| make ps                  | View the status of Docker's stack containers                               |

### CLI

| Command        | Description                                       |
|---             |---                                                |
| make shell     | Connect to the terminal in PHP docker's container |
| make mysql_cli | Connect to the MySql cli                          |
| make redis_cli | Connect to the Redis cli                          |

### Tests

| Command      | Description                |
|---           |---                         |
| make phpcs   | Run PHP Code Sniffer tests |
| make phpunit | Run PHP Unit tests         |
| make phpstan | Run PHP Stan tests         |
| make tests   | Run all tests commands     |

### logs

| Command                      | Description                                                        |
|---                           |---                                                                 |
| make logs                    | Follow the last 50 lines of containers logs                        |
| make logs_nginx              | Follow the last 50 lines of Nginx Containers logs                  |
| make logs_php-fpm            | Follow the last 50 lines of PHP-FPM Containers logs                |
| make logs_messenger-consumer | Follow the last 50 lines of PHP Messenger consumer Containers logs |
| make logs_mysql              | Follow the last 50 lines of MySql Container logs                   |
| make logs_redis              | Follow the last 50 lines of Redis Container logs                   |
| make logs_rabbitmq           | Follow the last 50 lines of RabbitMq Container logs                |
