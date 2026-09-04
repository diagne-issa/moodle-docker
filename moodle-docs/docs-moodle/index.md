---
icon: material/home
---

<div class="page-head hero" markdown="1">
<span class="kicker">Documentation projet</span>

# Plateforme d'apprentissage de l'Université Rose Dieng France-Sénégal

Moodle 5.1.6 conteneurisé, entièrement re-habillé aux couleurs de l'URDFS, avec trois espaces
de travail distincts et un modèle de permissions à moindre privilège.

<div class="herofacts" markdown="1">
<div markdown="1">**5.1.6**<span>Version figée</span></div>
<div markdown="1">**4**<span>Rôles distincts</span></div>
<div markdown="1">**7**<span>Scripts idempotents</span></div>
</div>
</div>

## De quoi s'agit-il

Cette plateforme est une instance **Moodle 5.1.6** exécutée dans Docker et personnalisée pour
l'Université Rose Dieng France-Sénégal. L'objectif du projet est double : offrir un LMS complet et
fonctionnel, et le rendre visuellement méconnaissable d'un Moodle standard, avec une identité URDFS
de bout en bout, du formulaire de connexion aux tableaux de notes.

<div class="grid g3" markdown="1">
<div class="tile" markdown="1">
#### Identité visuelle
Charte marine + vert appliquée par un thème enfant, jamais par les réglages d'administration.
</div>
<div class="tile green" markdown="1">
#### Trois espaces
Étudiant, enseignant et technopédagogue voient chacun une navigation et un tableau de bord dédiés.
</div>
<div class="tile" markdown="1">
#### Sécurité réelle
Les restrictions passent par les *capabilities* Moodle, pas par du masquage CSS.
</div>
</div>

## Les trois principes du projet

1. **Tout vit dans le code.** Aucune personnalisation n'est faite via *Administration du site →
   Apparence*. Ces réglages sont stockés en base de données et disparaissent à la moindre
   réinstallation. Tout est dans `theme/urdfs/`, versionnable avec git.
2. **Thème enfant, pas de patch du cœur.** `theme_urdfs` hérite de `boost` et se contente de
   surcharger ce qui doit l'être. Les fichiers de Moodle ne sont jamais modifiés, ce qui rend les
   montées de version possibles.
3. **Sécurité par les permissions.** Masquer un bouton ne protège rien : un utilisateur peut
   toujours taper l'URL. Chaque restriction est donc doublée d'un *Prevent* sur la capability
   correspondante.

## Les quatre profils

| Profil | Rôle Moodle | Périmètre |
|---|---|---|
| **Étudiant** | `student` | Ses cours, ses notes, son calendrier, ses messages. Rien d'autre. |
| **Enseignant** | `editingteacher` | Ses propres cours : contenus, activités, corrections, notes de ses étudiants. |
| **Technopédagogue** | `technopedagogue` | Pilotage pédagogique de toute la plateforme : cours, catégories, comptes, classes, rapports. <span class="badge no">Aucun accès technique</span> |
| **Super administrateur** | `siteadmin` | Tout, y compris les réglages serveur, les plugins et la sécurité. |

## Par où commencer

<div class="grid g3">
<a class="tile" href="demarrer/demarrage/"><h4>Lancer la plateforme</h4><p>Les commandes du quotidien et le réflexe qui évite de perdre une heure.</p><span class="go">Commencer ici →</span></a>
<a class="tile green" href="utiliser/technopedagogue/"><h4>Créer une classe</h4><p>Cohorte, import CSV, cours, inscription automatique des étudiants.</p><span class="go">Mode d'emploi →</span></a>
<a class="tile" href="reference/depannage/"><h4>Quelque chose ne marche pas</h4><p>Les pannes déjà rencontrées, leur cause réelle et leur correctif.</p><span class="go">Dépannage →</span></a>
</div>

Le reste de la documentation est accessible dans le menu de gauche.

<div class="note" markdown="1">
<b>Comment travailler sur le projet</b>
Tout le projet vit dans un dépôt Git et s'exécute dans Docker : il fonctionne à l'identique sous
**Windows, macOS et Linux**. Chacun clone le dépôt où il le souhaite ; toutes les commandes de
cette documentation se lancent depuis le dossier `moodle-docker`. Une seule règle : travailler
dans le dossier **cloné et versionné**, jamais dans une copie manuelle, sans quoi les
modifications ne sont ni prises en compte par Docker ni partagées avec l'équipe.
</div>
