<?php
/**
 * Theme URDFS - library functions.
 *
 * Contient :
 *  - le pipeline SCSS (delegue aux fonctions officielles de Boost, resistant
 *    aux mises a jour de Moodle) ;
 *  - les arbres de navigation de la sidebar sur-mesure (admin / enseignant /
 *    etudiant), consommes par classes/output/core_renderer.php::urdfs_sidebar().
 *
 * @package    theme_urdfs
 * @copyright  2026 URDFS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// =====================================================================
// PIPELINE SCSS
// =====================================================================

/**
 * SCSS principal : on reutilise le preset de Boost.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_urdfs_get_main_scss_content($theme) {
    global $CFG;
    require_once($CFG->dirroot . '/theme/boost/lib.php');
    return theme_boost_get_main_scss_content($theme);
}

/**
 * Pre-SCSS : base Boost + surcharges de variables URDFS.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_urdfs_get_pre_scss($theme) {
    global $CFG;
    require_once($CFG->dirroot . '/theme/boost/lib.php');

    $scss = theme_boost_get_pre_scss($theme);

    $variables = $CFG->dirroot . '/theme/urdfs/scss/_variables.scss';
    if (is_readable($variables)) {
        $scss .= "\n" . file_get_contents($variables);
    }

    return $scss;
}

/**
 * Extra SCSS : base Boost + composants URDFS.
 *
 * @param theme_config $theme
 * @return string
 */
function theme_urdfs_get_extra_scss($theme) {
    global $CFG;
    require_once($CFG->dirroot . '/theme/boost/lib.php');

    $content = theme_boost_get_extra_scss($theme);

    $custom = $CFG->dirroot . '/theme/urdfs/scss/_custom.scss';
    if (is_readable($custom)) {
        $content .= "\n" . file_get_contents($custom);
    }

    if (!empty($theme->settings->rawscss)) {
        $content .= "\n" . $theme->settings->rawscss;
    }

    return $content;
}

/**
 * Hook de post-traitement CSS (reserve).
 *
 * @param string $css
 * @param theme_config $theme
 * @return string
 */
function theme_urdfs_process_css($css, $theme) {
    return $css;
}

// =====================================================================
// NAVIGATION DE LA SIDEBAR (par role)
// =====================================================================

/**
 * L'utilisateur courant est-il un enseignant (et pas un admin) ?
 *
 * @return bool
 */
function theme_urdfs_is_teacher(): bool {
    global $COURSE;
    static $cache = null;

    if (!isloggedin() || isguestuser() || is_siteadmin()) {
        return false;
    }

    // 1) Dans un cours : on regarde le contexte du cours courant.
    if ($COURSE && $COURSE->id != SITEID) {
        $context = context_course::instance($COURSE->id);
        foreach (['moodle/course:update', 'moodle/course:manageactivities', 'mod/assign:grade'] as $cap) {
            if (has_capability($cap, $context)) {
                return true;
            }
        }
    }

    // 2) HORS d'un cours (tableau de bord, calendrier, messages...) :
    //    l'utilisateur est enseignant s'il enseigne dans AU MOINS un cours.
    //    Sans cela, il recevait le menu etudiant en dehors des cours.
    if ($cache === null) {
        $cache = !empty(theme_urdfs_get_teacher_courses());
    }
    return $cache;
}

/**
 * L'utilisateur courant est-il un technopédagogue (admin pédagogique) ?
 *
 * Détection propre : non-admin qui possède moodle/course:create au niveau
 * système (capability caractéristique du rôle Technopédagogue / Manager).
 *
 * @return bool
 */
function theme_urdfs_is_technopedagogue(): bool {
    if (!isloggedin() || isguestuser() || is_siteadmin()) {
        return false;
    }
    return has_capability('moodle/course:create', context_system::instance());
}

/**
 * L'utilisateur courant est-il responsable pédagogique ?
 *
 * On ne peut PAS le détecter par une capability : le rôle n'est jamais
 * attribué au niveau système (c'est précisément ce qui le cloisonne),
 * donc has_capability() au niveau système répond toujours non. On
 * interroge donc directement les attributions de rôle, quel que soit
 * le contexte (catégorie ou cours).
 *
 * @return bool
 */
function theme_urdfs_is_responsable(): bool {
    global $DB, $USER;
    static $cache = null;

    if (!isloggedin() || isguestuser() || is_siteadmin()) {
        return false;
    }
    if ($cache !== null) {
        return $cache;
    }

    try {
        $cache = $DB->record_exists_sql(
            "SELECT 1
               FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
              WHERE ra.userid = ? AND r.shortname = ?",
            [$USER->id, 'responsablepedagogique']
        );
    } catch (Throwable $e) {
        $cache = false;
    }
    return $cache;
}

