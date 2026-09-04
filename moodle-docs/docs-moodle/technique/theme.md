---
icon: material/palette
---

<div class="page-head" markdown="1">
<p class="crumb">Technique · 04</p>

# Le thème `theme_urdfs`

Thème enfant de Boost. Il ne remplace pas Moodle : il l'habille et ajoute une navigation propre à
l'URDFS, sans jamais toucher au cœur.
</div>

## Arborescence

<div class="tree">
<b>theme/urdfs/</b>
├── <b>config.php</b>          <i>Déclaration du thème, parent = boost</i>
├── <b>version.php</b>         <i>Numéro de version du plugin</i>
├── <b>settings.php</b>        <i>Réglages exposés dans l'administration</i>
├── <b>lib.php</b>             <i>Pipeline SCSS + arbres de navigation par rôle</i>
├── <b>classes/output/</b>
│   └── core_renderer.php  <i>Sidebar, tableaux de bord, classes de rôle</i>
├── <b>scss/</b>
│   ├── _variables.scss    <i>Palette, typographie, rayons, injecté AVANT Bootstrap</i>
│   └── _custom.scss       <i>~5 600 lignes, organisées en « paliers »</i>
├── <b>templates/</b>
│   ├── core/loginform.mustache        <i>Page de connexion sur mesure</i>
│   └── theme_boost/drawers.mustache   <i>Layout principal + JS de la sidebar</i>
├── <b>fonts/</b>             <i>Plus Jakarta Sans + Inter en local (woff2)</i>
├── <b>lang/fr, lang/en</b>   <i>Chaînes de langue</i>
└── <b>pix/</b>               <i>Logos et icônes URDFS</i>
</div>

## Le pipeline SCSS

Moodle compile le SCSS en trois temps, et `lib.php` se branche sur chacun d'eux en **déléguant** au
parent plutôt qu'en codant les chemins en dur. C'est ce qui rend le thème résistant aux mises à
jour.

<ol class="steps" markdown="1">
<li markdown="1"><b>Pre-SCSS : <code>theme_urdfs_get_pre_scss()</code></b>
Injecte `_variables.scss` *avant* Bootstrap. Redéfinir `$primary` ici recolore automatiquement
boutons, liens, badges et alertes partout dans Moodle, sans écrire une seule règle CSS.</li>
<li markdown="1"><b>Main SCSS : <code>theme_urdfs_get_main_scss_content()</code></b>
Délègue à `theme_boost_get_main_scss_content()` : on récupère l'intégralité du CSS de Boost, tel
quel, quelle que soit sa version.</li>
<li markdown="1"><b>Extra SCSS : <code>theme_urdfs_get_extra_scss()</code></b>
Ajoute `_custom.scss` *après* tout le reste, donc avec la priorité finale. C'est là que vit la
couche URDFS.</li>
</ol>

<div class="warn" markdown="1">
<b>Ne jamais coder un chemin en dur</b>
Le thème appelait autrefois `file_get_contents('theme/boost/scss/pre.scss')`. Ce chemin a changé à
la montée de version et la compilation a échoué. En appelant `theme_boost_get_pre_scss()`, c'est
Moodle qui résout le chemin : le thème ne peut plus casser sur une réorganisation de fichiers.
</div>

<div class="warn" markdown="1">
<b>Piège du compilateur SCSS de Moodle</b>
`scssphp` interprète `@import url("https://…")` comme un fichier local et fait échouer **toute** la
compilation. Les polices Google ne peuvent donc pas être importées en SCSS : elles sont chargées par
des balises `<link>` injectées dans `standard_head_html()`, et les fichiers `.woff2` sont embarqués
dans `fonts/`.
</div>

## Organisation de `_custom.scss`

Le fichier est découpé en « paliers » numérotés, chacun précédé d'un bloc de commentaire qui explique
*pourquoi* la règle existe. Pour repérer une section, chercher `PALIER`.

| Paliers | Contenu |
|---|---|
| 1 – 5 | Fondations, coquille de page, sidebar sur mesure, tableau de bord |
| 6 – 12 | Gestion des cours, catégories, tableaux, listes, carnet de notes |
| 13 – 19 | Formulaires, modales, blocs, réglages, mode sombre, profil |
| 20 – 26 | Activités, calendrier, page de connexion, accueil, administration |
| 27 (a → g) | Micro-finitions : BigBlueButton, pastilles de compteur, espaces enseignant et technopédagogue, sidebar libellé/chevron |
| 28 | Respiration globale du contenu |
| 29 | Tableaux larges : défilement dans leur cadre, en-tête figée |
| 30 – 31 | Interface de correction des devoirs, gestionnaire de fichiers |
| 32 | Boutons d'action collés au contenu, correctif transverse |
| 33 | Rapports de journaux : liste courte qui défile |

<div class="tip" markdown="1">
<b>Règle de travail</b>
Un défaut d'affichage qui se répète sur plusieurs pages se corrige dans les paliers 28+, avec une
règle transverse portée sur `#region-main` : jamais page par page. Le *scoping* sur `#region-main`
est essentiel : sans lui, une règle destinée aux boutons du contenu peut repeindre la sidebar
entière.
</div>

