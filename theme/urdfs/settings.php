<?php
/**
 * Theme URDFS - admin settings.
 *
 * @package    theme_urdfs
 * @copyright  2026 URDFS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    $settings = new theme_boost_admin_settingspage_tabs('themesettingurdfs', get_string('configtitle', 'theme_urdfs'));

    // General settings tab.
    $page = new admin_settingpage('theme_urdfs_general', get_string('generalsettings', 'theme_urdfs'));

    // Preset Boost (base de la compilation SCSS).
    $presets = [
        'default.scss' => 'Default',
        'plain.scss'   => 'Plain',
    ];
    $setting = new admin_setting_configselect(
        'theme_urdfs/preset',
        get_string('preset', 'theme_urdfs'),
        get_string('preset_desc', 'theme_urdfs'),
        'default.scss',
        $presets
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Couleur principale de la marque.
    $setting = new admin_setting_configcolourpicker(
        'theme_urdfs/brandcolor',
        get_string('brandcolor', 'theme_urdfs'),
        get_string('brandcolor_desc', 'theme_urdfs'),
        '#143876'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Couleur d'accent (or), réservée aux points forts.
    $setting = new admin_setting_configcolourpicker(
        'theme_urdfs/accentcolor',
        get_string('accentcolor', 'theme_urdfs'),
        get_string('accentcolor_desc', 'theme_urdfs'),
        '#06843c'
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    // Raw SCSS, appended at the end of the compiled stylesheet.
    $setting = new admin_setting_configtextarea(
        'theme_urdfs/rawscss',
        get_string('rawscss', 'theme_urdfs'),
        get_string('rawscss_desc', 'theme_urdfs'),
        '',
        PARAM_RAW
    );
    $setting->set_updatedcallback('theme_reset_all_caches');
    $page->add($setting);

    $settings->add($page);
}
