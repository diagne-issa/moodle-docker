<?php
/**
 * URDFS - Configuration des roles (moindre privilege).
 *
 * Cree/met a jour le role "Technopedagogue" et durcit le role Enseignant.
 * Idempotent : peut etre relance sans effet de bord.
 *
 * Lancement (depuis moodle-docker/) :
 *   cat scripts/setup_roles.php | docker compose exec -T -u www-data moodle php
 *
 * @package   local_urdfs
 * @copyright 2026 URDFS
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;
require_once($CFG->libdir . '/accesslib.php');

$syscontext = context_system::instance();

// -------------------------------------------------------------------
// 1) ROLE TECHNOPEDAGOGUE
// -------------------------------------------------------------------
$shortname = 'technopedagogue';
$role = $DB->get_record('role', ['shortname' => $shortname]);

if (!$role) {
    // Base sur l'archetype "manager" (jeu de capabilities pedagogiques
    // sensees), qu'on restreint ensuite. NB: n'herite PAS de site:config.
    $roleid = create_role(
        'Technopédagogue',
        $shortname,
        'Gestion pédagogique de la plateforme : cours, catégories, enseignants, '
        . 'étudiants, contenus et suivi. Aucun accès aux réglages techniques.',
        'manager'
    );
    echo "Role Technopedagogue cree (id=$roleid)\n";
} else {
    $roleid = $role->id;
    echo "Role Technopedagogue deja present (id=$roleid) - mise a jour\n";
}

// Contextes ou le role peut etre attribue.
set_role_contextlevels($roleid, [CONTEXT_SYSTEM, CONTEXT_COURSECAT, CONTEXT_COURSE]);

// --- Capabilities EXPLICITEMENT INTERDITES (technique/sensible) -----
$prevent = [
    'moodle/site:config',            // reglages du site (plugins, serveur, secu...)
    'moodle/site:configview',        // voir les pages de reglages
    'moodle/role:manage',            // definir/modifier les roles
    'moodle/role:override',          // surcharger des permissions
    'moodle/role:safeoverride',
    'moodle/site:maintenanceaccess', // mode maintenance
    'moodle/webservice:managealltokens',
];
foreach ($prevent as $cap) {
    if (get_capability_info($cap)) {
        assign_capability($cap, CAP_PREVENT, $roleid, $syscontext->id, true);
    }
}
echo "Capabilities sensibles interdites (Prevent).\n";

// --- Capabilities EXPLICITEMENT AUTORISEES (pedagogie) --------------
$allow = [
    'moodle/course:create',
    'moodle/course:update',
    'moodle/course:delete',
    'moodle/course:visibility',
    'moodle/course:manageactivities',
    'moodle/course:viewhiddencourses',
    'moodle/category:manage',
    'moodle/category:viewhiddencategories',
    'moodle/user:create',
    'moodle/user:update',
    // Import de listes d'etudiants par fichier CSV (rentree scolaire).
    'moodle/site:uploadusers',
    'moodle/user:viewdetails',
    'moodle/user:viewalldetails',
    'moodle/role:assign',
    'enrol/manual:enrol',
    'enrol/manual:manage',
    'enrol/manual:unenrol',
    'moodle/grade:viewall',
    'moodle/grade:edit',
    // Reutilisation de cours (sauvegarde / restauration / import).
    'moodle/backup:backupcourse',
    'moodle/backup:backuptargetimport',
    'moodle/restore:restorecourse',
    'moodle/restore:restoretargetimport',
    'moodle/restore:viewautomatedfilearea',
    // --- SUPERVISION PEDAGOGIQUE (voir sans etre inscrit) --------------
    'moodle/course:view',                 // entrer dans n'importe quel cours
    'moodle/course:viewparticipants',
    'moodle/course:viewhiddenactivities',
    'moodle/course:viewhiddensections',
    'moodle/site:accessallgroups',
    'moodle/site:viewreports',            // rapports pedagogiques
    'moodle/grade:viewhidden',
    'report/log:view',
    'report/log:viewlive',
    'report/loglive:view',
    'report/outline:view',
    'report/participation:view',
    'report/progress:view',
    'moodle/analytics:listinsights',
    // Cohortes (classes / promotions).
    'moodle/cohort:view',
    'moodle/cohort:manage',
    'moodle/cohort:assign',
];
foreach ($allow as $cap) {
    if (get_capability_info($cap)) {
        assign_capability($cap, CAP_ALLOW, $roleid, $syscontext->id, true);
    }
}
echo "Capabilities pedagogiques autorisees (Allow).\n";

// --- Matrice d'attribution : QUE enseignant / non-editeur / etudiant
// (empeche le technopedagogue d'attribuer Manager ou Admin => pas d'escalade)
$targets = ['editingteacher', 'teacher', 'student'];
// On repart d'une matrice propre pour ce role.
$DB->delete_records('role_allow_assign', ['roleid' => $roleid]);
foreach ($targets as $tsn) {
    if ($t = $DB->get_record('role', ['shortname' => $tsn])) {
        core_role_set_assign_allowed($roleid, $t->id);
    }
}
echo "Attributions autorisees limitees a : " . implode(', ', $targets) . "\n";

// -------------------------------------------------------------------
// 2) DURCISSEMENT DU ROLE ENSEIGNANT (editingteacher)
//    Il ne peut deja pas creer de cours ; on l'interdit explicitement.
// -------------------------------------------------------------------
if ($et = $DB->get_record('role', ['shortname' => 'editingteacher'])) {
    $etprevent = [
        'moodle/course:create',
        'moodle/category:manage',
        'moodle/course:delete',
    ];
    foreach ($etprevent as $cap) {
        if (get_capability_info($cap)) {
            assign_capability($cap, CAP_PREVENT, $et->id, $syscontext->id, true);
        }
    }
    echo "Role Enseignant durci (pas de creation/suppression de cours, pas de categorie).\n";
}

// -------------------------------------------------------------------
// Finalisation
// -------------------------------------------------------------------
$syscontext->mark_dirty();
purge_all_caches();
echo "\nOK. Configuration des roles appliquee.\n";
echo "Pense a attribuer le role Technopedagogue a un utilisateur :\n";
echo "  Admin > Utilisateurs > Permissions > Attribuer des roles systeme > Technopédagogue\n";
