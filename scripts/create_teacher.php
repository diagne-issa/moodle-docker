<?php
/**
 * URDFS - Cree un enseignant de test et l'inscrit dans un cours du pilote.
 *
 * Lancement (depuis moodle-docker/) :
 *   cat scripts/create_teacher.php | docker compose exec -T -u www-data moodle php
 *
 * @package   local_urdfs
 * @copyright 2026 URDFS
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;
require_once($CFG->dirroot . '/user/lib.php');

$username  = 'prof.diop';
$email     = 'prof.diop@urdfs.edu.sn';
$password  = 'Enseignant@2026';
$firstname = 'Ousmane';
$lastname  = 'Diop';

// 1) Creer l'enseignant s'il n'existe pas.
$user = $DB->get_record('user', ['username' => $username]);
if (!$user) {
    $u = new stdClass();
    $u->auth        = 'manual';
    $u->confirmed   = 1;
    $u->mnethostid  = $CFG->mnethostid;
    $u->username    = $username;
    $u->email       = $email;
    $u->firstname   = $firstname;
    $u->lastname    = $lastname;
    $u->password    = $password;
    $uid = user_create_user($u, true, false);
    echo "Enseignant cree (id=$uid)\n";
} else {
    $uid = (int) $user->id;
    echo "Enseignant deja present (id=$uid)\n";
}

// 2) L'inscrire comme Enseignant (editingteacher) dans le cours Algorithmique.
$course = $DB->get_record_select('course', "fullname LIKE 'Algorithmique%'", null, '*', IGNORE_MULTIPLE);
if ($course) {
    $teacherroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
    $manual = enrol_get_plugin('manual');
    $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
    if (!$instance) {
        $manual->add_default_instance($course);
        $instance = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual']);
    }
    if ($instance) {
        $manual->enrol_user($instance, $uid, $teacherroleid);
        echo "Inscrit comme Enseignant dans : " . $course->fullname . "\n";
    }
} else {
    echo "Cours 'Algorithmique...' introuvable.\n";
}

purge_all_caches();

echo "\n=== Identifiants de l'enseignant de test ===\n";
echo "  Utilisateur : $username\n";
echo "  Mot de passe : $password\n";
echo "(A changer apres le premier test.)\n";
