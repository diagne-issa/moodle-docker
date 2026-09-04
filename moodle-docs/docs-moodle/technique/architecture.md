---
icon: material/docker
---

<div class="page-head" markdown="1">
<p class="crumb">Technique · 03</p>

# Architecture Docker

Trois services, deux volumes, quatre montages depuis le disque. Et une subtilité sur le conteneur
cron qui a coûté plusieurs heures.
</div>

## Les trois services

Orchestrés par `docker-compose.yml`, sur un réseau bridge dédié `lms-network`.

| Service | Image | Rôle |
|---|---|---|
| `postgres` | `postgres:17` | Base de données. Volume nommé `postgres_data`, avec *healthcheck* : Moodle n'est démarré qu'une fois la base réellement prête à accepter des connexions. |
| `moodle` | `lms-moodle:local` | Serveur web + PHP. Construit depuis le `Dockerfile` local. Publié sur le port **8090**. |
| `moodle-cron` | `lms-moodle:local` | Boucle qui exécute `admin/cli/cron.php` toutes les 60 secondes. Indispensable : notifications, inscriptions par cohorte, rapports, purges programmées. |

<div class="note" markdown="1">
<b>Le port 8090</b>
Choisi volontairement plutôt que le 8080, trop souvent occupé par un autre projet. Il est déclaré à
la fois dans `docker-compose.yml` (`"8090:80"`) et dans `config.php` (`$CFG->wwwroot`). **Les deux
doivent concorder**, sinon Moodle génère des URL invalides et les redirections tournent en boucle.
</div>

## Persistance : ce qui survit, ce qui disparaît

<div class="grid g2" markdown="1">
<div class="card" markdown="1">
#### Volumes nommés

- `postgres_data` : toute la base de données
- `moodledata` : fichiers déposés, caches, sessions

Survivent à `docker compose down`. Détruits par `down -v`.
</div>
<div class="card" markdown="1">
#### Montages depuis le disque

- `./config.php`
- `./theme/urdfs`
- `./theme/moove`

Éditables directement : le changement est visible sans reconstruire l'image.
</div>
</div>

<div class="warn" markdown="1">
<b>Les thèmes sont montés dans les DEUX conteneurs</b>
`moodle` et `moodle-cron` partagent exactement les mêmes montages. Ce n'est pas de la redondance :
le cron reconstruit la table des composants (`moodledata/cache/core_component.php`). S'il ne voit pas
les thèmes, il génère une table incomplète et le site tombe avec
`Class "theme_moove\util\settings" not found` : une panne intermittente particulièrement pénible à
diagnostiquer, puisqu'elle survient plusieurs minutes après un déploiement apparemment réussi.
</div>

## Version figée

Le `Dockerfile` reçoit `MOODLE_BRANCH` depuis `.env`. Cette variable pointe sur le **tag** `v5.1.6`,
pas sur une branche.

```bash
# .env
MOODLE_BRANCH=v5.1.6
```

Auparavant elle suivait une branche mobile. Une simple reconstruction de l'image a fait passer le
site de 5.1.5 à 5.1.6 sans intervention, en cassant au passage un chemin SCSS codé en dur. Avec un
tag, la même commande produit toujours exactement la même version.

<div class="tip" markdown="1">
<b>Monter de version plus tard</b>
Changer le tag dans `.env`, sauvegarder les volumes, puis
`docker compose build --no-cache && docker compose up -d`. Moodle détecte la nouvelle version et
propose la mise à niveau de la base. Toujours tester sur une copie d'abord.
</div>

## `config.php`

Ce fichier existe sur le disque et est monté dans les conteneurs. Il vivait initialement dans le
système de fichiers éphémère du conteneur et disparaissait à chaque `docker compose down`, obligeant
à tout réinstaller.

```php
$CFG->dbtype    = 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = 'postgres';
$CFG->dbname    = 'moodle';
$CFG->dbuser    = 'urdfs.moodle.sa';

$CFG->wwwroot   = 'http://localhost:8090';
$CFG->dataroot  = '/var/www/moodledata';

// Environnement de démonstration : aucun serveur SMTP disponible.
$CFG->noemailever  = 1;
$CFG->debug        = 0;
$CFG->debugdisplay = 0;

require_once(__DIR__ . '/lib/setup.php');
```

### Pourquoi ces trois réglages

- `noemailever = 1` : sans serveur SMTP, chaque remise de devoir déclenchait une erreur d'envoi
  bloquante. Ce réglage fait avaler silencieusement les courriels.
- `debug = 0` et `debugdisplay = 0` : suppriment les traces rouges affichées en pleine page.
  Indispensable devant un public.

<div class="warn" markdown="1">
<b>Pour développer, remettre le débogage</b>
En phase de mise au point, `$CFG->debug = 32767;` et `$CFG->debugdisplay = 1;` font apparaître les
erreurs PHP exactes. Ne surtout pas laisser ces valeurs pour une démonstration ou en production.
</div>

## Le dossier du projet

<div class="tree">
<b>moodle-docker/</b>
├── <b>docker-compose.yml</b>   <i>Les trois services</i>
├── <b>Dockerfile</b>           <i>Image Moodle, version épinglée</i>
├── <b>.env</b>                 <i>Variables : version, base, admin</i>
├── <b>config.php</b>           <i>Configuration Moodle, montée dans les conteneurs</i>
├── <b>php.ini</b>              <i>Limites d'upload, mémoire</i>
├── <b>theme/urdfs/</b>         <i>Le thème URDFS, le cœur du travail</i>
├── <b>theme/moove/</b>         <i>Thème tiers, désormais inutilisé</i>
├── <b>scripts/</b>             <i>Scripts CLI idempotents</i>
└── <b>docs/</b>                <i>Cette documentation</i>
</div>

<div class="note" markdown="1">
<b>Travailler dans le bon dossier</b>
Seul le dossier **cloné depuis le dépôt Git** est monté dans les conteneurs : c'est celui que
Docker lit. Une copie du projet posée ailleurs sur le disque n'est pas montée, et toute
modification qui y est faite reste sans effet, cause classique du « pourquoi mes changements ne
s'appliquent pas ». En cas de doute : `docker compose config` affiche les chemins réellement
montés.
</div>