## Les templates surchargés

| Fichier | Ce qu'il fait |
|---|---|
| `core/loginform.mustache` | Carte de connexion URDFS : titre, sous-titre, libellés visibles, « se souvenir de moi », mot de passe oublié, bouton vert. Le `logintoken`, le champ d'ancre et le bloc `{{#js}}` d'origine sont préservés ; les retirer casse l'authentification. |
| `theme_boost/drawers.mustache` | Layout principal. Injecte `output.urdfs_sidebar` et les quatre tableaux de bord. Contient le JS de la sidebar : repli mémorisé en `localStorage`, accordéon des sous-menus, drawer mobile, recherche dans le menu. |

<div class="warn" markdown="1">
<b>Ne jamais définir <code>$THEME->layouts</code></b>
Dans `config.php`, déclarer `$THEME->layouts` *remplace* l'intégralité des layouts hérités de Boost
au lieu de les compléter. Résultat : des pages entières deviennent inaccessibles. Le tableau est
volontairement laissé vide, avec un commentaire d'avertissement dans le fichier.
</div>

## Le renderer

`classes/output/core_renderer.php` étend `\theme_boost\output\core_renderer` et ajoute :

- `standard_head_html()` : injecte les polices et les métadonnées URDFS ;
- `body_attributes()` : pose une classe `urdfs-role-admin`, `-teacher`, `-student` ou `-guest` sur
  le `<body>`, ce qui permet de styler selon le profil en CSS pur ;
- `urdfs_sidebar()` : construit la navigation en choisissant l'arbre selon le rôle ;
- les quatre méthodes de tableau de bord, chacune ne s'affichant que pour son profil.

### Détection du rôle

```php
is_siteadmin()                        // → arbre Administrateur
theme_urdfs_is_technopedagogue()      // → arbre Technopédagogue
theme_urdfs_is_teacher()              // → arbre Enseignant
sinon                                 // → arbre Étudiant
```

`theme_urdfs_is_technopedagogue()` teste `moodle/course:create` au niveau système, capability
caractéristique du rôle. `theme_urdfs_is_teacher()` vérifie d'abord le cours courant, puis, hors
d'un cours, retombe sur « l'utilisateur enseigne-t-il au moins un cours ? ». Sans ce repli, un
enseignant voyait le menu étudiant dès qu'il quittait ses cours.

<div class="note" markdown="1">
<b>L'ordre des tests compte</b>
Il est volontairement du plus spécifique au plus général, et chaque fonction commence par exclure
`is_siteadmin()`. Sans ces exclusions, un administrateur déclenchait aussi les branches
technopédagogue et enseignant, et se retrouvait avec plusieurs tableaux de bord empilés.
</div>

## Les quatre navigations

<div class="grid g2" markdown="1">
<div class="card" markdown="1">
#### Technopédagogue

- **Pilotage** : Tableau de bord
- **Pédagogie** : Cours, Catégories
- **Utilisateurs** : Utilisateurs, Classes / Cohortes
- **Suivi** : Rapports
- **Organisation** : Calendrier, Messages
</div>
<div class="card" markdown="1">
#### Administrateur

- **Pilotage** : Tableau de bord
- **Gestion** : Utilisateurs, Cours
- **Suivi** : Rapports, Calendrier
- **Système** : Réglages, Plugins, Apparence, Serveur, Sécurité
</div>
<div class="card" markdown="1">
#### Enseignant

- Tableau de bord
- Mes cours : sous-menu *dynamique*, une entrée par cours enseigné
- Mes étudiants, Devoirs à corriger
- Calendrier, Messages
</div>
<div class="card" markdown="1">
#### Étudiant

- Tableau de bord
- Mes cours
- Calendrier
- Mes notes
- Messages
</div>
</div>

<div class="tip" markdown="1">
<b>Libellé cliquable, chevron séparé</b>
Sur les entrées à sous-menu, le libellé est un lien qui **navigue** vers la page principale, et le
chevron est un bouton distinct qui **déplie**. Avant cette séparation, cliquer sur « Cours » ne
faisait qu'ouvrir le sous-menu, sans jamais mener à la liste des cours, un comportement contre-intuitif
signalé pendant les tests.
</div>

## Les tableaux de bord

| Profil | Contenu |
|---|---|
| Technopédagogue | Bandeau de bienvenue, 4 indicateurs clés, histogramme des inscriptions sur 6 mois, et carte « actions rapides » côte à côte avec le graphique. |
| Enseignant | Bandeau à liseré vert, 4 statistiques, **« Travaux à corriger »** calculé via `count_submissions_need_grading()`, et échéances à venir. |
| Étudiant | Bandeau personnalisé, cours en cours, progression, prochaines échéances. |
| Administrateur | Vue de pilotage technique, réservée à `is_siteadmin()`. |

Chaque méthode commence par un test d'exclusion. Le garde-fou `urdfs_is_mycourses_page()` évite par
ailleurs que le tableau de bord ne se duplique sur `/my/courses.php`, qui partage le même layout.