/**
 * Catégories (formations) dont l'utilisateur courant est responsable.
 *
 * Un même responsable peut en porter plusieurs : les attributions
 * s'additionnent.
 *
 * @return array liste d'objets {id, name}, indexée numériquement
 */
function theme_urdfs_get_responsable_categories(): array {
    global $DB, $USER;
    static $cache = null;

    if ($cache !== null) {
        return $cache;
    }
    if (!isloggedin() || isguestuser()) {
        return $cache = [];
    }

    try {
        $rows = $DB->get_records_sql(
            "SELECT DISTINCT cc.id, cc.name, cc.path
               FROM {role_assignments} ra
               JOIN {role} r ON r.id = ra.roleid
               JOIN {context} ctx ON ctx.id = ra.contextid
               JOIN {course_categories} cc ON cc.id = ctx.instanceid
              WHERE ra.userid = ? AND r.shortname = ? AND ctx.contextlevel = ?
           ORDER BY cc.name ASC",
            [$USER->id, 'responsablepedagogique', CONTEXT_COURSECAT]
        );
        $cache = array_values($rows);
    } catch (Throwable $e) {
        $cache = [];
    }
    return $cache;
}

/**
 * Identifiants de TOUTES les catégories du périmètre : celles confiées
 * au responsable, plus leurs sous-catégories (un domaine contient des
 * formations, qui contiennent des niveaux).
 *
 * @return array liste d'identifiants
 */
function theme_urdfs_get_responsable_category_ids(): array {
    global $DB;

    $ids = [];
    foreach (theme_urdfs_get_responsable_categories() as $cat) {
        $ids[$cat->id] = $cat->id;
        try {
            $like = $DB->sql_like('path', ':p');
            $children = $DB->get_records_select('course_categories', $like,
                ['p' => '%/' . $cat->id . '/%'], '', 'id');
            foreach ($children as $child) {
                $ids[$child->id] = $child->id;
            }
        } catch (Throwable $e) {
            continue;
        }
    }
    return array_values($ids);
}

/**
 * Arbre de navigation RESPONSABLE PÉDAGOGIQUE.
 *
 * Il suit des formations, il n'administre rien : pas de création de
 * cours, pas de gestion de comptes. Les rapports globaux sont volontai-
 * rement absents (il n'a pas la capability au niveau système, le lien
 * renverrait « Accès refusé ») ; ils restent accessibles cours par cours.
 *
 * @return array
 */
function theme_urdfs_nav_tree_responsable(): array {
    $cats = theme_urdfs_get_responsable_categories();

    $children = [];
    foreach ($cats as $cat) {
        $children[] = [
            'label' => format_string($cat->name),
            'url'   => '/course/index.php?categoryid=' . $cat->id,
        ];
    }

    $formations = [
        'label' => 'Mes formations',
        'icon'  => 'folder-tree',
        'url'   => !empty($cats)
            ? '/course/index.php?categoryid=' . $cats[0]->id
            : '/course/index.php',
    ];
    // On n'ajoute un sous-menu que s'il y a plusieurs formations :
    // dérouler une liste d'un seul élément n'apporte rien.
    if (count($children) > 1) {
        $formations['children'] = $children;
    }

    return [
        ['group' => 'Pilotage', 'items' => [
            ['label' => 'Tableau de bord', 'icon' => 'gauge-high', 'url' => '/my/'],
        ]],

        ['group' => 'Suivi pédagogique', 'items' => [
            $formations,
            ['label' => 'Tous les cours', 'icon' => 'book', 'url' => '/course/index.php'],
        ]],

        ['group' => 'Organisation', 'items' => [
            ['label' => 'Calendrier', 'icon' => 'calendar-days', 'url' => '/calendar/view.php?view=month'],
            ['label' => 'Messages', 'icon' => 'comments', 'url' => '/message/index.php'],
        ]],
    ];
}

/**
 * Arbre de navigation TECHNOPÉDAGOGUE (gestion pédagogique, sans technique).
 *
 * @return array
 */
