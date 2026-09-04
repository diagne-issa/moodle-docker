<?php
/**
 * Theme URDFS - surcharge de la mise en page « embedded ».
 *
 * Moodle utilise « embedded » pour des pages autonomes (iframes, selecteur
 * de fichiers, H5P...) MAIS aussi pour l'interface de correction des devoirs
 * (mod/assign, action=grader), qui se retrouve alors sans notre coquille.
 *
 * Ici : la page de correction est rendue avec notre mise en page normale
 * (sidebar + barre superieure) ; toutes les autres pages embarquees gardent
 * le rendu minimal d'origine.
 *
 * @package    theme_urdfs
 * @copyright  2026 URDFS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$urdfsaction = optional_param('action', '', PARAM_ALPHANUMEXT);
$urdfsisgrader = ($PAGE->pagetype === 'mod-assign-view')
    && in_array($urdfsaction, ['grader', 'grade'], true);

if ($urdfsisgrader) {
    // Notre mise en page complete (le template drawers est deja surcharge
    // par le theme : sidebar URDFS + barre superieure).
    require($CFG->dirroot . '/theme/boost/layout/drawers.php');
} else {
    // Rendu minimal d'origine (iframes, selecteur de fichiers, etc.).
    require($CFG->dirroot . '/theme/boost/layout/embedded.php');
}
