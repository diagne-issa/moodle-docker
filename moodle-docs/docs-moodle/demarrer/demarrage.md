---
icon: material/rocket
---

<div class="page-head" markdown="1">
<p class="crumb">Démarrer · 02</p>

# Lancer la plateforme

Les commandes du quotidien, et le réflexe qui évite de perdre une heure sur un changement qui
« ne marche pas ».
</div>

## Prérequis

La plateforme tourne entièrement dans Docker : rien d'autre n'est à installer, et le
fonctionnement est **identique sur Windows, macOS et Linux**.

| Système | À installer |
|---|---|
| Windows | [Docker Desktop](https://www.docker.com/products/docker-desktop/) et Git |
| macOS | [Docker Desktop](https://www.docker.com/products/docker-desktop/) et Git |
| Linux | Docker Engine, le plugin Compose et Git |

<div class="note" markdown="1">
<b>Vérifier que Docker est prêt</b>
`docker --version` puis `docker compose version` doivent répondre sans erreur. Sous Windows et
macOS, Docker Desktop doit être lancé avant toute commande.
</div>

## Récupérer le projet

```bash
git clone <URL-DU-DEPOT> lms-plateform
cd lms-plateform/moodle-docker
```

Toutes les commandes de cette documentation se lancent **depuis le dossier `moodle-docker`**.
Chacun le place où il veut sur sa machine ; seul le chemin d'accès change.

<div class="tip" markdown="1">
<b>Quel terminal utiliser</b>
Windows : PowerShell, le Terminal Windows ou WSL. macOS et Linux : le terminal du système. Les
commandes <code>docker compose</code> sont les mêmes partout.
</div>

## Créer son fichier .env

Étape obligatoire, à faire **une seule fois** après le clone. Les identifiants de la base et du
compte administrateur ne sont pas versionnés : chacun crée les siens en partant du modèle fourni.

```bash
cp .env.example .env
```

Ouvrez ensuite `.env` et remplacez toutes les valeurs `replace_with_...` par vos propres mots de
passe. Vérifiez surtout que `POSTGRES_PASSWORD` et `MOODLE_DB_PASSWORD` contiennent **la même
valeur**, sans quoi Moodle ne pourra pas joindre sa base.

<div class="warn" markdown="1">
<b>Ne jamais versionner son <code>.env</code></b>
Le fichier est volontairement listé dans <code>.gitignore</code>. Il contient des mots de passe :
s'il part sur GitHub, ils sont exposés à tous ceux qui ont accès au dépôt. Le fichier
<code>config.php</code> de Moodle ne contient d'ailleurs aucun secret, il lit ces variables.
</div>

## Démarrage

```bash
docker compose up -d

# Vérifier que les trois conteneurs tournent
docker compose ps
```

La plateforme est ensuite accessible sur **http://localhost:8090**.

<div class="note" markdown="1">
<b>Premier démarrage</b>
La toute première fois, Docker construit l'image : comptez plusieurs minutes. Les démarrages
suivants sont quasi instantanés.
</div>

## Installer la base la première fois

Sur une machine neuve, la base est vide et Moodle n'a encore aucune table. **Ne passez pas par
l'assistant web** : il est manuel, non reproductible, et demande de ressaisir des informations que
`.env` contient déjà. Une seule commande suffit.

```bash
docker compose exec -u www-data moodle php /var/www/html/admin/cli/install_database.php \
  --agree-license \
  --fullname="Université Rose Dieng France-Sénégal" \
  --shortname="URDFS" \
  --adminuser=admin \
  --adminemail=admin@urdfs.sn \
  --adminpass='VotreMotDePasseSolide'
```

Moodle crée alors toutes ses tables et le compte administrateur, en lisant la connexion à la base
dans `config.php`, qui la tient lui-même de `.env`. Rien à saisir, rien à deviner.

<div class="tip" markdown="1">
<b>Deux causes d'échec fréquentes</b>
Le mot de passe administrateur doit respecter la politique de Moodle : au moins huit caractères,
avec une majuscule, un chiffre et un caractère spécial. Sinon la commande s'arrête sans message
très explicite. Et si la connexion à la base échoue, vérifiez que <code>POSTGRES_PASSWORD</code> et
<code>MOODLE_DB_PASSWORD</code> portent bien <b>la même valeur</b> dans <code>.env</code>.
</div>

<div class="warn" markdown="1">
<b>Si l'assistant web s'affiche malgré tout</b>
C'est que <code>config.php</code> n'a pas été trouvé. Le serveur de base de données à saisir est
alors <code>postgres</code>, le nom du service Docker, et <strong>jamais</strong>
<code>localhost</code> : Moodle tourne dans un conteneur et joint PostgreSQL par le réseau interne.
Les autres champs reprennent les valeurs de <code>.env</code>, avec <code>mdl_</code> comme préfixe
de tables.
</div>

Ensuite, appliquer la configuration du projet, dans cet ordre :

```bash
# Rôles et permissions
cat scripts/setup_roles.php | docker compose exec -T -u www-data moodle php

# Catalogue des formations
cat scripts/setup_offre_formation.php | docker compose exec -T -u www-data moodle php

# Purge finale
docker compose exec -u www-data moodle php /var/www/html/admin/cli/purge_caches.php
```

## Les commandes du quotidien

| Besoin | Commande |
|---|---|
| Appliquer une modification de style ou de template | `docker compose exec -u www-data -w /var/www/html/public moodle php ../admin/cli/purge_caches.php` |
| Exécuter un script du dossier `scripts/` | `cat scripts/NOM.php \| docker compose exec -T -u www-data moodle php` |
| Voir les erreurs PHP en direct | `docker compose logs -f moodle` |
| Ouvrir un shell dans le conteneur | `docker compose exec -u www-data moodle bash` |
| Redémarrer un seul service | `docker compose restart moodle` |
| Arrêter sans rien perdre | `docker compose down` |
| Repartir de zéro <span class="badge no">destructif</span> | `docker compose down -v` |

<div class="warn" markdown="1">
<b><code>down</code> et <code>down -v</code> n'ont rien à voir</b>
`down` arrête les conteneurs mais conserve les volumes : toutes les données sont intactes au
redémarrage. `down -v` **supprime les volumes** : base de données, fichiers déposés, comptes, tout
disparaît. À n'utiliser que pour repartir volontairement d'une installation vierge.
</div>

## Le réflexe : purger les caches

<div class="tip" markdown="1">
<b>Après <em>toute</em> modification dans <code>theme/urdfs/</code></b>
SCSS, mustache ou PHP : il faut purger les caches. Sans ça, Moodle continue de servir la version
compilée précédente et on croit à tort que le changement n'a pas fonctionné. C'est de loin la
première cause de « mais je viens de le modifier ».
</div>

```bash
docker compose exec -u www-data -w /var/www/html/public moodle php ../admin/cli/purge_caches.php
```

Un raccourci fait gagner du temps quand on l'utilise souvent :

```bash
# macOS / Linux / WSL : à ajouter dans ~/.bashrc ou ~/.zshrc
alias mpurge='docker compose exec -u www-data -w /var/www/html/public moodle php ../admin/cli/purge_caches.php'
```

```powershell
# Windows PowerShell : à ajouter dans le profil ($PROFILE)
function mpurge { docker compose exec -u www-data -w /var/www/html/public moodle php ../admin/cli/purge_caches.php }
```

## Vider le cache navigateur aussi

Le CSS compilé est servi avec une URL versionnée, mais en cas de doute : ++ctrl+shift+r++ pour un
rechargement forcé.

<div class="tip" markdown="1">
<b>Tester un autre rôle sans se déconnecter</b>
Ouvrir une **fenêtre de navigation privée** et s'y connecter avec le compte étudiant ou enseignant.
La session administrateur reste ouverte dans la fenêtre normale, ce qui permet de corriger et de
vérifier en parallèle.
</div>

## Vérifier que tout va bien

<ol class="steps" markdown="1">
<li markdown="1"><b>Les trois conteneurs tournent</b>
`docker compose ps` : `lms-postgres`, `lms-moodle` et `lms-moodle-cron` doivent tous être *Up*.</li>
<li markdown="1"><b>La page d'accueil répond</b>
[http://localhost:8090](http://localhost:8090) affiche la page de connexion URDFS, pas un Moodle
bleu standard.</li>
<li markdown="1"><b>Le cron tourne</b>
`docker compose logs --tail 20 moodle-cron` : une exécution toutes les 60 secondes. C'est lui qui
porte les inscriptions par cohorte et les notifications.</li>
<li markdown="1"><b>Aucune erreur PHP</b>
`docker compose logs --tail 50 moodle`.</li>
</ol>
