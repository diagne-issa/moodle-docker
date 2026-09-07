---
icon: material/book-open-variant
---

<div class="page-head" markdown="1">
<p class="crumb">Utiliser · 10</p>

# Guide de l'enseignant

L'enseignant travaille dans ses cours. Sa navigation est construite dynamiquement à partir des cours
où il enseigne réellement.
</div>

## Son tableau de bord

- Bandeau de bienvenue à liseré vert, et quatre statistiques de synthèse
- **Travaux à corriger** : nombre de remises en attente, détaillé par devoir
- Prochaines échéances de ses cours

<div class="note" markdown="1">
<b>« Travaux à corriger » est calculé en direct</b>
Le chiffre vient de `count_submissions_need_grading()`, la même méthode que Moodle utilise en
interne. Il est donc toujours exact, y compris après une remise tardive.
</div>

### Sa navigation

- Tableau de bord
- **Mes cours** : sous-menu dynamique, une entrée par cours enseigné
- Mes étudiants, Devoirs à corriger
- Calendrier, Messages

Moodle n'offre pas de page globale « tous mes devoirs » ou « tous mes étudiants » : ces vues sont
reconstituées par cours dans le menu.

## Ajouter du contenu à un cours

<ol class="steps" markdown="1">
<li markdown="1"><b>Activer le mode édition</b>
Interrupteur en haut à droite de la page du cours.</li>
<li markdown="1"><b>Ajouter une activité ou une ressource</b>
Dans la section voulue. Les plus utilisés : *Fichier*, *Page*, *Devoir*, *Test*, *Forum*,
*BigBlueButton*.</li>
<li markdown="1"><b>Régler les paramètres</b>
Pour un devoir : date de remise, type de remise (fichier ou texte en ligne), notation sur 20, et
éventuellement les conditions d'achèvement.</li>
<li markdown="1"><b>Enregistrer et revenir au cours</b>
L'activité apparaît dans la section, et l'échéance remonte automatiquement dans le calendrier des
étudiants.</li>
</ol>

<div class="tip" markdown="1">
<b>Déposer un fichier</b>
Le glisser-déposer fonctionne directement sur la page du cours en mode édition : le fichier devient
une ressource sans passer par le formulaire. Le gestionnaire de fichiers a été retravaillé pour que
la zone de dépôt ne chevauche plus le contenu qui la suit.
</div>

## Corriger un devoir

<ol class="steps" markdown="1">
<li markdown="1"><b>Ouvrir le devoir</b>
Puis **Voir tous les travaux remis**.</li>
<li markdown="1"><b>Repérer les remises</b>
La colonne *Statut* affiche « Remis pour évaluation » en vert pour les travaux en attente.</li>
<li markdown="1"><b>Noter</b>
Bouton **Note** sur la ligne de l'étudiant. Saisir la note et le feedback.</li>
<li markdown="1"><b>Enchaîner</b>
**Enregistrer et afficher le suivant** pour passer à la copie suivante sans revenir à la liste.</li>
</ol>

L'interface de correction a été retravaillée : panneaux latéraux décalés pour ne pas passer sous la
barre de navigation, en-tête façon fil d'Ariane, formulaire forcé sur deux colonnes, et tableau de
remises défilant dans son cadre avec en-tête figée.

<div class="note" markdown="1">
<b>Notification à l'étudiant</b>
Dans cet environnement de démonstration, les courriels sont désactivés (`noemailever`). L'étudiant
voit sa note en se connectant, mais ne reçoit pas de message. En production, avec un serveur SMTP
configuré, la notification part automatiquement.
</div>

## Lancer une visioconférence

<ol class="steps" markdown="1">
<li markdown="1"><b>Ajouter l'activité</b>
Mode édition → *Ajouter une activité* → **BigBlueButton**.</li>
<li markdown="1"><b>Régler la séance</b>
Nom, description, date et heure. Activer l'enregistrement si la séance doit être consultable après
coup.</li>
<li markdown="1"><b>Rejoindre</b>
Le jour venu, ouvrir l'activité et cliquer sur **Rejoindre la session**. L'enseignant entre en
modérateur, les étudiants en participants.</li>
<li markdown="1"><b>Retrouver l'enregistrement</b>
Il apparaît sous l'activité quelques minutes après la fin de la séance, une fois le cron passé.</li>
</ol>

## Suivre ses étudiants

- **Participants** : la liste de sa classe, avec les rôles et le dernier accès
- **Notes** : carnet de notes du cours, exportable
- **Rapports → Achèvement d'activité** : qui a terminé quoi
- **Rapports → Participation** : qui n'a rien consulté depuis N jours

## Prendre les présences

Le plugin **Présence** s'ajoute comme n'importe quelle activité, par *Ajouter une activité ou
ressource → Présence*. Une seule activité suffit pour tout le cours.

<ol class="steps" markdown="1">
<li markdown="1"><b>Créer les séances</b>
Dans l'activité, onglet <i>Sessions</i>, puis <i>Ajouter une session</i>. Une séance récurrente
peut être générée pour tout le semestre en une fois.</li>
<li markdown="1"><b>Saisir les présences</b>
Cliquer sur la date de la séance. Chaque étudiant se coche en P, R, E ou A, pour présent, retard,
excusé ou absent, avec une remarque facultative.</li>
<li markdown="1"><b>Exporter</b>
Onglet <i>Rapport</i>, puis export en tableur pour la scolarité.</li>
</ol>

## Délivrer une attestation

L'activité **Certificat personnalisé** génère un PDF nominatif. Elle s'ajoute comme les autres,
puis se compose par le bouton **Modifier le certificat** : nom de l'étudiant, intitulé du cours,
date, logo de l'université.

<div class="warn" markdown="1">
<b>Toujours poser une condition de délivrance</b>
Par défaut, l'attestation est téléchargeable dès le premier jour, par n'importe quel étudiant
inscrit, même s'il n'a rien fait. Cela vide le document de sa valeur.

Dans les réglages de l'activité, section <i>Restreindre l'accès</i>, exigez une condition réelle :
l'<b>achèvement du cours</b>, une <b>note minimale</b>, ou une <b>date</b> de fin de session. Le
bouton de téléchargement n'apparaîtra alors qu'aux étudiants qui remplissent la condition.
</div>

## Les limites du rôle

<div class="note" markdown="1">
<b>Ce que l'enseignant ne peut pas faire</b>
Créer un cours, supprimer un cours, gérer les catégories. Ces opérations passent par le
technopédagogue, qui a la vision d'ensemble du catalogue et évite les doublons ou les cours rangés
dans la mauvaise formation.
</div>
