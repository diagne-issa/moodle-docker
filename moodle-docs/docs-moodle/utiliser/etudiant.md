---
icon: material/account
---

<div class="page-head" markdown="1">
<p class="crumb">Utiliser · 11</p>

# Guide de l'étudiant

L'espace étudiant est délibérément épuré : cinq entrées de menu, aucune option d'administration
visible, aucune page technique atteignable.
</div>

## Se connecter

L'étudiant se connecte sur la même page que tout le monde :
`http://localhost:8090/login/index.php`, avec l'identifiant fourni par l'établissement.

<div class="note" markdown="1">
<b>Une seule page de connexion</b>
C'est le **rôle** de l'utilisateur, pas l'adresse de connexion, qui détermine ce qu'il voit ensuite.
Il n'y a donc pas d'URL séparée « espace étudiant » à retenir.
</div>

## Son espace

| Entrée | Contenu |
|---|---|
| Tableau de bord | Cours en cours, progression, prochaines échéances |
| Mes cours | Tous les cours de sa classe, via la cohorte |
| Calendrier | Devoirs, tests et séances de visioconférence |
| Mes notes | Notes et feedbacks par cours |
| Messages | Échanges avec les enseignants et les camarades |

<div class="tip" markdown="1">
<b>Ses cours arrivent tout seuls</b>
L'étudiant n'a rien à faire pour s'inscrire : son appartenance à la cohorte de sa classe l'inscrit
automatiquement à tous les cours de sa formation. Il ne voit jamais les cours d'une autre promotion.
</div>

## Remettre un devoir

<ol class="steps" markdown="1">
<li markdown="1"><b>Ouvrir le cours puis le devoir</b>
Depuis *Mes cours*, ou directement depuis l'échéance affichée dans le calendrier ou sur le tableau de
bord.</li>
<li markdown="1"><b>Ajouter un travail</b>
Glisser-déposer le fichier dans la zone de dépôt, ou passer par le sélecteur de fichiers.</li>
<li markdown="1"><b>Enregistrer</b>
Le statut passe à **Remis pour évaluation**, affiché en vert.</li>
<li markdown="1"><b>Consulter la note</b>
Quand l'enseignant a corrigé, la note et le feedback apparaissent sur la page du devoir et dans
*Mes notes*.</li>
</ol>

<div class="note" markdown="1">
<b>Avant la date limite</b>
Tant que l'échéance n'est pas passée, le travail peut être remplacé : rouvrir le devoir, *Modifier le
travail remis*, supprimer l'ancien fichier et déposer le nouveau.
</div>

## Participer à une visioconférence

1. Ouvrir le cours, repérer l'activité **BigBlueButton** à la date prévue
2. Cliquer sur **Rejoindre la session**
3. Autoriser le micro et la caméra si demandé par le navigateur

Si la séance a été enregistrée, l'enregistrement apparaît sous l'activité quelques minutes après la
fin.

## Suivre sa progression

- **Tableau de bord** : vue d'ensemble : cours en cours et prochaines échéances
- **Mes notes** : toutes les notes, par cours, avec les feedbacks des enseignants
- Dans un cours, les cases d'achèvement indiquent les activités déjà terminées

## Ce que l'étudiant ne voit pas

Aucune entrée d'administration, aucun accès aux cours des autres promotions, aucune page de gestion.
Une tentative d'accès direct par l'URL renvoie *Accès refusé* : la restriction est portée par les
permissions Moodle, pas par un simple masquage de menu.
