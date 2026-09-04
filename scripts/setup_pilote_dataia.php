<?php
/**
 * URDFS - Pilote d'architecture academique.
 *
 * Cree, pour UNE formation / UN niveau / UNE promotion :
 *   - l'arborescence de categories (Domaine > Formation > Annee > Niveau)
 *   - la cohorte de classe
 *   - les cours (1 par matiere) dans la categorie du niveau
 *   - la methode "Synchronisation des cohortes" sur chaque cours
 *     (la cohorte = tous les etudiants de la classe, role Etudiant)
 *
 * Idempotent : relançable sans doublon.
 *
 * Lancement (depuis moodle-docker/) :
 *   cat scripts/setup_pilote_dataia.php | docker compose exec -T -u www-data moodle php
 *
 * @package   local_urdfs
 * @copyright 2026 URDFS
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/enrol/cohort/locallib.php');

// =====================================================================
// PARAMETRES DU PILOTE  (a adapter : domaine, formation, annee, niveau,
// nom de la classe, et la LISTE DES MATIERES/COURS de ce niveau)
// =====================================================================
$domaine   = 'Numérique & Intelligence Artificielle';
$formation = 'Licence Data & IA';
$annee     = '2026-2027';
$niveau    = 'L1';
$classe_nom      = 'L1 Data & IA — 2026';
$classe_idnumber = 'L1-DATA-2026';

// Les matieres de ce niveau -> un cours chacune (modifie librement).
$matieres = [
    'Algorithmique',
    'Programmation',
    'Mathématiques pour l\'IA',
    'Introduction aux bases de données',
];

// idnumbers de categories (stables -> idempotence).
$cat_dom_idn   = 'DOM-NUM-IA';
$cat_form_idn  = 'FORM-DATA-IA';
$cat_annee_idn = 'AN-DATA-IA-2026';
$cat_niv_idn   = 'NIV-DATA-IA-L1-2026';

// =====================================================================
// 1) ARBORESCENCE DE CATEGORIES
// =====================================================================
function urdfs_get_or_create_category(string $name, int $parentid, string $idnumber): int {
    global $DB;
    if ($rec = $DB->get_record('course_categories', ['idnumber' => $idnumber])) {
        return (int) $rec->id;
    }
    if ($rec = $DB->get_record('course_categories', ['name' => $name, 'parent' => $parentid])) {
        return (int) $rec->id;
    }
    $cat = core_course_category::create([
        'name'     => $name,
        'parent'   => $parentid,
        'idnumber' => $idnumber,
        'visible'  => 1,
    ]);
    return (int) $cat->id;
}

$domid   = urdfs_get_or_create_category($domaine, 0, $cat_dom_idn);
$formid  = urdfs_get_or_create_category($formation, $domid, $cat_form_idn);
$anneeid = urdfs_get_or_create_category($annee, $formid, $cat_annee_idn);
$nivid   = urdfs_get_or_create_category($niveau, $anneeid, $cat_niv_idn);
echo "Categories OK (niveau id=$nivid)\n";

// =====================================================================
// 2) COHORTE DE CLASSE (contexte systeme)
// =====================================================================
$syscontext = context_system::instance();
$cohort = $DB->get_record('cohort', ['idnumber' => $classe_idnumber]);
if (!$cohort) {
    $cid = cohort_add_cohort((object) [
        'name'        => $classe_nom,
        'idnumber'    => $classe_idnumber,
        'contextid'   => $syscontext->id,
        'visible'     => 1,
        'description' => 'Classe / promotion ' . $classe_nom,
    ]);
    echo "Cohorte creee (id=$cid)\n";
} else {
    $cid = (int) $cohort->id;
    echo "Cohorte deja presente (id=$cid)\n";
}

// =====================================================================
// 3) ACTIVER LA METHODE D'INSCRIPTION PAR COHORTE
// =====================================================================
$enabled = array_filter(explode(',', $CFG->enrol_plugins_enabled));
if (!in_array('cohort', $enabled, true)) {
    $enabled[] = 'cohort';
    set_config('enrol_plugins_enabled', implode(',', $enabled));
    echo "Methode d'inscription 'cohorte' activee.\n";
}

// =====================================================================
// 4) COURS (1 par matiere) + SYNCHRONISATION DE LA COHORTE (role Etudiant)
// =====================================================================
$studentroleid = (int) $DB->get_field('role', 'id', ['shortname' => 'student']);
$cohortplugin  = enrol_get_plugin('cohort');

foreach ($matieres as $i => $matiere) {
    $slug  = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $matiere), 0, 8));
    $short = 'DATAIA-L1-2026-' . $slug . '-' . ($i + 1);
    $full  = $matiere . ' — L1 Data & IA — 2026';

    $course = $DB->get_record('course', ['shortname' => $short]);
    if (!$course) {
        $course = create_course((object) [
            'fullname'  => $full,
            'shortname' => $short,
            'category'  => $nivid,
            'visible'   => 1,
        ]);
        echo "Cours cree : $full\n";
    } else {
        echo "Cours deja present : $full\n";
    }

    // Instance de synchronisation de cohorte (cohorte -> role Etudiant).
    $exists = $DB->record_exists('enrol', [
        'courseid'   => $course->id,
        'enrol'      => 'cohort',
        'customint1' => $cid,
    ]);
    if (!$exists) {
        $cohortplugin->add_instance($course, [
            'customint1' => $cid,
            'roleid'     => $studentroleid,
            'name'       => 'Classe ' . $classe_idnumber,
        ]);
        echo "  -> synchronisation cohorte ajoutee\n";
    }
}

// Lancer la synchronisation (inscrit immediatement les membres de la cohorte).
enrol_cohort_sync(new null_progress_trace());
purge_all_caches();

echo "\nOK. Pilote en place.\n";
echo "Prochaines etapes :\n";
echo " - Ajouter les etudiants a la cohorte '$classe_idnumber' (ils seront inscrits automatiquement dans les " . count($matieres) . " cours).\n";
echo " - Affecter les enseignants (role Enseignant) dans les cours qu'ils assurent.\n";
