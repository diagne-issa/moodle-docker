---
icon: material/school
---

<div class="page-head" markdown="1">
<p class="crumb">Technique · 07</p>

# Architecture pédagogique

La structure académique est portée par les catégories et les cohortes de Moodle, sans aucun plugin
supplémentaire.
</div>

## La hiérarchie

<div class="tree">
<b>Domaine</b>            <i>ex. Numérique, Santé, Agriculture, Industrie, Services</i>
  └─ <b>Formation</b>      <i>ex. Génie Informatique</i>
       └─ <b>Année</b>     <i>L1, L2, L3 / M1, M2</i>
            └─ <b>Classe (cohorte)</b>   <i>ex. L1-DATA-2026</i>
                 ├─ <b>Étudiants</b>     <i>membres de la cohorte</i>
                 └─ <b>Cours</b>         <i>inscription automatique par cohorte</i>
                      └─ <b>Enseignants</b>
</div>

Les trois premiers niveaux sont des **catégories de cours** imbriquées. La classe est une
**cohorte**. Le lien entre les deux est la méthode d'inscription « synchronisation des cohortes ».

## Pourquoi des cohortes plutôt que des inscriptions manuelles

<div class="grid g3" markdown="1">
<div class="tile green" markdown="1">
#### Étanchéité
Un étudiant de L1-2026 ne voit jamais les cours de L2, et deux promotions successives restent
totalement séparées.
</div>
<div class="tile green" markdown="1">
#### Une action, tout un groupe
Ajouter un étudiant à `L1-DATA-2026` l'inscrit automatiquement à *tous* les cours liés à cette
cohorte.
</div>
<div class="tile green" markdown="1">
#### Réversible
Le retirer de la cohorte le désinscrit partout, sans avoir à passer sur chaque cours.
</div>
</div>

Avec des inscriptions manuelles, chaque nouvel étudiant demanderait autant d'opérations qu'il y a de
cours dans sa formation. Sur une promotion de 40 étudiants et 8 cours, cela représente 320
inscriptions à saisir, et autant d'occasions de se tromper de promotion.

## Mécanique technique

<ol class="steps" markdown="1">
<li markdown="1"><b>Créer la cohorte</b>
`Classes / Cohortes → Créer une cohorte`. Le contexte est le système (`?contextid=1`) pour que la
cohorte soit utilisable dans toutes les catégories.</li>
<li markdown="1"><b>Lier la cohorte au cours</b>
Dans chaque cours : `Participants → Méthodes d'inscription → Ajouter une méthode → Synchronisation
des cohortes`. Choisir la cohorte et le rôle *Étudiant*.</li>
<li markdown="1"><b>Laisser le cron travailler</b>
La synchronisation est portée par `enrol_cohort_sync()`, déclenchée par le cron, d'où l'importance
du conteneur `moodle-cron`.</li>
</ol>

<div class="note" markdown="1">
<b>Si les inscriptions n'apparaissent pas</b>
Vérifier que le conteneur cron tourne : `docker compose logs --tail 20 moodle-cron`. Une exécution
doit apparaître toutes les 60 secondes. Sans cron, la cohorte est bien enregistrée mais aucun
étudiant n'est réellement inscrit au cours.
</div>

## Convention de nommage

`NIVEAU-FILIÈRE-ANNÉE`

| Identifiant | Signification |
|---|---|
| `L1-DATA-2026` | Licence 1 Data & IA, promotion 2026 |
| `L1-GIM-2026` | Licence 1 Génie Industriel & Maintenance, promotion 2026 |
| `CERT-CBP-2026` | Certificat Cybersécurité des Biens et Personnes, promotion 2026 |

Cette convention rend les cohortes triables et filtrables dans les listes, et permet de repérer d'un
coup d'œil la promotion concernée dans les journaux d'activité.

## État actuel

| Élément | État |
|---|---|
| Cohorte pilote `L1-DATA-2026` | <span class="badge ok">18 étudiants</span> |
| Cours rattachés | <span class="badge ok">4 cours synchronisés</span> |
| Contenus de démonstration | <span class="badge ok">Page, forum, devoir</span> |
| Cycle remise → correction → note | <span class="badge ok">Vérifié de bout en bout</span> |
| Visioconférence BigBlueButton | <span class="badge wip">Serveur de démonstration</span> |
| Catalogue complet : 5 domaines, 20 formations | <span class="badge wip">Script prêt, à exécuter</span> |

<div class="tip" markdown="1">
<b>Exclusion automatique du personnel</b>
Le script `add_students_to_cohort.php` exclut explicitement les administrateurs et les
technopédagogues, et les retire de la cohorte s'ils y figuraient déjà. Sans ce garde-fou, un compte
de pilotage se retrouvait inscrit comme étudiant et voyait son espace basculer.
</div>

## Le catalogue de formations

`scripts/setup_offre_formation.php` crée l'offre complète : **5 domaines** (Industrie, Numérique,
Santé, Agriculture, Services), **20 formations**, les niveaux L1/L2/L3 ou M1/M2 selon le cycle, et
une cohorte par classe.

```bash
cat scripts/setup_offre_formation.php | docker compose exec -T -u www-data moodle php
```

Le script est idempotent : il vérifie l'existence de chaque catégorie et de chaque cohorte avant de
la créer. On peut donc le relancer après avoir ajouté une formation au catalogue.

## Visioconférence

<div class="note" markdown="1">
<b>BigBlueButton est intégré à Moodle</b>
Le module `mod_bigbluebuttonbn` est livré avec Moodle depuis la version 4.0 : rien à installer. Il
pointe actuellement sur le serveur de démonstration public, suffisant pour la présentation, mais
**à remplacer par un serveur dédié** en production. Le serveur de test n'offre aucune garantie de
disponibilité ni de confidentialité, et limite la durée des sessions.
</div>

Les enregistrements apparaissent sous l'activité une fois la session terminée *et* le cron passé. Un
délai de quelques minutes est normal.
