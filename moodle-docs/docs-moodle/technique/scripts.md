---
icon: material/console
---

<div class="page-head" markdown="1">
<p class="crumb">Technique · 08</p>

# Scripts d'automatisation

Sept scripts CLI dans `scripts/`, tous idempotents : les relancer ne duplique rien.
</div>

## Comment les exécuter

```bash
# Depuis le dossier moodle-docker du dépôt cloné
cat scripts/NOM_DU_SCRIPT.php | docker compose exec -T -u www-data moodle php
```

<div class="note" markdown="1">
<b>Pourquoi <code>cat … | docker compose exec -T</code></b>
Cette forme envoie le script sur l'entrée standard de PHP dans le conteneur. Elle évite d'avoir à
monter le dossier `scripts/` et fonctionne quel que soit l'emplacement du fichier. Le `-T` désactive
l'allocation d'un pseudo-terminal, indispensable pour que le *pipe* passe correctement.
</div>

## Les sept scripts

<div class="card" markdown="1">
#### `setup_roles.php` <span class="badge neutral">essentiel</span>

Crée ou met à jour le rôle Technopédagogue, applique les listes Allow et Prevent, verrouille la
matrice d'attribution contre l'escalade de privilèges, et durcit le rôle Enseignant. Se termine par
`purge_all_caches()`.

**À relancer après toute évolution des permissions.** Voir la page
[Rôles & permissions](roles.md).
</div>

<div class="card" markdown="1">
#### `setup_offre_formation.php`

Crée le catalogue complet : 5 domaines, 20 formations, les niveaux L1/L2/L3 ou M1/M2 selon le cycle,
et une cohorte par classe. Vérifie l'existence de chaque élément avant création.
</div>

<div class="card" markdown="1">
#### `setup_pilote_dataia.php`

Jeu de démonstration complet : catégories, cohorte `L1-DATA-2026`, quatre cours, et synchronisation
des inscriptions. C'est le script qui produit l'environnement utilisé pour la présentation.
</div>

<div class="card" markdown="1">
#### `add_students_to_cohort.php`

Ajoute les comptes `@urdfs.edu.sn` à une cohorte. **Exclut explicitement** les administrateurs et les
technopédagogues, détectés par `has_capability('moodle/course:create', $syscontext, $u->id)`, et
les retire s'ils y figuraient déjà.
</div>

<div class="card" markdown="1">
#### `create_teacher.php`

Crée un compte enseignant de démonstration (`prof.diop`) et lui attribue le rôle *Enseignant* sur les
cours du pilote.
</div>

<div class="card" markdown="1">
#### `create_demo_content.php`

Ajoute une page, un forum et un devoir au cours d'Algorithmique, et active le suivi d'achèvement.
C'est ce contenu qui permet de démontrer le cycle remise → correction → note.
</div>

<div class="card" markdown="1">
#### `set_student_password.php`

Définit le mot de passe d'un compte étudiant, pour pouvoir tester l'espace étudiant sans passer par
la procédure de réinitialisation par courriel, impossible ici puisqu'il n'y a pas de serveur SMTP.
</div>

## Écrire un nouveau script

Le squelette commun :

```php
<?php
define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;

// … le travail …

purge_all_caches();
echo "OK.\n";
```

### Les trois règles

1. **Idempotence.** Toujours vérifier l'existence avant de créer. Un script qu'on n'ose pas relancer
   est un script inutilisable.
2. **Paramètres liés en SQL.** Jamais de concaténation.
3. **Sortie explicite.** Un `echo` par étape : c'est la seule trace disponible quand quelque chose se
   passe mal.

<div class="warn" markdown="1">
<b>Attention aux guillemets en PostgreSQL</b>
PostgreSQL interprète les guillemets doubles comme des *identifiants de colonne* :
`WHERE name = "Algorithmique"` échoue avec `column "Algorithmique" does not exist`. Toujours passer
par des paramètres liés (`?`), jamais par de la concaténation de chaînes.
</div>

```php
// Faux : PostgreSQL cherche une colonne nommée "Algorithmique"
$DB->get_record_sql('SELECT * FROM {course} WHERE fullname = "Algorithmique"');

// Correct
$DB->get_record_sql('SELECT * FROM {course} WHERE fullname = ?', ['Algorithmique']);
```
