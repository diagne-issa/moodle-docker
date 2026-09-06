---
icon: material/history
---

<div class="page-head" markdown="1">
<p class="crumb">Référence · 12</p>

# Journal des décisions

Les choix structurants du projet, et ce qui les a motivés. Utile pour reprendre le projet dans six
mois, ou pour le défendre devant un jury.
</div>

<div class="card" markdown="1">
#### Thème enfant plutôt que retouches sur `theme_moove`

**Contexte.** Les premières personnalisations modifiaient directement un thème tiers installé depuis
un dépôt externe.

**Problème.** Chaque mise à jour de ce thème aurait tout effacé. Les modifications étaient dispersées
entre plusieurs fichiers appartenant à quelqu'un d'autre, impossibles à isoler ou à réappliquer.

**Décision.** Créer `theme_urdfs`, thème enfant de Boost. Le code URDFS est isolé, survit aux mises à
jour et se versionne proprement. C'est l'approche des universités qui déploient Moodle à grande
échelle.
</div>

<div class="card" markdown="1">
#### Tout dans le code, rien dans la base

**Problème.** Les réglages faits via *Administration → Apparence* vivent en base de données. Ils ne
sont ni versionnés, ni reproductibles, et disparaissent à la moindre réinstallation.

**Décision.** Aucune personnalisation via l'interface d'administration. Palette, typographie,
navigation, templates : tout est dans `theme/urdfs/`. Une réinstallation complète restitue l'identité
visuelle sans aucune manipulation.
</div>

<div class="card" markdown="1">
#### Version de Moodle figée sur le tag `v5.1.6`

**Problème.** Le `Dockerfile` suivait une branche mobile. Une reconstruction de l'image a fait passer
le site de 5.1.5 à 5.1.6 sans intervention, en cassant au passage un chemin SCSS codé en dur.

**Décision.** Épingler le tag. La même commande produit désormais toujours exactement la même
version. Les montées de version deviennent des décisions explicites, pas des accidents.
</div>

<div class="card" markdown="1">
#### Cible de production : Moodle 5.3 LTS, attendue en octobre 2026

**Contexte.** Le développement a démarré sur Moodle 5.1, version courante au moment du lancement du
projet.

**Problème.** 5.1 est une version de transition. Ses correctifs fonctionnels s'arrêtent le
5 octobre 2026 et ses correctifs de sécurité en avril 2027. Ce n'est pas une base sur laquelle
installer une université pour plusieurs années. Redescendre vers l'actuelle LTS 4.5 n'est pas une
option : Moodle ne migre sa base que vers le haut, et 4.5 ne reçoit déjà plus que des correctifs de
sécurité.

**Décision.** Poursuivre le développement sur 5.1, puis **mettre en production sur 5.3 LTS** dès sa
sortie, prévue en octobre 2026. Une LTS offre environ deux ans de support et c'est la version que
visent les auteurs de plugins tiers.

**Conséquence.** La montée se résume à changer `MOODLE_BRANCH` dans `.env`, reconstruire l'image et
lancer `upgrade.php`. Le thème, les rôles et les scripts suivent sans réécriture, précisément parce
que tout vit dans un thème enfant et des scripts idempotents. La montée sera testée sur une copie
avant toute application en production.
</div>

<div class="card" markdown="1">
#### Plugins installés par le `Dockerfile`, pas par l'interface web

**Problème.** Un plugin installé depuis l'interface d'administration vit dans l'image du conteneur.
Il disparaît à la première reconstruction, et rien n'indique à un collègue quelle version installer.

**Décision.** Chaque plugin est cloné dans le `Dockerfile`, sur une branche figée. L'installation
devient reproductible à l'identique pour toute l'équipe et survit aux reconstructions. Après ajout,
`upgrade.php` crée les tables du plugin.

**À savoir.** Le champ `$plugin->requires` d'un plugin déclare une version **minimale** de Moodle.
Une branche prévue pour 5.0 s'installe donc sans problème sur 5.1, alors qu'une branche 5.2 serait
refusée. C'est pourquoi Custom certificate est installé depuis `MOODLE_500_STABLE`, faute de branche
5.1 publiée.
</div>