function theme_urdfs_nav_tree_technopedagogue(): array {
    return [
        ['group' => 'Pilotage', 'items' => [
            ['label' => 'Tableau de bord', 'icon' => 'gauge-high', 'url' => '/my/'],
        ]],

        ['group' => 'Pédagogie', 'items' => [
            ['label' => 'Cours', 'icon' => 'book',
             'url' => '/course/management.php?view=courses', 'children' => [
                ['label' => 'Créer un cours', 'url' => '/course/edit.php'],
                ['label' => 'Cours (vue publique)', 'url' => '/course/index.php'],
            ]],
            ['label' => 'Catégories', 'icon' => 'folder-tree',
             'url' => '/course/management.php?view=categories', 'children' => [
                ['label' => 'Gérer les catégories', 'url' => '/course/management.php?view=categories'],
                ['label' => 'Créer une catégorie', 'url' => '/course/editcategory.php?parent=0'],
            ]],
        ]],

        ['group' => 'Utilisateurs', 'items' => [
            ['label' => 'Utilisateurs', 'icon' => 'users', 'url' => '/admin/user.php', 'children' => [
                ['label' => 'Tous les utilisateurs', 'url' => '/admin/user.php'],
                ['label' => 'Ajouter un utilisateur', 'url' => '/user/editadvanced.php?id=-1'],
            ]],
            ['label' => 'Classes / Cohortes', 'icon' => 'user-group', 'url' => '/cohort/index.php', 'children' => [
                ['label' => 'Toutes les cohortes', 'url' => '/cohort/index.php'],
                ['label' => 'Créer une cohorte', 'url' => '/cohort/edit.php?contextid=1'],
                ['label' => 'Importer (CSV)', 'url' => '/admin/tool/uploaduser/index.php'],
            ]],
        ]],

        ['group' => 'Suivi', 'items' => [
            ['label' => 'Rapports', 'icon' => 'chart-column',
             'url' => '/report/log/index.php', 'children' => [
                ['label' => 'Journaux d\'activité', 'url' => '/report/log/index.php'],
                ['label' => 'Utilisateurs en ligne', 'url' => '/report/loglive/index.php'],
            ]],
        ]],

        ['group' => 'Organisation', 'items' => [
            ['label' => 'Calendrier', 'icon' => 'calendar-days', 'url' => '/calendar/view.php?view=month'],
            ['label' => 'Messages', 'icon' => 'comments', 'url' => '/message/index.php'],
        ]],
    ];
}

/**
 * Arbre de navigation ADMINISTRATEUR.
 *
 * Format : [ ['group' => 'Titre', 'items' => [ item, ... ]], ... ]
 * item : ['label','icon','url','external'?,'children'? => [ ['label','url','external'?], ... ] ]
 * (icon = nom Font Awesome sans le prefixe "fa-")
 *
 * @return array
 */
function theme_urdfs_nav_tree(): array {
    return [
        ['group' => 'Pilotage', 'items' => [
            ['label' => 'Tableau de bord', 'icon' => 'gauge-high', 'url' => '/my/'],
        ]],
        ['group' => 'Gestion', 'items' => [
            ['label' => 'Utilisateurs', 'icon' => 'users', 'url' => '/admin/user.php', 'children' => [
                ['label' => 'Tous les utilisateurs', 'url' => '/admin/user.php'],
                ['label' => 'Ajouter un utilisateur', 'url' => '/user/editadvanced.php?id=-1'],
                ['label' => 'Cohortes', 'url' => '/cohort/index.php'],
            ]],
            ['label' => 'Cours', 'icon' => 'book', 'url' => '/course/management.php', 'children' => [
                ['label' => 'Gérer cours & catégories', 'url' => '/course/management.php'],
                ['label' => 'Créer un cours', 'url' => '/course/edit.php'],
                ['label' => 'Créer une catégorie', 'url' => '/course/editcategory.php?parent=0'],
            ]],
        ]],
        ['group' => 'Suivi', 'items' => [
            ['label' => 'Rapports', 'icon' => 'chart-column',
             'url' => '/report/log/index.php', 'children' => [
                ['label' => 'Journaux d\'activité', 'url' => '/report/log/index.php'],
                ['label' => 'Utilisateurs en ligne', 'url' => '/report/loglive/index.php'],
            ]],
            ['label' => 'Calendrier', 'icon' => 'calendar-days', 'url' => '/calendar/view.php?view=month'],
        ]],
        ['group' => 'Système', 'items' => [
            ['label' => 'Administration', 'icon' => 'gear', 'url' => '/admin/search.php', 'children' => [
                ['label' => 'Réglages du site', 'url' => '/admin/search.php'],
                ['label' => 'Plugins', 'url' => '/admin/plugins.php'],
                ['label' => 'Apparence', 'url' => '/admin/category.php?category=appearance'],
                ['label' => 'Serveur', 'url' => '/admin/category.php?category=server'],
                ['label' => 'Sécurité', 'url' => '/admin/category.php?category=security'],
            ]],
        ]],
    ];
}

