# Skeleton

## Description

- Symfony (5.0):
  - Doctrine ORM 
  - Symfony Messenger
  - Symfony HTTP client
- Docker stack:
  - Nginx (1.16)
  - PHP-fpm (7.4)
  - PHP messenger consumer (7.4)
  - MySql (8.0)
  - Redis (5.0)
  - RabbitMq (3.8)
- Tests:
  - PHP Unit
  - PHP Code sniffer
  - PHP Stan
- CircleCi

## Install

Options

| Name                     | Description                                          | Default Value     |
|---                       |---                                                   |---                |
| url                      | Define the project URL.                              |                   |
| directory                | Define the directory to create project. see example. | current directory |
| delete-project-directory | Delete the project directory if exist.               | false             |
| no-initialize-git        | Do not initialize GIT repository.                    | true              |
| fix-files-owner          | Fix Files owner.                                     | false             |

Example:
```bash
bin/console project:create test --directory /home/test/project
```

The project is found in the folder `/home/test/project/test`

### Docker

requirements:
- docker

```bash
docker pull philippev/skeleton:latest
docker run --rm -ti -v {directory}:/project philippev/skeleton:latest {project name}
```

### PHAR

requirements:
- PHP >= 7.4
- PHP extension curl
- PHP extension json

```bash
sudo curl -sl https://github.com/philippe-vandermoere/skeleton/releases/download/0.2.0-rc/philou.phar -o /usr/local/bin/philou
sudo chmod +x 
philou project:create {project name}
```

### PHP

requirements:
- PHP >= 7.4
- PHP extension curl
- PHP extension json

```bash
composer install
bin/console project:create {project name}
```

## Development

### Build Phar

```bash
bin/console phar:build
```
