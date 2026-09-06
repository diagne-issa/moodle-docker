---
icon: material/format-list-checks
---

<div class="page-head" markdown="1">
<p class="crumb">Référence · 14</p>

# Reste à faire

Ce qui est acquis, ce qui doit être fait avant la démonstration, et les chantiers qui attendent la
version suivante.
</div>

## Avant la démonstration

<ul class="check" markdown="1">
<li class="todo" markdown="1">Exécuter `setup_offre_formation.php` : catalogue complet des 20 formations</li>
<li class="todo" markdown="1">Relancer `setup_roles.php` pour les dernières capabilities, puis purger les caches</li>
<li class="todo" markdown="1">Parcourir la plateforme avec un compte de chaque rôle, dans une fenêtre privée</li>
<li class="todo" markdown="1">Vérifier qu'un étudiant ne peut atteindre aucune page d'administration par URL directe</li>
<li class="todo" markdown="1">Préparer le parcours de démonstration de bout en bout</li>
</ul>

<div class="card" markdown="1">
#### Parcours de démonstration suggéré

<ol class="steps" markdown="1">
<li markdown="1"><b>La page de connexion</b>
Montrer d'emblée qu'on ne reconnaît pas Moodle : carte URDFS, couleurs de la charte, typographie
propre.</li>
<li markdown="1"><b>L'espace technopédagogue</b>
Tableau de bord de pilotage, indicateurs, histogramme des inscriptions. Ouvrir la liste des cohortes
pour montrer la structure par classe.</li>
<li markdown="1"><b>L'espace enseignant</b>
Fenêtre privée. Tableau de bord avec « Travaux à corriger », puis ouvrir un devoir et corriger une
copie.</li>
<li markdown="1"><b>L'espace étudiant</b>
Autre fenêtre privée. Montrer que la note vient d'apparaître dans « Mes notes ».</li>
<li markdown="1"><b>La sécurité</b>
Depuis le compte étudiant, taper une URL d'administration : « Accès refusé ». C'est l'argument le plus
convaincant du projet.</li>
</ol>
</div>

<div class="tip" markdown="1">
<b>Préparer trois fenêtres à l'avance</b>
Une fenêtre normale connectée en technopédagogue, deux fenêtres privées pour l'enseignant et
l'étudiant. Basculer entre elles est bien plus fluide que de se déconnecter à chaque fois devant le
public.
</div>

## Déjà en place

<ul class="check" markdown="1">
<li markdown="1">Thème enfant `theme_urdfs` actif, charte appliquée sur toutes les pages</li>
<li markdown="1">Page de connexion sur mesure</li>
<li markdown="1">Quatre navigations et quatre tableaux de bord distincts</li>
<li markdown="1">Rôle Technopédagogue créé et verrouillé contre l'escalade de privilèges</li>
<li markdown="1">Rôle Enseignant durci</li>
<li markdown="1">Cohorte pilote de 18 étudiants, synchronisée sur 4 cours</li>
<li markdown="1">Cycle remise → correction → note vérifié de bout en bout</li>
<li markdown="1">Visioconférence BigBlueButton fonctionnelle, enregistrement compris</li>
<li markdown="1">Version de Moodle figée, configuration persistante</li>
<li markdown="1">Correctifs d'affichage transverses sur les tableaux, formulaires et gestionnaire de fichiers</li>
<li markdown="1">Cette documentation</li>
</ul>

## Après la version 1

<div class="grid g2" markdown="1">
<div class="card" markdown="1">
#### Montée vers Moodle 5.3 LTS
Version cible pour la mise en production, attendue en octobre 2026. Environ deux ans de support, et
c'est la version que visent les auteurs de plugins. La montée consiste à changer `MOODLE_BRANCH`,
reconstruire l'image et lancer `upgrade.php`, après un test sur une copie.

<span class="badge no">Avant la mise en production</span>
</div>
<div class="card" markdown="1">
#### Serveur BigBlueButton dédié
Le serveur public de démonstration ne convient pas à un usage réel : aucune garantie de
disponibilité, aucune confidentialité, durée de session limitée.

<span class="badge no">Bloquant pour la production</span>
</div>
<div class="card" markdown="1">
#### Serveur SMTP
Pour réactiver les notifications par courriel : remises, corrections, messages, réinitialisation de
mot de passe. Retirer alors `noemailever`.

<span class="badge no">Bloquant pour la production</span>
</div>
<div class="card" markdown="1">
#### HTTPS et nom de domaine
Remplacer `localhost:8090` par une vraie adresse avec certificat. À faire en même temps que la mise
en ligne.

<span class="badge no">Bloquant pour la production</span>
</div>
<div class="card" markdown="1">
#### Sauvegardes automatiques
Planifier des exports réguliers de `postgres_data` et `moodledata`, stockés hors de la machine hôte.

<span class="badge no">Bloquant pour la production</span>
</div>
<div class="card" markdown="1">
#### Attestations de suivi
Le plugin `mod_customcert` génère des attestations personnalisées. À installer par montage durable
pour survivre aux reconstructions d'image.

<span class="badge wip">Confort</span>
</div>
<div class="card" markdown="1">
#### Nettoyage
Retirer `theme/moove` et ses anciennes retouches, devenues inutiles depuis la migration vers
`theme_urdfs`.

<span class="badge wip">Dette technique</span>
</div>
</div>

## Ordre de priorité suggéré

<ol class="steps" markdown="1">
<li markdown="1"><b>Sauvegardes</b>
Avant tout le reste. Une plateforme sans sauvegarde n'est pas en production, quelle que soit la
qualité du reste.</li>
<li markdown="1"><b>HTTPS et nom de domaine</b>
Condition d'accès depuis l'extérieur, et prérequis de plusieurs autres chantiers.</li>
<li markdown="1"><b>Serveur SMTP</b>
Sans courriel, aucun étudiant ne peut réinitialiser son mot de passe seul, et la charge retombe
entièrement sur le technopédagogue.</li>
<li markdown="1"><b>Serveur BigBlueButton</b>
Dès que les cours en visioconférence deviennent réguliers.</li>
<li markdown="1"><b>Attestations et nettoyage</b>
Quand le rythme le permet.</li>
</ol>