<div class="card" markdown="1">
#### Déléguer aux fonctions SCSS du parent

**Problème.** Le thème appelait `file_get_contents('theme/boost/scss/pre.scss')`. Ce chemin a changé
à la montée de version, et la compilation a échoué avec un avertissement PHP en pleine page.

**Décision.** Appeler `theme_boost_get_pre_scss()` et `theme_boost_get_main_scss_content()`. C'est
Moodle qui résout les chemins : le thème ne peut plus casser sur une réorganisation de fichiers.
</div>

<div class="card" markdown="1">
#### Permissions réelles plutôt que masquage d'interface

**Problème.** Cacher une entrée de menu en CSS ne protège rien : l'URL reste accessible en la tapant
directement.

**Décision.** Chaque restriction du technopédagogue est un *Prevent* sur la capability correspondante,
et la matrice `role_allow_assign` l'empêche de s'attribuer un rôle supérieur. Le masquage d'interface
ne sert qu'au confort visuel, jamais à la sécurité.
</div>

<div class="card" markdown="1">
#### Cohortes plutôt qu'inscriptions manuelles

**Problème.** Avec des inscriptions manuelles, chaque nouvel étudiant demande autant d'opérations
qu'il y a de cours, et rien n'empêche les mélanges entre promotions.

**Décision.** La cohorte est l'unité « classe ». Une seule action inscrit un étudiant à tous les cours
de sa formation, et aucune fuite entre années n'est possible.
</div>

<div class="card" markdown="1">
#### Thèmes montés aussi dans le conteneur cron

**Problème.** Le site tombait par intermittence avec `Class "theme_moove\util\settings" not found`,
plusieurs minutes après un déploiement apparemment réussi.

**Cause.** Le cron reconstruit la table des composants de Moodle. Sans accès aux thèmes, il générait
une table incomplète qui écrasait la bonne.

**Décision.** Les deux conteneurs partagent exactement les mêmes montages.
</div>

<div class="card" markdown="1">
#### `config.php` sur le disque, monté dans les conteneurs

**Problème.** Le fichier vivait dans le système de fichiers éphémère du conteneur et disparaissait à
chaque `docker compose down`, obligeant à relancer l'installation complète.

**Décision.** Créer le fichier sur le disque et le monter dans `moodle` et `moodle-cron`.
</div>

<div class="card" markdown="1">
#### Ne jamais définir `$THEME->layouts`

**Problème.** Une tentative de surcharger un seul layout a rendu des pages entières inaccessibles.

**Cause.** Déclarer `$THEME->layouts` *remplace* l'intégralité des layouts hérités au lieu de les
compléter.

**Décision.** Tableau laissé vide, avec un commentaire d'avertissement dans le fichier. Les
ajustements de mise en page passent par le CSS et les templates.
</div>

<div class="card" markdown="1">
#### Correctifs globaux, jamais page par page

**Problème.** Les mêmes défauts revenaient sur des dizaines de pages Moodle : boutons collés aux
tableaux, encadrés blancs vides, tableaux qui débordent de la page.

**Décision.** Les paliers 28 à 33 de `_custom.scss` regroupent des règles transverses portées sur
`#region-main`, qui règlent chaque famille de défaut d'un seul geste. Le *scoping* est obligatoire :
une règle non scopée avait un jour repeint la sidebar entière en bleu.
</div>

<div class="card" markdown="1">
#### Vérifier avant d'affirmer

**Contexte.** Pour raccourcir la liste du journal en direct, un paramètre `?perpage=10` a été ajouté
à l'URL. Sans effet : le paramètre n'existe pas dans `report_loglive`, la valeur de 100 lignes étant
codée en dur dans le renderable.

**Décision.** Lire la source du composant concerné avant de supposer qu'un paramètre existe. Le
correctif retenu, borner la hauteur du cadre avec défilement interne, ne dépend d'aucune supposition
sur le cœur de Moodle.
</div>
