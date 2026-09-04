<?php
/**
 * Theme URDFS - configuration.
 *
 * Child theme of Boost, branded for the URDFS Moodle platform with
 * dedicated styling for the 3 main spaces: admin, enseignant, etudiant.
 *
 * @package    theme_urdfs
 * @copyright  2026 URDFS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$THEME->name = 'urdfs';
$THEME->parents = ['boost'];

$THEME->sheets = [];
$THEME->editor_sheets = [];

// SCSS compilation hooks (reuse Boost pipeline + URDFS overrides).
$THEME->scss = function ($theme) {
    return theme_urdfs_get_main_scss_content($theme);
};
$THEME->prescsscallback = 'theme_urdfs_get_pre_scss';
$THEME->extrascsscallback = 'theme_urdfs_get_extra_scss';

$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->enable_dock = false;
$THEME->yuicssmodules = [];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';

// Layouts are inherited from Boost. We only override rendering via the
// custom core_renderer (role-based body classes) and SCSS (branding).
// NB : ne pas surcharger $THEME->layouts ici — cela remplacerait TOUTES
// les mises en page heritees (pop-ups, selecteur de fichiers, mode examen).
$THEME->layouts = [];

$THEME->csspostprocess = 'theme_urdfs_process_css';
