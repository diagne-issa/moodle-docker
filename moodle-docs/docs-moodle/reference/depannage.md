---
icon: material/wrench
---

<div class="page-head" markdown="1">
<p class="crumb">Référence · 13</p>

# Dépannage

Les pannes déjà rencontrées sur ce projet, leur cause réelle et leur correctif. Toutes ont été
diagnostiquées et résolues.
</div>

## Méthode de diagnostic

<ol class="steps" markdown="1">
<li markdown="1"><b>Les conteneurs tournent-ils ?</b>
`docker compose ps` : les trois doivent être *Up*.</li>
<li markdown="1"><b>Quelle est l'erreur exacte ?</b>
`docker compose logs -f moodle`, puis reproduire l'action qui échoue.</li>
<li markdown="1"><b>Est-ce un cache ?</b>
Purger les caches Moodle, puis recharger avec ++ctrl+shift+r++.</li>
<li markdown="1"><b>Est-ce la session ?</b>
Tester dans une fenêtre de navigation privée.</li>
<li markdown="1"><b>Est-ce une permission ?</b>
Relancer `setup_roles.php`, purger, puis se déconnecter et se reconnecter.</li>
</ol>

<div class="tip" markdown="1">
<b>Neuf fois sur dix</b>
C'est un cache non purgé, ou une modification faite dans un autre dossier que celui monté par
Docker (une copie du projet restée sur le disque). Vérifier ces deux points avant de chercher plus
loin.
</div>

## Affichage et thème

| Symptôme | Cause | Correctif |
|---|---|---|
| Une modification n'a aucun effet | CSS compilé en cache, ou fichier édité hors du dossier monté | Purger les caches ; vérifier qu'on travaille dans le dépôt cloné |
| Page blanche après édition d'un template | Balise `{{#js}}` / `{{/js}}` déséquilibrée, ou bloc placé dans un commentaire mustache | Vérifier l'appairage des balises ; `docker compose logs -f moodle` |
| Des pages entières deviennent inaccessibles | `$THEME->layouts` défini : il remplace tous les layouts hérités | Laisser le tableau vide dans `theme/urdfs/config.php` |
| Toute la compilation SCSS échoue | `@import url("https://…")` : scssphp le prend pour un fichier local | Charger les polices par `<link>` dans `standard_head_html()` |
| Encadrés blancs vides un peu partout | `.form-control-feedback` contient des espaces : `:empty` ne matche pas | `display:none` global, réaffiché sur `.is-invalid ~` |
| Un bouton collé au tableau qui le suit | Moodle n'applique aucune marge entre les deux | Règle transverse du palier 32 : `.btn ~ table, .btn ~ .table-responsive { margin-top:1.5rem }` |
| Deux zones de dépôt de fichiers superposées | `.dndupload-target` forcé en `display:flex` alors qu'il doit rester masqué jusqu'au survol | Ne styler que l'apparence, jamais l'affichage ; `.fm-content-wrapper` en `position:relative` |

## Docker et infrastructure

| Symptôme | Cause | Correctif |
|---|---|---|
| `Class "theme_moove\util\settings" not found` | Le conteneur cron ne voyait pas les thèmes et régénérait une table de composants incomplète | Monter les thèmes dans `moodle-cron` aussi, puis purger les caches |
| `config.php` disparaît après `down` | Le fichier vivait dans le système de fichiers éphémère du conteneur | Créer le fichier sur le disque et le monter dans les deux services |
| Moodle s'est mis à jour tout seul | `MOODLE_BRANCH` pointait sur une branche mobile | Épingler le tag `v5.1.6` dans `.env` |
| Redirections en boucle à la connexion | `$CFG->wwwroot` ne correspond pas au port publié | Aligner `wwwroot` et le mapping de port de `docker-compose.yml` |
| Les inscriptions par cohorte n'arrivent jamais | Le conteneur cron ne tourne pas | `docker compose logs --tail 20 moodle-cron` ; redémarrer le service |

## Rôles et permissions

| Symptôme | Cause | Correctif |
|---|---|---|
| « Accès refusé » sur une page attendue | Capability manquante dans la liste Allow | Ajouter la capability dans `setup_roles.php`, relancer, purger, se reconnecter |
| Import CSV refusé | `moodle/site:uploadusers` absent | Ajouté à la liste Allow du technopédagogue |
| « Utilisateurs en ligne » refusé | `report/loglive:view` et `report/log:viewlive` absents | Ajoutés à la liste Allow |
| Erreur *Invalid context id specified* | URL sans paramètre `contextid` | Utiliser `/cohort/edit.php?contextid=1` |
| Un enseignant voit le menu étudiant | La détection ne testait que le cours courant | Repli sur « enseigne-t-il au moins un cours ? » dans `theme_urdfs_is_teacher()` |
| Un compte de pilotage apparaît comme étudiant | Il avait été ajouté à une cohorte de classe | `add_students_to_cohort.php` exclut désormais admins et technopédagogues |
| Une modification de rôle ne prend pas | Les permissions sont en cache dans la session | L'utilisateur doit se déconnecter et se reconnecter |

## Contenu et affichage des données

| Symptôme | Cause | Correctif |
|---|---|---|
| Deux tableaux de bord superposés | Plusieurs méthodes de *hero* s'affichaient pour le même utilisateur | Tests d'exclusion mutuelle + garde `urdfs_is_mycourses_page()` |
| Erreurs à la remise d'un devoir | Aucun serveur SMTP configuré | `noemailever=1`, `debug=0`, `debugdisplay=0` |
| `column "…" does not exist` en PostgreSQL | Guillemets doubles dans une requête SQL | Utiliser des paramètres liés `?` |
| Le journal en direct affiche 100 lignes | Valeur codée en dur dans `report_loglive` : aucun paramètre d'URL n'existe | Hauteur du cadre bornée en CSS, défilement interne, en-tête figée (palier 33) |
| Pagination affichée deux fois | Moodle place une pagination avant *et* après le tableau | Masquage global de celle du haut via `ul.pagination:has(~ table)` |
| Un tableau déborde de la page | Trop de colonnes pour la largeur disponible | Palier 29 : le cadre défile horizontalement, la page ne bouge pas |

## Reprendre après une longue interruption

```bash
cd <chemin-vers>/lms-plateform/moodle-docker
docker compose up -d
docker compose ps
docker compose logs --tail 30 moodle
docker compose exec -u www-data -w /var/www/html/public moodle php ../admin/cli/purge_caches.php
```

Puis ouvrir [http://localhost:8090](http://localhost:8090) et vérifier que la page de connexion
affiche bien l'identité URDFS.
