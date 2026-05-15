# LMS Platform — Moodle Docker

Instance Moodle conteneurisée pour le développement interne.

## Stack technique

| Composant       | Version / Image                                                                                     |
|-----------------|-----------------------------------------------------------------------------------------------------|
| Moodle          | 5.1 (branche `MOODLE_501_STABLE`)                                                                  |
| PHP             | 8.3 sur Debian Bookworm                                                                            |
| Serveur web     | Apache (via [`moodlehq/moodle-php-apache`](https://hub.docker.com/r/moodlehq/moodle-php-apache))   |
| Base de données | PostgreSQL 17                                                                                       |

## Architecture

```
moodle-docker/
├── .env.example          # Variables d'environnement (template)
├── .gitignore
├── docker-compose.yml    # Orchestration des 3 services
├── Dockerfile            # Image custom Moodle
├── php.ini               # Config PHP personnalisée
└── README.md
```

Trois services Docker :

- **postgres** — base de données PostgreSQL, persistée via un volume nommé
- **moodle** — serveur Apache/PHP servant Moodle sur le port 8080
- **moodle-cron** — même image, exécute le cron Moodle toutes les 60 secondes (pas d'Apache)

## Prérequis

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) installé et lancé
- [Git](https://git-scm.com/downloads)
- Accès au dépôt GitHub du projet

## Récupérer le projet

Commence par **forker** le dépôt sur ton compte GitHub. Ça te permet de travailler sur ta propre copie, de créer des branches et d'ouvrir des pull requests sans toucher au dépôt principal.

1. Sur GitHub, ouvre le dépôt du projet et clique sur **Fork** (en haut à droite)
2. Clone ensuite **ton fork** (remplace `TON_USERNAME`) :

```bash
git clone https://github.com/TON_USERNAME/moodle-docker.git
cd moodle-docker
```

## Configuration

Copie le fichier d'environnement et modifie les mots de passe :

**Mac / Linux :**

```bash
cp .env.example .env
```

**Windows (PowerShell) :**

```powershell
Copy-Item .env.example .env
```

Ouvre `.env` et remplace les valeurs `replace_with_...` par des mots de passe sécurisés. Le mot de passe PostgreSQL doit être identique dans `POSTGRES_PASSWORD` et `MOODLE_DB_PASSWORD`.

## Lancement

Construire l'image et démarrer les services :

```bash
docker compose up -d --build
```

Le premier lancement prend quelques minutes (téléchargement des images, clone de Moodle, installation des dépendances Composer).

Vérifier que tout tourne :

```bash
docker compose ps
```

Les trois conteneurs (`lms-postgres`, `lms-moodle`, `lms-moodle-cron`) doivent être en état `running` ou `healthy`.

## Installation de Moodle

Au premier lancement, Moodle n'est pas encore installé. L'installation se fait via l'interface web pour le moment.

1. Ouvrir **http://localhost:8080** dans le navigateur
2. Suivre l'assistant d'installation
3. Quand il demande la configuration de la base de données, utiliser ces valeurs (celles de ton `.env`) :

| Champ              | Valeur                              |
|--------------------|-------------------------------------|
| Type de base       | PostgreSQL                          |
| Hôte               | `postgres`                          |
| Port               | `5432`                              |
| Nom de la base     | la valeur de `MOODLE_DB_NAME`       |
| Utilisateur        | la valeur de `MOODLE_DB_USER`       |
| Mot de passe       | la valeur de `MOODLE_DB_PASSWORD`   |
| Socket Unix        | *(laisser vide)*                    |

L'installation crée les tables et le fichier `config.php`. Ça prend 2-3 minutes.

## Commandes courantes

**Voir les logs :**

```bash
# Tous les services
docker compose logs -f

# Un service spécifique
docker compose logs -f moodle
docker compose logs -f moodle-cron
```

**Arrêter les services :**

```bash
docker compose down
```

**Tout supprimer et repartir de zéro** (base de données et fichiers Moodle inclus) :

```bash
docker compose down -v
```

**Ouvrir un shell dans le conteneur Moodle :**

```bash
docker compose exec moodle bash
```

**Vider le cache Moodle :**

```bash
docker compose exec -u www-data moodle php admin/cli/purge_caches.php
```

## Dépannage

**Le conteneur `moodle` ne démarre pas :**
Vérifier que Docker Desktop est lancé et que le port 8080 n'est pas déjà utilisé par une autre application.

**Erreur de connexion à la base de données :**
Vérifier que les mots de passe dans `.env` sont identiques entre `POSTGRES_PASSWORD` et `MOODLE_DB_PASSWORD`, et que le conteneur `lms-postgres` est `healthy` (`docker compose ps`).

**Le cron ne tourne pas :**
Vérifier les logs avec `docker compose logs -f moodle-cron`. Le service attend que `moodle` et `postgres` soient prêts avant de démarrer.