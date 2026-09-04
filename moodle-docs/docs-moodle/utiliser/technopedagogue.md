---
icon: material/account-group
---

<div class="page-head" markdown="1">
<p class="crumb">Utiliser · 09</p>

# Guide du technopédagogue

Le technopédagogue pilote la vie pédagogique de la plateforme. Il ne touche jamais aux réglages
techniques. C'est volontaire, et c'est verrouillé au niveau des permissions.
</div>

## Son espace

À la connexion, le technopédagogue arrive sur un tableau de bord de pilotage : bandeau de bienvenue,
quatre indicateurs clés, histogramme des inscriptions sur six mois, et une carte d'actions rapides
placée à côté du graphique.

| Groupe de menu | Contient |
|---|---|
| Pilotage | Tableau de bord |
| Pédagogie | Cours (créer, vue publique), Catégories (gérer, créer) |
| Utilisateurs | Utilisateurs (liste, ajouter), Classes / Cohortes (liste, créer, importer CSV) |
| Suivi | Rapports (journaux d'activité, utilisateurs en ligne) |
| Organisation | Calendrier, Messages |

## Créer une classe et y inscrire les étudiants

C'est l'opération de rentrée. Elle se fait une fois par promotion.

<ol class="steps" markdown="1">
<li markdown="1"><b>Créer la cohorte</b>
`Classes / Cohortes → Créer une cohorte`. Nom lisible (« L1 Data 2026 »), identifiant selon la
convention (`L1-DATA-2026`), contexte : Système.</li>
<li markdown="1"><b>Importer les étudiants</b>
`Classes / Cohortes → Importer (CSV)`. Colonnes minimales : `username, password, firstname,
lastname, email`. Ajouter une colonne `cohort1` contenant l'identifiant de la cohorte pour les y
inscrire directement.</li>
<li markdown="1"><b>Créer les cours</b>
`Cours → Créer un cours`, en le rangeant dans la bonne catégorie (domaine → formation → année).</li>
<li markdown="1"><b>Lier la cohorte au cours</b>
Dans le cours : `Participants → Méthodes d'inscription → Ajouter une méthode → Synchronisation des
cohortes`. Choisir la cohorte, rôle *Étudiant*.</li>
<li markdown="1"><b>Nommer l'enseignant</b>
`Participants → Inscrire des utilisateurs`, rôle *Enseignant*.</li>
<li markdown="1"><b>Vérifier</b>
Ouvrir `Participants` : la liste doit afficher les étudiants avec la mention « Synchronisation des
cohortes » dans la colonne des méthodes d'inscription.</li>
</ol>

<div class="tip" markdown="1">
<b>Préparer le fichier CSV</b>
Enregistrer en **CSV UTF-8** (pas en `.xlsx`), première ligne = en-têtes exacts en minuscules, pas de
ligne vide en fin de fichier, pas de cellule fusionnée. Une erreur de chargement vient presque
toujours de l'encodage ou d'un en-tête mal orthographié.
</div>

### Exemple de fichier

```csv
username,password,firstname,lastname,email,cohort1
awa.ndiaye,Urdfs2026!,Awa,Ndiaye,awa.ndiaye@urdfs.edu.sn,L1-DATA-2026
moussa.fall,Urdfs2026!,Moussa,Fall,moussa.fall@urdfs.edu.sn,L1-DATA-2026
```

<div class="note" markdown="1">
<b>Le délai de synchronisation</b>
L'inscription par cohorte est portée par une tâche planifiée qui s'exécute toutes les minutes. Si les
étudiants n'apparaissent pas immédiatement dans le cours, patienter une minute et recharger.
</div>

## Ajouter un étudiant en cours d'année

1. `Utilisateurs → Ajouter un utilisateur`, ou import CSV d'une seule ligne.
2. `Classes / Cohortes` → ouvrir la cohorte → **Affecter** → ajouter l'étudiant.

Il est automatiquement inscrit à tous les cours de sa classe. Aucune autre action n'est nécessaire.

## Suivre l'activité

| Outil | Quand l'utiliser |
|---|---|
| **Rapports → Journaux d'activité** | Recherche précise : filtrable par cours, utilisateur, date et type d'événement. C'est la vue à privilégier. |
| **Rapports → Utilisateurs en ligne** | Surveillance temps réel de la dernière heure, actualisée toutes les 60 secondes. Non filtrable. |
| Dans un cours : **Rapports → Achèvement d'activité** | Progression détaillée par étudiant et par activité. |
| Dans un cours : **Rapports → Participation** | Qui a consulté quoi, et qui n'a rien consulté. |

<div class="note" markdown="1">
<b>Le journal en direct affiche 100 lignes</b>
Cette valeur est fixée dans le cœur de Moodle et n'est pas paramétrable. Le cadre du tableau est
borné en hauteur avec défilement interne, pour que la page reste lisible sans perdre de données.
</div>

## Superviser un cours sans y être inscrit

Le technopédagogue possède `moodle/course:view` au niveau système : il peut ouvrir n'importe quel
cours, voir les sections et activités masquées, accéder à tous les groupes et consulter les notes,
**sans avoir besoin de s'y inscrire**.

C'est important : s'inscrire dans un cours pour le consulter fausse les statistiques de participation
et fait apparaître un compte de pilotage dans la liste des participants.

## Ce que le technopédagogue ne peut pas faire

Et c'est normal :

- Réglages du site, plugins, apparence, serveur, sécurité
- Mode maintenance
- Gestion et surcharge des rôles
- Jetons de services web
- Attribuer un rôle Manager, Technopédagogue ou Administrateur

Ces zones relèvent du super administrateur. Une tentative d'accès direct par l'URL renvoie *Accès
refusé*. La restriction est réelle, pas cosmétique.

<div class="tip" markdown="1">
<b>Si une page légitime est refusée</b>
C'est qu'une capability manque dans la liste Allow. Le correctif est décrit dans
[Rôles & permissions → Ajouter une capability](../technique/roles.md#ajouter-une-capability). Cela
s'est produit trois fois sur ce projet : import CSV, utilisateurs en ligne, liste complète des
utilisateurs.
</div>
