<?php
/**
 * URDFS - Definit un mot de passe de test pour un etudiant.
 *
 * Usage (depuis moodle-docker/) :
 *   cat scripts/set_student_password.php | docker compose exec -T -u www-data moodle php
 *
 * Modifier $email / $password ci-dessous si besoin.
 *
 * @package   local_urdfs
 * @copyright 2026 URDFS
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;
require_once($CFG->dirroot . '/user/lib.php');

$email    = 'mariama.ba5@urdfs.edu.sn';
$password = 'Etudiant@2026';

$user = $DB->get_record('user', ['email' => $email, 'deleted' => 0]);
if (!$user) {
    echo "Utilisateur introuvable : $email\n";
    exit(1);
}

// Definit le mot de passe (hash correctement par Moodle).
update_internal_user_password($user, $password);

// S'assurer que le compte est confirme et actif.
$DB->set_field('user', 'confirmed', 1, ['id' => $user->id]);
$DB->set_field('user', 'suspended', 0, ['id' => $user->id]);

echo "=== Identifiants de test (etudiant) ===\n";
echo "  Nom          : " . fullname($user) . "\n";
echo "  Utilisateur  : " . $user->username . "\n";
echo "  Mot de passe : $password\n";
echo "\nCours de cet etudiant :\n";
foreach (enrol_get_users_courses($user->id, true) as $c) {
    echo "  - " . format_string($c->fullname) . "\n";
}