/**
 * Arbre de navigation ENSEIGNANT (et technopédagogue).
 *
 * @return array
 */
function theme_urdfs_nav_tree_teacher(): array {
    // Les cours ou l'utilisateur enseigne : ils alimentent les sous-menus
    // (Moodle n'a pas de page globale « tous mes devoirs / quiz / etudiants »,
    // on la reconstitue par cours).
    $courses = theme_urdfs_get_teacher_courses();

    $students = [];
    $assignments = [];
    $quizzes = [];
    $grades = [];
    foreach ($courses as $c) {
        $label = format_string($c->shortname ?: $c->fullname);
        $students[]    = ['label' => $label, 'url' => '/user/index.php?id=' . $c->id];
        $assignments[] = ['label' => $label, 'url' => '/mod/assign/index.php?id=' . $c->id];
        $quizzes[]     = ['label' => $label, 'url' => '/mod/quiz/index.php?id=' . $c->id];
        $grades[]      = ['label' => $label, 'url' => '/grade/report/grader/index.php?id=' . $c->id];
    }

    $tree = [
        ['group' => 'Pilotage', 'items' => [
            ['label' => 'Tableau de bord', 'icon' => 'gauge-high', 'url' => '/my/'],
            ['label' => 'Mes cours', 'icon' => 'book', 'url' => '/my/courses.php'],
        ]],
    ];

    $pedago = [];
    if (!empty($students)) {
        $pedago[] = ['label' => 'Étudiants', 'icon' => 'users',
                     'url' => '/user/index.php?id=' . $courses[0]->id, 'children' => $students];
        $pedago[] = ['label' => 'Devoirs', 'icon' => 'file-pen',
                     'url' => '/mod/assign/index.php?id=' . $courses[0]->id, 'children' => $assignments];
        $pedago[] = ['label' => 'Quiz', 'icon' => 'clipboard-question',
                     'url' => '/mod/quiz/index.php?id=' . $courses[0]->id, 'children' => $quizzes];
        $pedago[] = ['label' => 'Notes', 'icon' => 'chart-line',
                     'url' => '/grade/report/grader/index.php?id=' . $courses[0]->id, 'children' => $grades];
    }
    if ($pedago) {
        $tree[] = ['group' => 'Pédagogie', 'items' => $pedago];
    }

    $tree[] = ['group' => 'Organisation', 'items' => [
        ['label' => 'Calendrier', 'icon' => 'calendar-days', 'url' => '/calendar/view.php?view=month'],
        ['label' => 'Messages', 'icon' => 'comments', 'url' => '/message/index.php'],
    ]];

    return $tree;
}

/**
 * Cours dans lesquels l'utilisateur courant a un role d'enseignant.
 *
 * @return array liste d'objets cours (indexee numeriquement)
 */
function theme_urdfs_get_teacher_courses(): array {
    global $USER;

    $result = [];
    try {
        foreach (enrol_get_users_courses($USER->id, true, 'id, fullname, shortname') as $c) {
            if ($c->id == SITEID) {
                continue;
            }
            $ctx = context_course::instance($c->id);
            if (has_capability('moodle/course:manageactivities', $ctx)
                    || has_capability('mod/assign:grade', $ctx)) {
                $result[] = $c;
            }
        }
    } catch (Throwable $e) {
        return [];
    }
    return array_values($result);
}

/**
 * Arbre de navigation ÉTUDIANT.
 *
 * @return array
 */
function theme_urdfs_nav_tree_student(): array {
    return [
        ['group' => 'Apprentissage', 'items' => [
            ['label' => 'Tableau de bord', 'icon' => 'gauge-high', 'url' => '/my/'],
            ['label' => 'Mes cours', 'icon' => 'book', 'url' => '/my/courses.php'],
            ['label' => 'Calendrier', 'icon' => 'calendar-days', 'url' => '/calendar/view.php?view=month'],
            ['label' => 'Mes notes', 'icon' => 'chart-line', 'url' => '/grade/report/overview/index.php'],
        ]],
        ['group' => 'Communication', 'items' => [
            ['label' => 'Messages', 'icon' => 'comments', 'url' => '/message/index.php'],
        ]],
    ];
}
