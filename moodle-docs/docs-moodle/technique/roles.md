---
icon: material/shield-check
---

<div class="page-head" markdown="1">
<p class="crumb">Technique · 06</p>

# Rôles & permissions

Masquer un bouton ne protège rien. Chaque restriction est un *Prevent* sur une capability Moodle, et
la matrice d'attribution empêche toute escalade de privilèges.
</div>

## Un seul script fait foi

La configuration complète tient dans `scripts/setup_roles.php`, idempotent : on peut le relancer
autant de fois que nécessaire sans effet de bord.

```bash
# Depuis le dossier moodle-docker du dépôt cloné
cat scripts/setup_roles.php | docker compose exec -T -u www-data moodle php
```

<div class="tip" markdown="1">
<b>Pourquoi un script plutôt que l'interface</b>
Configurer les rôles à la main dans l'administration fonctionne, mais rien n'en garde trace :
impossible de savoir *pourquoi* une capability a été accordée, ni de reproduire la configuration sur
une autre instance. Le script est la source de vérité, commenté et versionné.
</div>

## Le rôle Technopédagogue

Créé à partir de l'archétype `manager`, qui fournit un jeu de capabilities pédagogiques cohérent,
puis **restreint**. Attribuable aux niveaux système, catégorie et cours.

```php
$roleid = create_role(
    'Technopédagogue',
    'technopedagogue',
    'Gestion pédagogique de la plateforme…',
    'manager'
);
set_role_contextlevels($roleid, [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE]);
```

<div class="grid g2" markdown="1">
<div class="card" style="border-top:3px solid #ef4444" markdown="1">
#### Interdit <span class="badge no">Prevent</span>

- `moodle/site:config`
- `moodle/site:configview`
- `moodle/role:manage`
- `moodle/role:override`
- `moodle/role:safeoverride`
- `moodle/site:maintenanceaccess`
- `moodle/webservice:managealltokens`
</div>
<div class="card" style="border-top:3px solid #06843c" markdown="1">
#### Autorisé <span class="badge ok">Allow</span>

- **Cours** : créer, modifier, supprimer, visibilité, activités, cours masqués
- **Catégories** : gérer, voir les masquées
- **Comptes** : créer, modifier, import CSV, voir les détails complets
- **Inscriptions** : attribution de rôles, inscription manuelle et désinscription
- **Notes** : voir tout, modifier, voir les notes masquées
- **Sauvegarde / restauration** : réutiliser un cours d'une année sur l'autre
- **Supervision** : entrer dans tout cours sans y être inscrit, voir sections et activités masquées, accéder à tous les groupes
- **Rapports** : journaux, journal en direct, participation, progression, plan du cours, analyses
- **Cohortes** : voir, gérer, affecter
</div>
</div>

## Protection contre l'escalade de privilèges

C'est le point le plus important du dispositif. Sans lui, tout le reste serait contournable en une
minute : il suffirait au technopédagogue de s'attribuer le rôle Manager pour récupérer
`site:config`.

```php
// On repart d'une matrice propre pour ce rôle.
$targets = ['editingteacher', 'teacher', 'student'];
$DB->delete_records('role_allow_assign', ['roleid' => $roleid]);

foreach ($targets as $tsn) {
    if ($t = $DB->get_record('role', ['shortname' => $tsn])) {
        core_role_set_assign_allowed($roleid, $t->id);
    }
}
```

Un technopédagogue peut donc nommer un enseignant ou inscrire un étudiant, mais il ne peut **pas**
attribuer, ni à lui-même ni à personne, le rôle Manager ou Administrateur.

| Le technopédagogue peut attribuer… | Statut |
|---|---|
| Enseignant (`editingteacher`) | <span class="badge ok">Autorisé</span> |
| Enseignant non éditeur (`teacher`) | <span class="badge ok">Autorisé</span> |
| Étudiant (`student`) | <span class="badge ok">Autorisé</span> |
| Manager | <span class="badge no">Refusé</span> |
| Technopédagogue | <span class="badge no">Refusé</span> |
| Administrateur | <span class="badge no">Refusé</span> |

