# Thème Moodle URDFS

Thème enfant de **Boost**, aux couleurs de l'URDFS, conçu pour offrir un
rendu cohérent avec le frontend React (`moodle-frontend`) sur les 3 espaces :
**administrateur**, **enseignant** et **étudiant**.

## Structure du plugin

```
theme/urdfs/
├── classes/
│   └── output/
│       └── core_renderer.php   # Ajoute une classe de body selon le rôle
├── lang/
│   ├── en/theme_urdfs.php
│   └── fr/theme_urdfs.php
├── scss/
│   ├── _variables.scss         # Couleurs, rayons, polices (tokens React)
│   └── _custom.scss            # Styles des composants (cartes, drawer, login...)
├── config.php
├── lib.php                      # Pipeline SCSS (pre/extra/post)
├── settings.php                 # Page de réglages admin (onglet "Réglages généraux")
├── version.php
└── README.md
```

## Activation (intégration Docker)

Le thème est monté en volume dans `docker-compose.yml` :

```yaml
moodle:
  volumes:
    - moodledata:/var/www/moodledata
    - ./theme/urdfs:/var/www/html/theme/urdfs
```

Étapes :

1. Démarrer/redémarrer la stack :
   ```
   docker compose up -d
   ```
2. Aller dans **Administration du site → Apparence → Thèmes → Sélecteur de thème**
   et choisir **URDFS**.
3. Vider le cache de thème : **Administration du site → Développement → Purger
   tous les caches** (ou `php admin/cli/purge_caches.php` dans le conteneur).

Comme le dossier est monté en volume, toute modification des fichiers
`scss/`, `classes/`, `lang/`, etc. est immédiatement visible côté conteneur.
Il suffit de purger les caches (SCSS notamment) pour voir les changements,
sans reconstruire l'image Docker.

## Comment fonctionnent les 3 espaces

Le thème **ne duplique pas** les pages Moodle par rôle : il s'appuie sur le
même squelette Boost, mais :

- `classes/output/core_renderer.php` détecte le rôle de l'utilisateur connecté
  (`is_siteadmin()`, capacités d'enseignant via `has_capability()`, sinon
  étudiant) et ajoute une classe au `<body>` :
  - `urdfs-role-admin`
  - `urdfs-role-teacher`
  - `urdfs-role-student`
  - `urdfs-role-guest` (visiteur / non connecté)
- `scss/_custom.scss` utilise ces classes pour appliquer une **couleur
  d'accent** différente par espace (navy / bleu / vert), reprenant les
  couleurs déjà définies dans le frontend React (`$urdfs-admin-accent`,
  `$urdfs-teacher-accent`, `$urdfs-student-accent`).

## Plan pour aller plus loin (3 dashboards)

Pistes pour rapprocher encore plus le rendu Moodle du frontend React,
inspirées de Blackbox/Teachup :

1. **Dashboard admin**
   - Page d'accueil personnalisée (`my/index.php` ou bloc dédié) avec des
     cartes de statistiques (nombre d'utilisateurs, cours, inscriptions),
     stylées via `.card` (déjà prêtes dans `_custom.scss`).
   - Réorganiser la navigation latérale (`#nav-drawer`) pour mettre en avant :
     Utilisateurs, Cours, Catégories, Rapports.

2. **Dashboard enseignant**
   - Mettre en avant le bloc "Mes cours" en grille de cartes plutôt qu'en
     liste.
   - Ajouter un raccourci vers la notation (`mod/assign:grade`) et les
     messages, avec le badge `.urdfs-space-badge` (couleur teacher).

3. **Dashboard étudiant**
   - Vue "calendrier + progression" avec les barres `.progress` déjà stylées
     en vert (accent étudiant).
   - Mettre en avant les prochaines échéances (devoirs, quiz).

4. **Templates Mustache**
   - Pour aller plus loin que le SCSS, surcharger des templates Mustache
     spécifiques (ex. `core/drawer`, `theme_boost/columns2`) dans
     `theme/urdfs/templates/` afin de réorganiser la structure HTML
     (en-tête, barre latérale) et se rapprocher davantage du layout React
     (sidebar fixe, topbar avec recherche, etc.).

5. **Web Services / React**
   - Le frontend React (`moodle-frontend`) reste utile pour des outils
     transverses (tableaux de bord avancés, exports, automatisations) en
     consommant l'API Moodle Web Services, en complément de ce thème qui
     gère l'expérience native Moodle.
