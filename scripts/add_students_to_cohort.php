<?php
/**
 * URDFS - Ajoute les etudiants existants a la cohorte de classe.
 *
 * Cible : tous les comptes @urdfs.edu.sn (hors administrateurs) qui ne sont
 * pas deja membres. L'ajout a la cohorte declenche l'inscription automatique
 * dans les cours branches sur cette cohorte (synchronisation).
 *
 * Idempotent. Lancement (depuis moodle-docker/) :
 *   cat scripts/add_students_to_cohort.php | docker compose exec -T -u www-data moodle php
 *
 * @package   local_urdfs
 * @copyright 2026 URDFS
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');

$classe_idnumber = 'L1-DATA-2026';   // <-- cohorte cible (a adapter au besoin)
$email_pattern   = '%@urdfs.edu.sn'; // <-- domaine des etudiants

$cohort = $DB->get_record('cohort', ['idnumber' => $classe_idnumber]);
if (!$cohort) {
    echo "Cohorte '$classe_idnumber' introuvable. Lance d'abord setup_pilote_dataia.php.\n";
    exit(1);
}

$users = $DB->get_records_select(
    'user',
    "deleted = 0 AND suspended = 0 AND email LIKE :email AND id > 2",
    ['email' => $email_pattern],
    'lastname ASC'
);

$syscontext = context_system::instance();

// Nettoyage : retirer de la cohorte tout membre qui est admin ou
// technopedagogue (ils ne doivent pas y figurer comme etudiants).
$members = $DB->get_records('cohort_members', ['cohortid' => $cohort->id]);
foreach ($members as $m) {
    $isadmin  = is_siteadmin($m->userid);
    $istechno = has_capability('moodle/course:create', $syscontext, $m->userid);
    if ($isadmin || $istechno) {
        cohort_remove_member($cohort->id, $m->userid);
        $uu = $DB->get_record('user', ['id' => $m->userid]);
        echo "  - retire (admin/technopedagogue) : " . ($uu ? fullname($uu) : $m->userid) . "\n";
    }
}

$added = 0;
foreach ($users as $u) {
    if (is_siteadmin($u)) {
        continue; // jamais un admin comme etudiant
    }
    // Exclure les technopedagogues (ils pilotent, ils ne sont pas etudiants).
    if (has_capability('moodle/course:create', $syscontext, $u->id)) {
        continue;
    }
    if (cohort_is_member($cohort->id, $u->id)) {
        continue;
    }
    cohort_add_member($cohort->id, $u->id);
    $added++;
    echo "  + " . fullname($u) . "  (" . $u->email . ")\n";
}

// Securite : forcer la synchronisation des inscriptions.
enrol_cohort_sync(new null_progress_trace());
purge_all_caches();

echo "\n$added etudiant(s) ajoute(s) a la cohorte '$classe_idnumber'.\n";
echo "Ils sont maintenant inscrits automatiquement dans les cours de la classe.\n";