## Le rôle Responsable pédagogique

Il assure le suivi opérationnel **d'une ou plusieurs formations précises**, là où le technopédagogue
pilote l'ensemble de la plateforme.

```bash
cat scripts/setup_responsable_pedagogique.php | docker compose exec -T -u www-data moodle php
```

### La distinction ne tient pas aux droits, mais au contexte

C'est le point à comprendre, et il évite bien des malentendus.

| | Technopédagogue | Responsable pédagogique |
|---|---|---|
| Portée | Toute la plateforme | Les formations qui lui sont confiées |
| Attribué au niveau | Système | Catégorie |
| Créer et supprimer des cours | <span class="badge ok">Oui</span> | <span class="badge no">Non</span> |
| Créer des comptes | <span class="badge ok">Oui</span> | <span class="badge no">Non</span> |
| Consulter les notes | <span class="badge ok">Oui</span> | <span class="badge ok">Oui</span> |
| **Modifier** les notes | <span class="badge ok">Oui</span> | <span class="badge no">Non</span> |
| Rapports et exports | <span class="badge ok">Oui</span> | <span class="badge ok">Oui</span> |
| Contacter enseignants et étudiants | <span class="badge ok">Oui</span> | <span class="badge ok">Oui</span> |
| Réglages techniques | <span class="badge no">Non</span> | <span class="badge no">Non</span> |

Le cloisonnement est **structurel** : le rôle est déclaré attribuable uniquement sur une catégorie
ou un cours, jamais au niveau système. Personne ne peut donc lui donner une portée globale, même par
erreur. Filtrer l'affichage n'aurait rien protégé, puisqu'il aurait suffi de saisir l'URL d'un autre
cours.

<div class="warn" markdown="1">
<b>Il ne modifie jamais les notes</b>
<code>moodle/grade:edit</code>, <code>mod/assign:grade</code> et <code>mod/quiz:grade</code> sont en
<i>Prevent</i>. Il consulte, exporte et alerte ; la correction reste la responsabilité de
l'enseignant. C'est une garantie pédagogique autant qu'une règle de sécurité.
</div>

### Un responsable, plusieurs formations

C'est le cas courant, et Moodle le gère par la **hiérarchie des catégories**. Le périmètre se règle
en choisissant le niveau où l'on attribue le rôle.

| Besoin | Où attribuer |
|---|---|
| Il suit **tout un domaine** | Sur la catégorie du domaine, par exemple *Numérique & IA*. Il hérite de toutes les formations qu'elle contient. |
| Il suit **deux formations** de domaines différents | Deux attributions, une sur chaque catégorie de formation. |
| Il suit **une seule promotion** | Sur la catégorie du niveau, par exemple *L1* sous *Licence Data & IA*. |

Les attributions s'additionnent : un même utilisateur peut porter le rôle sur autant de catégories
que nécessaire, et il verra l'union de ces périmètres.

### Attribuer le rôle

La manipulation diffère de celle du technopédagogue, et c'est volontaire.

<ol class="steps" markdown="1">
<li markdown="1"><b>Ouvrir la catégorie concernée</b>
<i>Cours → Catégories</i>, puis cliquer sur la formation ou le domaine.</li>
<li markdown="1"><b>Aller dans l'attribution des rôles</b>
Menu de la catégorie, <i>Permissions → Attribuer des rôles</i>.</li>
<li markdown="1"><b>Choisir Responsable pédagogique</b>
Sélectionner l'utilisateur dans la colonne de droite, puis <b>Ajouter</b>.</li>
<li markdown="1"><b>Répéter si nécessaire</b>
Une attribution par catégorie dont il est responsable.</li>
<li markdown="1"><b>Faire reconnecter l'utilisateur</b>
Sans déconnexion puis reconnexion, sa session conserve les anciennes permissions.</li>
</ol>

