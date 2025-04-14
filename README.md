# Kalaxia

## Setup

### Dependencies
- [Docker](https://docs.docker.com/engine/install/) and [Docker Compose](https://docs.docker.com/compose/install/)
- We recommend using [Symfony CLI](https://symfony.com/download) on your local environment.
  Otherwise you can adapt the given commands to be executed inside your PHP-FPM container.

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
# Setup the database schema using Doctrine ORM
symfony console doctrine:schema:update --force --complete
# Initialize a new game data in Kalaxia (players, map systems, planets...)
symfony console app:hephaistos:populate-database
```

If you are working on the UI, launch Tailwind watcher :
```shell
symfony console tailwind:build --watch
```

# Troubleshooting

### Problème lors du ```symfony composer install``` :
![img.png](assets/readme-docs/img.png)
#### La solution :
```shell
mkdir config/doctrine/hephaistos
```
---

### Si la commande populate-database à planté, que vous la relancez dans la foulée et que vous obtenez cette erreur : ![img_1.png](assets/readme-docs/img_1.png)
#### La solution :
Pensez à agrandir votre MEMORY_LIMIT dans votre php.ini s'il est à 256M et relancez votre serveur Symfony et docker. Ensuite faite :
```shell
symfony console doctrine:database:drop --force
symfony console doctrine:database:create
symfony console doctrine:schema:update --force --complete
symfony console app:hephaistos:populate-database
```
La commmande populate-database peut prendre un peu de temps.

---

### Si vous avez ce message d'erreur :
![img_2.png](assets/readme-docs/img_2.png)
#### La solution :
```shell
symfony server:ca:install
```
Et relancer le serveur Symfony
