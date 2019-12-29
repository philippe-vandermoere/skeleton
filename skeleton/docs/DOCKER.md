# Docker

## Requirements

- [Docker](https://docs.docker.com/install/#supported-platforms) >= 18.06.0
- [Docker compose](https://docs.docker.com/compose/install) >= 1.25
- [Docker Proxy](https://github.com/philippe-vandermoere/docker-proxy)

## Start

To run the Docker's stack, execute:

```bash
make start
```

This command does:
- Build Docker's image
- Install PHP vendor
- Install or upgrade database

Your application is reachable at `https://skeleton_url`

## Shell

To connect to the PHP shell in the docker's container, execute:

```bash
make shell
```

## Logs

If you want to see the logs of docker's container, execute:

```bash
make logs
```
