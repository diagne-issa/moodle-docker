<?php
/**
 * URDFS - Role "Responsable pedagogique".
 *
 * Suivi operationnel D'UNE formation, par opposition au Technopedagogue
 * qui pilote l'ensemble de la plateforme.
 *
 * LA DIFFERENCE EST STRUCTURELLE, PAS SEULEMENT UNE LISTE DE DROITS :
 *   - Technopedagogue        : attribuable au niveau SYSTEME -> voit tout.
 *   - Responsable pedagogique: attribuable uniquement sur une CATEGORIE
 *                              (ou un cours) -> ne voit que sa formation.
 *
 * C'est le contexte d'attribution qui cloisonne, et c'est la seule
 * methode fiable. Filtrer par l'interface ne protegerait rien : il
 * suffirait de saisir l'URL d'un autre cours.
 *
 * Ce role OBSERVE et RAPPORTE. Il ne modifie ni les cours, ni les notes,
 * ni les comptes : la correction reste la responsabilite de l'enseignant.
 *
 * Idempotent : peut etre relance sans effet de bord.
 *
 * Lancement (depuis moodle-docker/) :
 *   cat scripts/setup_responsable_pedagogique.php | docker compose exec -T -u www-data moodle php
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
// 1) CREATION OU MISE A JOUR DU ROLE
// -------------------------------------------------------------------
$shortname = 'responsablepedagogique';
$role = $DB->get_record('role', ['shortname' => $shortname]);

if (!$role) {
    // Archetype "teacher" (enseignant NON editeur) : il donne l'acces en
    // lecture aux cours, aux participants et aux notes, sans les droits
    // de modification du contenu. C'est exactement la posture attendue.
    // L'archetype "manager" aurait donne bien trop de pouvoir.
    $roleid = create_role(
        'Responsable pédagogique',
        $shortname,
        'Suivi opérationnel d\'une formation : cours, enseignants, étudiants, '
        . 'progression, assiduité, résultats et rapports. Périmètre limité à la '
        . 'catégorie sur laquelle le rôle est attribué. Aucun droit de '
        . 'modification des cours ni des notes.',
        'teacher'
    );
    echo "Role Responsable pedagogique cree (id=$roleid)\n";
} else {
    $roleid = $role->id;
    echo "Role Responsable pedagogique deja present (id=$roleid) - mise a jour\n";
}

// LE POINT CLE : pas de CONTEXT_SYSTEM dans cette liste.
// Le role ne peut donc pas etre attribue globalement, meme par erreur.
set_role_contextlevels($roleid, [CONTEXT_COURSECAT, CONTEXT_COURSE]);
echo "Attribution limitee aux categories et aux cours (jamais au systeme).\n";

// -------------------------------------------------------------------
// 2) CAPABILITIES INTERDITES
// -------------------------------------------------------------------
$prevent = [
    // Technique et securite : hors de son perimetre.
    'moodle/site:config',
    'moodle/site:configview',
    'moodle/site:maintenanceaccess',
    'moodle/webservice:managealltokens',

    // Gestion des roles : il ne doit pas pouvoir s'elever ni elever autrui.
    'moodle/role:manage',
    'moodle/role:assign',
    'moodle/role:override',
    'moodle/role:safeoverride',

    // Structure des cours : il suit, il ne construit pas.
    'moodle/course:create',
    'moodle/course:delete',
    'moodle/course:update',
    'moodle/course:manageactivities',
    'moodle/category:manage',

    // Comptes utilisateurs : releve du technopedagogue.
    'moodle/user:create',
    'moodle/user:update',
    'moodle/user:delete',
    'moodle/site:uploadusers',

    // NOTES : il les consulte, il ne les modifie jamais.
    // La correction appartient a l'enseignant, et c'est une garantie
    // pedagogique autant qu'une regle de securite.
    'moodle/grade:edit',
    'moodle/grade:manage',
    'moodle/grade:manageletters',
    'mod/assign:grade',
    'mod/quiz:grade',
];
foreach ($prevent as $cap) {
    if (get_capability_info($cap)) {
        assign_capability($cap, CAP_PREVENT, $roleid, $syscontext->id, true);
    }
}
echo "Capabilities de modification et techniques interdites (Prevent).\n";

// -------------------------------------------------------------------
// 3) CAPABILITIES AUTORISEES
// -------------------------------------------------------------------
$allow = [
    // --- Sa formation : voir les cours, meme masques ---------------
    'moodle/course:view',
    'moodle/course:viewhiddencourses',
    'moodle/course:viewhiddensections',
    'moodle/course:viewhiddenactivities',
    'moodle/course:viewparticipants',
    'moodle/category:viewhiddencategories',
    'moodle/course:isincompletionreports',

    // --- Suivi etudiant --------------------------------------------
    'moodle/user:viewdetails',
    'moodle/user:viewalldetails',
    'moodle/user:viewhiddendetails',
    'moodle/user:viewuseractivitiesreport',
    'moodle/site:accessallgroups',

    // --- Notes et resultats : LECTURE seule -------------------------
    'moodle/grade:viewall',
    'moodle/grade:viewhidden',
    'gradereport/grader:view',
    'gradereport/overview:view',
    'gradereport/user:view',
    'gradereport/summary:view',

    // --- Rapports et analytics --------------------------------------
    'report/outline:view',
    'report/log:view',
    'report/loglive:view',
    'report/participation:view',
    'report/progress:view',
    'report/completion:view',
    'report/courseoverview:view',
    'moodle/analytics:listinsights',

    // --- Export pour les bilans de formation ------------------------
    'gradeexport/csv:view',
    'gradeexport/xls:view',
    'gradeexport/ods:view',

    // --- Cohortes : voir la composition des promotions --------------
    'moodle/cohort:view',

    // --- Assiduite (plugin Presence) --------------------------------
    'mod/attendance:view',
    'mod/attendance:viewreports',
    'mod/attendance:viewsummaryreports',

    // --- Suivi des remises et des quiz, en consultation -------------
    'mod/assign:viewgrades',
    'mod/assign:viewblinddetails',
    'mod/quiz:viewreports',

    // --- Communication et annonces ----------------------------------
    'moodle/site:sendmessage',
    'moodle/course:bulkmessaging',
    'mod/forum:addnews',
    'mod/forum:startdiscussion',
    'mod/forum:viewdiscussion',

    // --- Calendrier et planning de la formation ---------------------
    'moodle/calendar:manageentries',
    'moodle/calendar:managegroupentries',
];
foreach ($allow as $cap) {
    if (get_capability_info($cap)) {
        assign_capability($cap, CAP_ALLOW, $roleid, $syscontext->id, true);
    }
}
echo "Capabilities de suivi, de consultation et de reporting accordees (Allow).\n";

// -------------------------------------------------------------------
// 4) QUI PEUT ATTRIBUER CE ROLE
// -------------------------------------------------------------------
// Le technopedagogue doit pouvoir nommer un responsable sur une
// formation, sans passer par l'administrateur.
$techno = $DB->get_record('role', ['shortname' => 'technopedagogue']);
if ($techno) {
    core_role_set_assign_allowed($techno->id, $roleid);
    echo "Le Technopedagogue peut desormais attribuer ce role.\n";
}

// Le responsable pedagogique, lui, n'attribue aucun role : on repart
// d'une matrice vide pour eviter toute escalade.
$DB->delete_records('role_allow_assign', ['roleid' => $roleid]);
echo "Le Responsable pedagogique ne peut attribuer aucun role.\n";

// -------------------------------------------------------------------
// 5) FIN
// -------------------------------------------------------------------
purge_all_caches();

echo "\n=========================================\n";
echo "OK. Role Responsable pedagogique configure.\n\n";
echo "POUR L'ATTRIBUER, c'est different du Technopedagogue :\n";
echo "  Cours > Categories > ouvrir la categorie de la formation\n";
echo "  > Attribuer des roles > Responsable pedagogique\n\n";
echo "Attribuer sur la CATEGORIE de la formation, jamais au niveau\n";
echo "du site : c'est ce qui limite sa vue a sa seule formation.\n";
echo "L'utilisateur doit se deconnecter puis se reconnecter.\n";
