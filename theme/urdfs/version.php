<?php
/**
 * Theme URDFS - version information.
 *
 * @package    theme_urdfs
 * @copyright  2026 URDFS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version   = 2026072801;
$plugin->requires  = 2024100700; // Moodle 4.5 / 5.0+
$plugin->component = 'theme_urdfs';
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0';
$plugin->dependencies = [
    'theme_boost' => 2024100700,
];
