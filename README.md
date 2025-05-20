# Kalaxia

## Setup

### Dependencies
- [Docker](https://docs.docker.com/engine/install/) and [Docker Compose](https://docs.docker.com/compose/install/)
- We recommend using [Symfony CLI](https://symfony.com/download) on your local environment.
  Otherwise you can adapt the given commands to be executed inside your PHP-FPM container.
- If you want to do this, you will need to have PHP 8.4 installed in your host system with the following extensions :
  - curl
  - redis
  - amqp
  - xml
  - dom
  - simplexml
  - zip
  - intl
  - pdo-mysql

### Procedure

First, clone the repository on your local environment :

```sh
git clone git@github.com:Kern046/asylamba-game.git kalaxia
```

Move to the cloned repository and then move to the adequate branch (don't hesitate to ask which one it is currently since we are not using `master` for now)

```shell
cd kalaxia
git checkout {branch} # Insert the current main branch, which is currently changing regularly
```

Launch the Docker containers :

```shell
docker compose up -d
```

Then, install the project dependencies and setup the database :

```shell
# Install the PHP dependencies
symfony composer install
# Install the frontend dependencies
symfony console importmap:install
# Setup the database schema using Doctrine ORM
symfony console doctrine:schema:update --force --complete
# Initialize a new game data in Kalaxia (players, map systems, planets...)
# If you launch this on
symfony console app:hephaistos:populate-database
```

> If you prefer to launch the commands inside your Docker container, just type `docker exec -it kalaxia_app sh` and then replace `symfony console` with `php bin/console` in the given commands.

> Currently investigating a connection issue between Symfony CLI running on the host and the database.

If you are working on the UI, launch Tailwind watcher :
```shell
symfony console tailwind:build --watch
```

# Troubleshooting

### Failing ```symfony composer install``` :
![img.png](assets/readme-docs/img.png)
#### Solution :
```shell
mkdir config/doctrine/hephaistos
```
---

### If the command `populate-database` fails, you try again and obtain this issue : ![img_1.png](assets/readme-docs/img_1.png)
#### Solution :
Increase your MEMORY_LIMIT inside php.ini if it is currently 256M and then reboot your Symfony server and Docker. Then launch :
```shell
symfony console doctrine:schema:drop --force
symfony console doctrine:schema:update --force --complete
symfony console app:hephaistos:populate-database
```

> The commmand `populate-database` may take time.
