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
