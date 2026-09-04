<?php
/**
 * URDFS - Remplit le cours vitrine de contenu pour la demo V1.
 *
 * Cours cible : "Algorithmique — L1 Data & IA — 2026".
 * Ajoute : suivi d'achevement + une Page (support), un Forum, un Devoir.
 * Chaque activite est isolee (try/catch) : un echec n'empeche pas les autres.
 *
 * Lancement (depuis moodle-docker/) :
 *   cat scripts/create_demo_content.php | docker compose exec -T -u www-data moodle php
 *
 * @package   local_urdfs
 * @copyright 2026 URDFS
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;
require_once($CFG->dirroot . '/course/modlib.php');
require_once($CFG->dirroot . '/lib/completionlib.php');

$course = $DB->get_record_select('course', "fullname LIKE 'Algorithmique%'", null, '*', IGNORE_MULTIPLE);
if (!$course) {
    echo "Cours 'Algorithmique...' introuvable.\n";
    exit(1);
}
echo "Cours cible : {$course->fullname} (id={$course->id})\n";

// 1) Activer le suivi d'achevement sur le cours.
if (empty($course->enablecompletion)) {
    $DB->set_field('course', 'enablecompletion', 1, ['id' => $course->id]);
    rebuild_course_cache($course->id, true);
    $course = $DB->get_record('course', ['id' => $course->id]);
    echo "Suivi d'achevement active.\n";
}

/**
 * Ajoute une activite si elle n'existe pas deja (par nom).
 */
function urdfs_addmod($course, stdClass $data): void {
    global $DB;
    if ($DB->record_exists_sql(
        "SELECT 1 FROM {course_modules} cm
           JOIN {modules} m ON m.id = cm.module
           JOIN {" . $data->modulename . "} inst ON inst.id = cm.instance
          WHERE cm.course = ? AND inst.name = ?",
        [$course->id, $data->name]
    )) {
        echo "  = deja present : {$data->name}\n";
        return;
    }
    try {
        $data->course  = $course->id;
        $data->section  = 0;
        $data->visible  = 1;
        $data->visibleoncoursepage = 1;
        $data->module   = $DB->get_field('modules', 'id', ['name' => $data->modulename]);
        $data->groupmode = 0;
        $data->groupingid = 0;
        add_moduleinfo($data, $course);
        echo "  + cree : {$data->name}\n";
    } catch (\Throwable $e) {
        echo "  ! ERREUR ({$data->name}) : " . $e->getMessage() . "\n";
    }
}

// 2) PAGE — support de cours.
urdfs_addmod($course, (object) [
    'modulename'    => 'page',
    'name'          => 'Support de cours — Introduction à l\'algorithmique',
    'intro'         => '<p>Support de la séance d\'introduction.</p>',
    'introformat'   => FORMAT_HTML,
    'content'       => '<h3>Chapitre 1 — Les bases</h3>'
                     . '<p>Un <strong>algorithme</strong> est une suite finie d\'instructions '
                     . 'permettant de résoudre un problème. Nous verrons : variables, conditions, '
                     . 'boucles et fonctions.</p><ul><li>Variables et types</li>'
                     . '<li>Structures conditionnelles</li><li>Boucles</li></ul>',
    'contentformat' => FORMAT_HTML,
    'display'       => 5,
    'printheading'  => 1,
    'printintro'    => 1,
    'printlastmodified' => 1,
    'completion'    => 2,   // achevement automatique
    'completionview' => 1,  // termine quand consulte
]);

// 3) FORUM — discussion.
urdfs_addmod($course, (object) [
    'modulename'    => 'forum',
    'name'          => 'Forum de discussion',
    'intro'         => '<p>Posez vos questions sur le cours d\'algorithmique.</p>',
    'introformat'   => FORMAT_HTML,
    'type'          => 'general',
    'forcesubscribe' => 0,
    'assessed'      => 0,
    'scale'         => 0,
    'maxbytes'      => 0,
    'maxattachments' => 1,
    'blockperiod'   => 0,
    'blockafter'    => 0,
    'warnafter'     => 0,
    'completion'    => 1,   // achevement manuel
]);

// 4) DEVOIR (assign) — remise en ligne.
urdfs_addmod($course, (object) [
    'modulename'    => 'assign',
    'name'          => 'Devoir 1 — Exercices d\'algorithmique',
    'intro'         => '<p>Rendez vos exercices (texte en ligne ou fichier PDF).</p>',
    'introformat'   => FORMAT_HTML,
    'alwaysshowdescription' => 1,
    'submissiondrafts' => 0,
    'requiresubmissionstatement' => 0,
    'sendnotifications' => 0,
    'sendlatenotifications' => 0,
    'sendstudentnotifications' => 1,
    'duedate'       => time() + (14 * DAYSECS),
    'allowsubmissionsfromdate' => time(),
    'gradingduedate' => 0,
    'cutoffdate'    => 0,
    'grade'         => 100,
    'teamsubmission' => 0,
    'requireallteammemberssubmit' => 0,
    'blindmarking'  => 0,
    'attemptreopenmethod' => 'none',
    'maxattempts'   => -1,
    'markingworkflow' => 0,
    'markingallocation' => 0,
    'assignsubmission_onlinetext_enabled' => 1,
    'assignsubmission_file_enabled' => 1,
    'assignsubmission_file_maxfiles' => 1,
    'assignsubmission_file_maxsizebytes' => 0,
    'assignfeedback_comments_enabled' => 1,
    'completion'    => 2,
    'completionsubmit' => 1,
]);

rebuild_course_cache($course->id, true);
purge_all_caches();

echo "\nTermine. Ouvre le cours pour verifier le contenu.\n";
echo "Quiz + session BigBlueButton : a ajouter via l'interface (guide fourni).\n";