<div class="note" markdown="1">
<b>Jamais depuis les rôles système</b>
Ne pas passer par <i>Administration du site → Utilisateurs → Permissions → Attribuer des rôles
système</i> : cette page est réservée au technopédagogue. Le rôle Responsable pédagogique n'y
apparaît d'ailleurs pas, par construction.
</div>

Le technopédagogue peut nommer lui-même les responsables, sans solliciter l'administrateur.

### Son espace dans le thème

Le thème choisit l'espace à afficher d'après les **capabilities** de l'utilisateur. Or le responsable
n'en possède aucune au niveau système, puisque c'est justement ce qui le cloisonne : il tombait donc
dans l'espace étudiant.

La détection se fait maintenant sur son **attribution de rôle**, dans `theme_urdfs_is_responsable()`,
quel que soit le contexte. Il obtient sa propre navigation (ses formations, calendrier, messagerie)
et un tableau de bord qui affiche son périmètre : formations suivies, cours concernés, étudiants.

## Durcissement du rôle Enseignant

Trois capabilities passées explicitement en *Prevent* sur `editingteacher` :

```php
'moodle/course:create'     // un enseignant ne crée pas de cours
'moodle/category:manage'   // ni ne gère les catégories
'moodle/course:delete'     // ni n'en supprime
```

Un enseignant travaille *dans* ses cours. La création et la suppression relèvent du technopédagogue,
qui a la vision d'ensemble du catalogue.

<div class="tip" markdown="1">
<b>Le Super Administrateur n'est jamais touché</b>
Aucun script ne réduit les droits d'un compte `siteadmin`. Le durcissement porte uniquement sur les
rôles Technopédagogue et Enseignant. Un administrateur conserve l'intégralité de ses accès en toutes
circonstances.
</div>

## Attribuer le rôle

<ol class="steps" markdown="1">
<li markdown="1"><b>Ouvrir la page d'attribution</b>
Administration du site → Utilisateurs → Permissions → *Attribuer des rôles système*.</li>
<li markdown="1"><b>Choisir Technopédagogue</b>
Puis sélectionner l'utilisateur dans la colonne de droite et cliquer sur **Ajouter**.</li>
<li markdown="1"><b>Faire reconnecter l'utilisateur</b>
Les permissions sont mises en cache dans la session. Sans déconnexion / reconnexion, l'utilisateur
continue de voir son ancien espace.</li>
</ol>

<div class="warn" markdown="1">
<b>Après chaque modification de rôle</b>
Le script se termine par `purge_all_caches()`, mais cela ne suffit pas : l'utilisateur concerné doit
**se déconnecter puis se reconnecter** pour que sa session prenne les nouvelles permissions. C'est la
cause la plus fréquente de « j'ai ajouté la capability mais rien n'a changé ».
</div>

## Ajouter une capability

Quand une page renvoie « Accès refusé » alors qu'elle devrait être accessible :

<ol class="steps" markdown="1">
<li markdown="1"><b>Identifier la capability manquante</b>
Le nom apparaît généralement dans le message d'erreur, ou dans la documentation Moodle de la page
concernée.</li>
<li markdown="1"><b>L'ajouter dans le tableau <code>$allow</code></b>
Fichier `scripts/setup_roles.php`, avec un commentaire expliquant à quoi elle sert.</li>
<li markdown="1"><b>Relancer le script</b>
`cat scripts/setup_roles.php | docker compose exec -T -u www-data moodle php`</li>
<li markdown="1"><b>Purger, puis se reconnecter</b>
Et vérifier que la page est bien accessible.</li>
</ol>

Exemples réels rencontrés sur ce projet :

| Page en erreur | Capability ajoutée |
|---|---|
| Importer des utilisateurs (CSV) | `moodle/site:uploadusers` |
| Utilisateurs en ligne | `report/loglive:view`, `report/log:viewlive` |
| Liste complète des utilisateurs | `moodle/user:viewalldetails` |
