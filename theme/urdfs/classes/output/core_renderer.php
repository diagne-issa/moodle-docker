<?php
/**
 * Theme URDFS - core renderer override.
 *
 * Adds a role-based CSS class to <body> (urdfs-role-admin,
 * urdfs-role-teacher or urdfs-role-student) so the SCSS in
 * scss/_custom.scss can apply a distinct accent colour per space,
 * matching the 3 spaces (admin / enseignant / etudiant) of the
 * React frontend.
 *
 * @package    theme_urdfs
 * @copyright  2026 URDFS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_urdfs\output;

defined('MOODLE_INTERNAL') || die();

use context_system;
use context_course;

/**
 * Custom core renderer for theme_urdfs.
 */
class core_renderer extends \theme_boost\output\core_renderer {

    /**
     * Override body attributes to inject a role-based class.
     *
     * @param string|array $additionalclasses Additional classes to add.
     * @return string HTML attributes.
     */
    /**
     * Ajoute le chargement des polices URDFS (Plus Jakarta Sans + Inter) dans
     * le <head>. On passe par des <link> HTML et non par un @import SCSS, que
     * le compilateur scssphp de Moodle refuse pour les URL distantes.
     *
     * @return string HTML
     */
    public function standard_head_html() {
        $output = parent::standard_head_html();
        // Les polices (Plus Jakarta Sans + Inter) sont désormais AUTO-HÉBERGÉES
        // via @font-face dans le SCSS (theme/urdfs/fonts/). Plus de lien Google
        // Fonts : la plateforme ne dépend plus d'un CDN pour ses polices.
        // Le favicon est fourni par le thème (voir favicon() ci-dessous).
        // (Mode sombre désactivé pour le moment, on y reviendra.)
        return $output;
    }

    /**
     * Favicon URDFS servi par le THÈME plutôt que par le réglage
     * Apparence > Logos, qui vit en base de données et disparaît à la
     * moindre réinstallation. Le fichier attendu est :
     *
     *     theme/urdfs/pix/favicon.png   (ou .ico / .svg)
     *
     * Si le fichier est absent, on retombe sur le comportement de Moodle.
     *
     * @return \moodle_url
     */
    public function favicon() {
        global $CFG;

        // Le dossier pix/ du thème est sous la racine web : on peut donc
        // servir le fichier par son URL directe. On ne passe PAS par
        // image_url(), qui ne sait résoudre que svg, png, jpg et gif : un
        // favicon .ico, format le plus courant, y serait introuvable.
        $dir = $CFG->dirroot . '/theme/urdfs/pix/';

        // urdfs-icon.svg est l'icône déjà fournie avec le thème ; les noms
        // favicon.* permettent d'en déposer une autre sans toucher au code.
        foreach (['favicon.ico', 'favicon.png', 'favicon.svg', 'urdfs-icon.svg'] as $file) {
            if (file_exists($dir . $file)) {
                return new \moodle_url('/theme/urdfs/pix/' . $file);
            }
        }

        return parent::favicon();
    }

    public function body_attributes($additionalclasses = []) {
        if (!is_array($additionalclasses)) {
            $additionalclasses = explode(' ', $additionalclasses);
        }

        $additionalclasses[] = $this->get_urdfs_role_class();

        return parent::body_attributes($additionalclasses);
    }

    /**
     * Determine which "space" the current user belongs to and return
     * the matching body class.
     *
     * Priority:
     *  - Not logged in / guest    -> urdfs-role-guest
     *  - Site admin                -> urdfs-role-admin
     *  - Has a teaching capability  -> urdfs-role-teacher
     *  - Everyone else (logged in)  -> urdfs-role-student
     *
     * @return string
     */
    protected function get_urdfs_role_class(): string {
        global $USER, $COURSE, $PAGE;

        if (!isloggedin() || isguestuser()) {
            return 'urdfs-role-guest';
        }

        if (is_siteadmin($USER)) {
            return 'urdfs-role-admin';
        }

        // Le responsable pédagogique n'a AUCUNE capability d'enseignement
        // (il ne modifie ni les cours ni les notes) : sans ce test il
        // retombait sur l'espace étudiant. On le teste donc avant, par
        // son attribution de rôle et non par une capability.
        if (theme_urdfs_is_responsable()) {
            return 'urdfs-role-responsable';
        }

        // Use the current course context if we are inside a course,
        // otherwise fall back to the system context.
        $context = ($COURSE && $COURSE->id != SITEID)
            ? context_course::instance($COURSE->id)
            : context_system::instance();

        $teachercapabilities = [
            'moodle/course:update',
            'moodle/course:manageactivities',
            'mod/assign:grade',
        ];

        foreach ($teachercapabilities as $capability) {
            if (has_capability($capability, $context)) {
                return 'urdfs-role-teacher';
            }
        }

        return 'urdfs-role-student';
    }

    /**
     * Rendu de la sidebar sur-mesure URDFS (coquille d'administration).
     *
     * Génère une navigation groupée fixe à gauche à partir de
     * theme_urdfs_nav_tree(). Chaque entrée pointe vers une page Moodle
     * existante ; l'élément correspondant à la page courante est marqué actif
     * et sa section dépliée. Appelée depuis le template de layout via
     * {{{ output.urdfs_sidebar }}}.
     *
     * @return string HTML
     */
    public function urdfs_sidebar(): string {
        global $CFG, $PAGE;

        // Jamais de sidebar pour un visiteur non connecté (ou invité) :
        // il ne doit pas voir la navigation d'administration.
        if (!isloggedin() || isguestuser()) {
            return '';
        }

        // Navigation adaptée au rôle : administrateur -> coquille d'admin ;
        // enseignant -> espace enseignant ; sinon -> espace étudiant.
        if (is_siteadmin()) {
            $tree = theme_urdfs_nav_tree();
        } else if (theme_urdfs_is_technopedagogue()) {
            $tree = theme_urdfs_nav_tree_technopedagogue();
        } else if (theme_urdfs_is_responsable()) {
            $tree = theme_urdfs_nav_tree_responsable();
        } else if (theme_urdfs_is_teacher()) {
            $tree = theme_urdfs_nav_tree_teacher();
        } else {
            $tree = theme_urdfs_nav_tree_student();
        }
        $current = $PAGE->url ? $PAGE->url->out_as_local_url(false) : '';

        // Marque de la sidebar.
        // 1) Si un « logo compact » est configuré dans Moodle (Apparence > Logos),
        //    on affiche CE fichier (le vrai logo de l'université).
        // 2) Sinon seulement, on retombe sur le « C » vert SVG intégré.
        $logo = $this->get_compact_logo_url(200, 44);
        if (!empty($logo)) {
            $html  = '<aside class="urdfs-sidebar" id="urdfs-sidebar" aria-label="Navigation URDFS">';
            $html .= '<a href="' . $CFG->wwwroot . '/my/" class="urdfs-brand urdfs-brand--img">';
            $html .= '<img src="' . $logo . '" alt="URDFS">';
            $html .= '</a>';
        } else {
            $mark = '<span class="urdfs-brand-mark" aria-hidden="true">'
                . '<svg viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">'
                . '<g transform="translate(50 50) rotate(45)" fill="none" stroke="#067d3b" stroke-linecap="round">'
                . '<circle r="43" stroke-width="8" pathLength="100" stroke-dasharray="75 25"/>'
                . '<circle r="32" stroke-width="7" pathLength="100" stroke-dasharray="75 25"/>'
                . '<circle r="22" stroke-width="6" pathLength="100" stroke-dasharray="75 25"/>'
                . '<circle r="12" stroke-width="5" pathLength="100" stroke-dasharray="75 25"/>'
                . '</g></svg></span>';
            $html  = '<aside class="urdfs-sidebar" id="urdfs-sidebar" aria-label="Navigation URDFS">';
            $html .= '<a href="' . $CFG->wwwroot . '/my/" class="urdfs-brand">' . $mark;
            $html .= '<span class="urdfs-brand-text"><span class="urdfs-brand-name">Université Rose Dieng</span>';
            $html .= '<span class="urdfs-brand-sub">Espace Administrateur</span></span></a>';
        }
        // Recherche : filtre les entrées du menu (JS dans le layout).
        $html .= '<div class="urdfs-navsearch"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i>'
               . '<input type="search" id="urdfs-nav-search" placeholder="Rechercher un menu…" '
               . 'aria-label="Rechercher dans le menu" autocomplete="off"></div>';

        $html .= '<nav class="urdfs-nav">';

        foreach ($tree as $section) {
            $html .= '<div class="urdfs-group">';
            $html .= '<div class="urdfs-group-label">' . s($section['group']) . '</div>';

            foreach ($section['items'] as $item) {
                $children = $item['children'] ?? [];
                $itemurl  = $this->urdfs_url($item);

                // Un item est « courant » si sa page ou l'un de ses enfants
                // correspond au chemin de la page affichée.
                $isactive = $this->urdfs_match($item, $current);
                foreach ($children as $c) {
                    if ($this->urdfs_match($c, $current)) {
                        $isactive = true;
                    }
                }

                $haschildren = !empty($children);
                $classes = 'urdfs-item' . ($isactive ? ' active open' : '');
                $html .= '<div class="' . $classes . '">';

                if ($haschildren) {
                    // Parent avec sous-menu : le LIBELLE ouvre la page
                    // principale, le CHEVRON déplie/replie la section.
                    $html .= '<div class="urdfs-parent">';
                    $html .= '<a class="urdfs-link urdfs-parent-link" href="' . $itemurl . '">';
                    $html .= '<i class="fa-solid fa-' . s($item['icon']) . ' urdfs-ico" aria-hidden="true"></i>';
                    $html .= '<span class="urdfs-lbl">' . s($item['label']) . '</span>';
                    $html .= '</a>';
                    $html .= '<button type="button" class="urdfs-toggle urdfs-chevbtn" aria-expanded="'
                           . ($isactive ? 'true' : 'false') . '" aria-label="Déplier '
                           . s($item['label']) . '">';
                    $html .= '<i class="fa-solid fa-chevron-right urdfs-chev" aria-hidden="true"></i>';
                    $html .= '</button>';
                    $html .= '</div>';

                    $html .= '<div class="urdfs-subnav"><div class="urdfs-subnav-inner">';
                    foreach ($children as $c) {
                        $suburl = $this->urdfs_url($c);
                        $subcur = $this->urdfs_match($c, $current) ? ' current' : '';
                        $html .= '<a class="urdfs-sublink' . $subcur . '" href="' . $suburl . '"'
                               . (!empty($c['external']) ? ' target="_blank" rel="noopener"' : '') . '>'
                               . s($c['label']) . '</a>';
                    }
                    $html .= '</div></div>';
                } else {
                    // Élément simple : lien direct vers la page.
                    $html .= '<a class="urdfs-link" href="' . $itemurl . '"'
                           . (!empty($item['external']) ? ' target="_blank" rel="noopener"' : '') . '>';
                    $html .= '<i class="fa-solid fa-' . s($item['icon']) . ' urdfs-ico" aria-hidden="true"></i>';
                    $html .= '<span class="urdfs-lbl">' . s($item['label']) . '</span>';
                    $html .= '</a>';
                }

                $html .= '</div>'; // .urdfs-item
            }
            $html .= '</div>'; // .urdfs-group
        }

        $html .= '</nav>';

        // Pied : bouton de repli.
        $html .= '<div class="urdfs-sb-foot">';
        $html .= '<button type="button" class="urdfs-collapse" id="urdfs-collapse" aria-label="Réduire le menu">';
        $html .= '<i class="fa-solid fa-angles-left" aria-hidden="true"></i>';
        $html .= '<span class="urdfs-foot-text">Réduire le menu</span></button>';
        $html .= '</div>';

        $html .= '</aside>';
        $html .= '<div class="urdfs-scrim" id="urdfs-scrim"></div>';

        return $html;
    }

    /**
     * Bandeau « dashboard premium » URDFS : cartes KPI (données réelles) +
     * graphique des inscriptions + actions rapides. Rendu uniquement sur la
     * page d'accueil de l'administrateur ( /my/ ). Injecté dans le layout via
     * {{{ output.urdfs_dashboard_hero }}}.
     *
     * Robuste par construction : toute erreur d'accès aux données renvoie une
     * chaîne vide, la page n'est jamais cassée.
     *
     * @return string HTML
     */
    public function urdfs_dashboard_hero(): string {
        global $DB, $USER, $CFG, $PAGE;

        // Tableau de bord uniquement, pour un administrateur OU un
        // technopedagogue (c'est lui qui pilote la plateforme au quotidien).
        // Reserve a l'ADMINISTRATEUR : le technopedagogue a son propre
        // tableau de pilotage (urdfs_techno_hero).
        if ($PAGE->pagetype !== 'my-index' || $this->urdfs_is_mycourses_page()
                || !is_siteadmin()) {
            return '';
        }

        try {
            $now = time();

            $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
            $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

            $students = $studentroleid
                ? $DB->count_records_sql(
                    'SELECT COUNT(DISTINCT userid) FROM {role_assignments} WHERE roleid = ?', [$studentroleid])
                : 0;
            $teachers = $teacherroleid
                ? $DB->count_records_sql(
                    'SELECT COUNT(DISTINCT userid) FROM {role_assignments} WHERE roleid = ?', [$teacherroleid])
                : 0;

            $activecourses   = $DB->count_records_select('course', 'id > 1 AND visible = 1');
            $archivedcourses = $DB->count_records_select('course', 'id > 1 AND visible = 0');
            $online          = $DB->count_records_select('user', 'deleted = 0 AND lastaccess > ?', [$now - 300]);

            // Inscriptions (créations de comptes) sur les 6 derniers mois.
            $labels = [];
            $series = [];
            $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.',
                       'août', 'sept.', 'oct.', 'nov.', 'déc.'];
            $base = strtotime('first day of this month midnight');
            for ($i = 5; $i >= 0; $i--) {
                $start = strtotime("-$i month", $base);
                $end   = strtotime('+1 month', $start);
                $labels[] = $months[(int)date('n', $start) - 1];
                $series[] = (int)$DB->count_records_select(
                    'user', 'deleted = 0 AND timecreated >= ? AND timecreated < ?', [$start, $end]);
            }
        } catch (\Throwable $e) {
            return '';
        }

        $kpi = function ($value, $label, $icon, $variant) {
            return '<div class="urdfs-kpi">'
                . '<div class="urdfs-kpi-icon ' . $variant . '"><i class="fa-solid fa-' . $icon . '"></i></div>'
                . '<div class="urdfs-kpi-body"><div class="urdfs-kpi-value">' . number_format($value, 0, ',', ' ') . '</div>'
                . '<div class="urdfs-kpi-label">' . $label . '</div></div></div>';
        };

        $qa = function ($url, $label, $icon) use ($CFG) {
            return '<a class="urdfs-qa" href="' . $CFG->wwwroot . $url . '">'
                . '<span class="urdfs-qa-ic"><i class="fa-solid fa-' . $icon . '"></i></span>'
                . '<span class="urdfs-qa-lbl">' . $label . '</span>'
                . '<i class="fa-solid fa-arrow-right urdfs-qa-arw"></i></a>';
        };


        $h  = '<section class="urdfs-dash">';
        $h .= '<div class="urdfs-dash-head"><div>';
        $h .= '<h1 class="urdfs-dash-title">Bonjour, ' . s($USER->firstname) . '</h1>';
        $h .= '<p class="urdfs-dash-sub">Voici l\'état de la plateforme URDFS aujourd\'hui.</p>';
        $h .= '</div><span class="urdfs-badge-ok"><span class="urdfs-dot"></span>Système opérationnel</span></div>';

        $h .= '<div class="urdfs-kpis">';
        $h .= $kpi($students, 'Étudiants', 'users', 'is-green');
        $h .= $kpi($teachers, 'Enseignants', 'graduation-cap', 'is-green');
        $h .= $kpi($activecourses, 'Cours actifs', 'book-open', 'is-amber');
        $h .= $kpi($online, 'Connectés (5 min)', 'signal', 'is-green');
        $h .= '</div>';

        // Graphique en barres CSS pur — aucune dépendance externe (offline OK).
        $maxv = 1;
        foreach ($series as $v) { if ($v > $maxv) { $maxv = $v; } }
        $bars = '';
        $lastidx = count($series) - 1;
        foreach ($series as $i => $v) {
            $pct = (int) round(($v / $maxv) * 100);
            $barcls = ($i === $lastidx) ? 'urdfs-bar is-current' : 'urdfs-bar';
            $bars .= '<div class="urdfs-bar-col">'
                . '<div class="urdfs-bar-val">' . (int) $v . '</div>'
                . '<div class="urdfs-bar-track"><div class="' . $barcls . '" style="height:' . $pct . '%"></div></div>'
                . '<div class="urdfs-bar-label">' . s($labels[$i]) . '</div></div>';
        }

        $h .= '<div class="urdfs-dash-grid">';
        $h .= '<div class="card urdfs-chart-card"><div class="urdfs-card-head">'
            . '<div class="urdfs-card-title">Nouvelles inscriptions</div>'
            . '<div class="urdfs-card-sub">6 derniers mois</div></div>'
            . '<div class="urdfs-bars">' . $bars . '</div></div>';

        $h .= '<div class="card urdfs-actions"><div class="urdfs-card-head">'
            . '<div class="urdfs-card-title">Actions rapides</div></div><div class="urdfs-actions-list">';
        $h .= $qa('/user/editadvanced.php?id=-1', 'Créer un utilisateur', 'user-plus');
        $h .= $qa('/course/edit.php', 'Créer un cours', 'folder-plus');
        $h .= $qa('/admin/user.php', 'Gérer les utilisateurs', 'users-gear');
        $h .= $qa('/course/management.php', 'Gérer les cours', 'layer-group');
        $h .= $qa('/reportbuilder/index.php', 'Voir les rapports', 'chart-column');
        $h .= '</div></div>';
        $h .= '</div>'; // grid

        $h .= '</section>';

        return $h;
    }

    /**
     * Bandeau d'accueil ÉTUDIANT : bienvenue nommée, nombre de cours et
     * actions rapides. Affiché sur /my/ pour tout utilisateur connecté qui
     * n'est PAS administrateur. Robuste : renvoie '' en cas de souci.
     *
     * @return string HTML
     */
    public function urdfs_student_hero(): string {
        global $USER, $CFG, $PAGE, $DB;

        if ($PAGE->pagetype !== 'my-index' || $this->urdfs_is_mycourses_page()
                || !isloggedin() || isguestuser()
                || is_siteadmin() || theme_urdfs_is_technopedagogue()
                || theme_urdfs_is_responsable()
                || theme_urdfs_is_teacher()) {
            return '';
        }

        // --- Collecte de données (toujours défensive) --------------------
        $courses = [];
        $coursecount = 0;
        $badgecount = 0;
        $upcomingcount = 0;
        $events = [];
        try {
            $courses = enrol_get_users_courses($USER->id, true);
            $coursecount = count($courses);
        } catch (\Throwable $e) {
            $courses = [];
        }
        try {
            $badgecount = (int)$DB->count_records('badge_issued', ['userid' => $USER->id]);
        } catch (\Throwable $e) {
            $badgecount = 0;
        }
        if (!empty($courses)) {
            try {
                list($insql, $inparams) = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED, 'c');
                $params = $inparams + ['now' => time(), 'end' => time() + (30 * DAYSECS)];
                $where = "timestart >= :now AND timestart <= :end AND courseid $insql AND visible = 1";
                $upcomingcount = (int)$DB->count_records_select('event', $where, $params);
                $events = $DB->get_records_select('event', $where, $params, 'timestart ASC',
                    'id, name, timestart, courseid, modulename', 0, 5);
            } catch (\Throwable $e) {
                $events = [];
            }
        }

        $qa = function ($url, $label, $icon, $primary = false) use ($CFG) {
            $cls = $primary ? 'urdfs-wbtn urdfs-wbtn--solid' : 'urdfs-wbtn';
            return '<a class="' . $cls . '" href="' . $CFG->wwwroot . $url . '">'
                . '<i class="fa-solid fa-' . $icon . '"></i><span>' . $label . '</span></a>';
        };

        $hour = (int)date('G');
        $greeting = ($hour < 18) ? 'Bonjour' : 'Bonsoir';
        $sub = $coursecount > 0
            ? 'Vous suivez ' . $coursecount . ' cours. Reprenez là où vous vous êtes arrêté.'
            : 'Bienvenue sur votre espace d\'apprentissage URDFS.';

        // --- Bandeau de bienvenue ---------------------------------------
        $h  = '<section class="urdfs-welcome">';
        $h .= '<div class="urdfs-welcome-inner">';
        $h .= '<div class="urdfs-welcome-copy">';
        $h .= '<div class="urdfs-welcome-eyebrow">Espace étudiant</div>';
        $h .= '<h1 class="urdfs-welcome-title">' . $greeting . ', ' . s($USER->firstname) . '</h1>';
        $h .= '<p class="urdfs-welcome-sub">' . $sub . '</p>';
        $h .= '<div class="urdfs-welcome-actions">';
        $h .= $qa('/my/courses.php', 'Continuer un cours', 'play', true);
        $h .= $qa('/calendar/view.php', 'Calendrier', 'calendar-days');
        $h .= $qa('/grade/report/overview/index.php', 'Mes notes', 'chart-line');
        $h .= $qa('/message/index.php', 'Messages', 'comments');
        $h .= '</div></div>';
        $h .= '<div class="urdfs-welcome-badge"><i class="fa-solid fa-graduation-cap"></i></div>';
        $h .= '</div></section>';

        // --- Strip de statistiques + prochaines échéances ---------------
        $stat = function ($value, $label, $icon, $variant) {
            return '<div class="urdfs-sstat">'
                . '<div class="urdfs-sstat-ic ' . $variant . '"><i class="fa-solid fa-' . $icon . '"></i></div>'
                . '<div><div class="urdfs-sstat-val">' . (int)$value . '</div>'
                . '<div class="urdfs-sstat-lbl">' . $label . '</div></div></div>';
        };

        $h .= '<div class="urdfs-sgrid">';
        $h .= '<div class="urdfs-sstats">';
        $h .= $stat($coursecount, 'Cours suivis', 'graduation-cap', 'is-blue');
        $h .= $stat($upcomingcount, 'Échéances à venir', 'calendar-check', 'is-amber');
        $h .= $stat($badgecount, 'Badges obtenus', 'award', 'is-green');
        $h .= '</div>';

        // Carte « Prochaines échéances ».
        $modicons = ['assign' => 'file-pen', 'quiz' => 'clipboard-question', 'forum' => 'comments',
                     'lesson' => 'book-open', 'feedback' => 'square-poll-vertical', 'workshop' => 'users'];
        $h .= '<div class="urdfs-up card">';
        $h .= '<div class="urdfs-up-head"><span class="urdfs-card-title">Prochaines échéances</span>';
        $h .= '<a class="urdfs-up-all" href="' . $CFG->wwwroot . '/calendar/view.php?view=upcoming">Tout voir</a></div>';

        if (!empty($events)) {
            $h .= '<div class="urdfs-up-list">';
            foreach ($events as $e) {
                $icon = $modicons[$e->modulename] ?? 'calendar-day';
                $coursename = isset($courses[$e->courseid])
                    ? format_string($courses[$e->courseid]->shortname) : '';
                $when = userdate($e->timestart, '%d %b · %H:%M');
                $url = $CFG->wwwroot . '/calendar/view.php?view=day&time=' . (int)$e->timestart;
                $h .= '<a class="urdfs-up-item" href="' . $url . '">'
                    . '<span class="urdfs-up-ic"><i class="fa-solid fa-' . $icon . '"></i></span>'
                    . '<span class="urdfs-up-body"><span class="urdfs-up-name">' . format_string($e->name) . '</span>'
                    . '<span class="urdfs-up-meta">' . $coursename . '</span></span>'
                    . '<span class="urdfs-up-when">' . $when . '</span></a>';
            }
            $h .= '</div>';
        } else {
            $h .= '<div class="urdfs-up-empty"><i class="fa-solid fa-circle-check"></i>'
                . '<span>Aucune échéance à venir. Vous êtes à jour.</span></div>';
        }
        $h .= '</div>'; // .urdfs-up
        $h .= '</div>'; // .urdfs-sgrid

        return $h;
    }

    /**
     * Bandeau d'accueil ENSEIGNANT : bienvenue, statistiques (cours, étudiants,
     * échéances) et actions rapides. Affiché sur /my/ pour un enseignant.
     * Robuste : toute erreur d'accès aux données renvoie '' pour la section.
     *
     * @return string HTML
     */
    public function urdfs_teacher_hero(): string {
        global $USER, $CFG, $PAGE, $DB;

        if ($PAGE->pagetype !== 'my-index' || $this->urdfs_is_mycourses_page()
                || !isloggedin() || isguestuser()
                || is_siteadmin() || theme_urdfs_is_technopedagogue()
                || !theme_urdfs_is_teacher()) {
            return '';
        }

        $courses = [];
        $coursecount = 0;
        $studentcount = 0;
        $upcomingcount = 0;
        $events = [];
        try {
            $courses = enrol_get_users_courses($USER->id, true);
            $coursecount = count($courses);
        } catch (\Throwable $e) {
            $courses = [];
        }
        try {
            foreach ($courses as $c) {
                $studentcount += (int)count_enrolled_users(\context_course::instance($c->id));
            }
        } catch (\Throwable $e) {
            $studentcount = 0;
        }
        if (!empty($courses)) {
            try {
                list($insql, $inparams) = $DB->get_in_or_equal(array_keys($courses), SQL_PARAMS_NAMED, 'c');
                $params = $inparams + ['now' => time(), 'end' => time() + (30 * DAYSECS)];
                $where = "timestart >= :now AND timestart <= :end AND courseid $insql AND visible = 1";
                $upcomingcount = (int)$DB->count_records_select('event', $where, $params);
                $events = $DB->get_records_select('event', $where, $params, 'timestart ASC',
                    'id, name, timestart, courseid, modulename', 0, 5);
            } catch (\Throwable $e) {
                $events = [];
            }
        }

        $qa = function ($url, $label, $icon, $primary = false) use ($CFG) {
            $cls = $primary ? 'urdfs-wbtn urdfs-wbtn--solid' : 'urdfs-wbtn';
            return '<a class="' . $cls . '" href="' . $CFG->wwwroot . $url . '">'
                . '<i class="fa-solid fa-' . $icon . '"></i><span>' . $label . '</span></a>';
        };
        $stat = function ($value, $label, $icon, $variant) {
            return '<div class="urdfs-sstat">'
                . '<div class="urdfs-sstat-ic ' . $variant . '"><i class="fa-solid fa-' . $icon . '"></i></div>'
                . '<div><div class="urdfs-sstat-val">' . (int)$value . '</div>'
                . '<div class="urdfs-sstat-lbl">' . $label . '</div></div></div>';
        };

        $hour = (int)date('G');
        $greeting = ($hour < 18) ? 'Bonjour' : 'Bonsoir';

        // --- Travaux a corriger (donnees reelles) -----------------------
        $tograde = [];
        $togradecount = 0;
        try {
            foreach ($courses as $c) {
                if ($c->id == SITEID) {
                    continue;
                }
                $cctx = \context_course::instance($c->id);
                if (!has_capability('mod/assign:grade', $cctx)) {
                    continue;
                }
                $modinfo = get_fast_modinfo($c->id);
                foreach ($modinfo->get_instances_of('assign') as $cm) {
                    if (!$cm->uservisible) {
                        continue;
                    }
                    $assign = new \assign($cm->context, $cm, $c);
                    $need = (int) $assign->count_submissions_need_grading();
                    if ($need > 0) {
                        $togradecount += $need;
                        $tograde[] = [
                            'name'   => format_string($cm->name),
                            'course' => format_string($c->shortname ?: $c->fullname),
                            'count'  => $need,
                            'url'    => $CFG->wwwroot . '/mod/assign/view.php?id=' . $cm->id . '&action=grading',
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            $tograde = [];
        }

        $h  = '<section class="urdfs-welcome urdfs-welcome--teacher">';
        $h .= '<div class="urdfs-welcome-inner"><div class="urdfs-welcome-copy">';
        $h .= '<div class="urdfs-welcome-eyebrow">Espace enseignant</div>';
        $h .= '<h1 class="urdfs-welcome-title">' . $greeting . ', ' . s($USER->firstname) . '</h1>';
        $h .= '<p class="urdfs-welcome-sub">'
            . ($togradecount > 0
                ? 'Vous avez <strong>' . $togradecount . '</strong> travail(aux) en attente de correction.'
                : 'Aucune correction en attente. Vos cours sont à jour.')
            . '</p>';
        $h .= '<div class="urdfs-welcome-actions">';
        $h .= $qa('/my/courses.php', 'Mes cours', 'graduation-cap', true);
        if (!empty($courses)) {
            $first = reset($courses);
            $h .= $qa('/user/index.php?id=' . $first->id, 'Mes étudiants', 'users');
            $h .= $qa('/grade/report/grader/index.php?id=' . $first->id, 'Carnet de notes', 'table-list');
        }
        $h .= $qa('/calendar/view.php?view=month', 'Calendrier', 'calendar-days');
        $h .= '</div></div>';
        $h .= '<div class="urdfs-welcome-badge"><i class="fa-solid fa-chalkboard-user"></i></div>';
        $h .= '</div></section>';

        $h .= '<div class="urdfs-sgrid"><div class="urdfs-sstats urdfs-sstats--teacher">';
        $h .= $stat($coursecount, 'Cours', 'graduation-cap', 'is-blue');
        $h .= $stat($studentcount, 'Étudiants', 'users', 'is-green');
        $h .= $stat($togradecount, 'À corriger', 'file-pen', 'is-amber');
        $h .= $stat($upcomingcount, 'Échéances', 'calendar-check', 'is-blue');
        $h .= '</div>';

        // --- Panneau « Travaux a corriger » ------------------------------
        $h .= '<div class="urdfs-tograde card"><div class="urdfs-up-head">'
            . '<span class="urdfs-card-title">Travaux à corriger</span></div>';
        if (!empty($tograde)) {
            $h .= '<div class="urdfs-up-list">';
            foreach (array_slice($tograde, 0, 6) as $t) {
                $h .= '<a class="urdfs-up-item" href="' . $t['url'] . '">'
                    . '<span class="urdfs-up-ic"><i class="fa-solid fa-file-pen"></i></span>'
                    . '<span class="urdfs-up-body"><span class="urdfs-up-name">' . $t['name'] . '</span>'
                    . '<span class="urdfs-up-meta">' . $t['course'] . '</span></span>'
                    . '<span class="urdfs-grade-badge">' . $t['count'] . '</span></a>';
            }
            $h .= '</div>';
        } else {
            $h .= '<div class="urdfs-up-empty"><i class="fa-solid fa-circle-check"></i>'
                . '<span>Rien à corriger pour le moment.</span></div>';
        }
        $h .= '</div>';

        $modicons = ['assign' => 'file-pen', 'quiz' => 'clipboard-question', 'forum' => 'comments',
                     'lesson' => 'book-open', 'feedback' => 'square-poll-vertical', 'workshop' => 'users'];
        $h .= '<div class="urdfs-up card"><div class="urdfs-up-head">'
            . '<span class="urdfs-card-title">Prochaines échéances</span>'
            . '<a class="urdfs-up-all" href="' . $CFG->wwwroot . '/calendar/view.php?view=upcoming">Tout voir</a></div>';
        if (!empty($events)) {
            $h .= '<div class="urdfs-up-list">';
            foreach ($events as $e) {
                $icon = $modicons[$e->modulename] ?? 'calendar-day';
                $coursename = isset($courses[$e->courseid]) ? format_string($courses[$e->courseid]->shortname) : '';
                $when = userdate($e->timestart, '%d %b · %H:%M');
                $url = $CFG->wwwroot . '/calendar/view.php?view=day&time=' . (int)$e->timestart;
                $h .= '<a class="urdfs-up-item" href="' . $url . '">'
                    . '<span class="urdfs-up-ic"><i class="fa-solid fa-' . $icon . '"></i></span>'
                    . '<span class="urdfs-up-body"><span class="urdfs-up-name">' . format_string($e->name) . '</span>'
                    . '<span class="urdfs-up-meta">' . $coursename . '</span></span>'
                    . '<span class="urdfs-up-when">' . $when . '</span></a>';
            }
            $h .= '</div>';
        } else {
            $h .= '<div class="urdfs-up-empty"><i class="fa-solid fa-circle-check"></i>'
                . '<span>Aucune échéance à venir dans vos cours.</span></div>';
        }
        $h .= '</div></div>';

        // Note : la grille « Mes cours » a ete retiree du tableau de bord
        // (elle faisait doublon avec la page « Mes cours » et la sidebar).

        return $h;
    }

    /**
     * Construit l'URL absolue d'une entrée de navigation.
     *
     * @param array $item
     * @return string
     */
    /**
     * Tableau de PILOTAGE du technopedagogue.
     *
     * Vue globale de la plateforme pedagogique : indicateurs, derniers cours
     * crees, dernieres inscriptions, cours a verifier. Distinct de l'espace
     * enseignant (qui gere SES cours) et de l'espace admin (technique).
     *
     * @return string HTML
     */
    public function urdfs_techno_hero(): string {
        global $DB, $USER, $CFG, $PAGE;

        if ($PAGE->pagetype !== 'my-index' || $this->urdfs_is_mycourses_page()
                || is_siteadmin() || !theme_urdfs_is_technopedagogue()) {
            return '';
        }

        try {
            $studentroleid = $DB->get_field('role', 'id', ['shortname' => 'student']);
            $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);

            $students = $studentroleid ? (int) $DB->count_records_sql(
                'SELECT COUNT(DISTINCT userid) FROM {role_assignments} WHERE roleid = ?', [$studentroleid]) : 0;
            $teachers = $teacherroleid ? (int) $DB->count_records_sql(
                'SELECT COUNT(DISTINCT userid) FROM {role_assignments} WHERE roleid = ?', [$teacherroleid]) : 0;

            $courses       = (int) $DB->count_records_select('course', 'id > 1');
            $activecourses = (int) $DB->count_records_select('course', 'id > 1 AND visible = 1');
            $categories    = (int) $DB->count_records('course_categories');

            // Inscriptions aux cours sur les 6 derniers mois (graphique).
            $labels = [];
            $series = [];
            $months = ['janv.', 'févr.', 'mars', 'avr.', 'mai', 'juin', 'juil.',
                       'août', 'sept.', 'oct.', 'nov.', 'déc.'];
            $base = strtotime('first day of this month midnight');
            for ($i = 5; $i >= 0; $i--) {
                $start = strtotime("-$i month", $base);
                $end   = strtotime('+1 month', $start);
                $labels[] = $months[(int) date('n', $start) - 1];
                $series[] = (int) $DB->count_records_select('user_enrolments',
                    'timecreated >= ? AND timecreated < ?', [$start, $end]);
            }
        } catch (\Throwable $e) {
            return '';
        }

        $hour = (int) date('G');
        $greeting = ($hour < 18) ? 'Bonjour' : 'Bonsoir';

        $kpi = function ($value, $label, $icon, $variant) {
            return '<div class="urdfs-kpi">'
                . '<div class="urdfs-kpi-icon ' . $variant . '"><i class="fa-solid fa-' . $icon . '"></i></div>'
                . '<div class="urdfs-kpi-body"><div class="urdfs-kpi-value">' . (int) $value . '</div>'
                . '<div class="urdfs-kpi-label">' . $label . '</div></div></div>';
        };
        $qa = function ($url, $label, $icon, $primary = false) use ($CFG) {
            $cls = $primary ? 'urdfs-wbtn urdfs-wbtn--solid' : 'urdfs-wbtn';
            return '<a class="' . $cls . '" href="' . $CFG->wwwroot . $url . '">'
                . '<i class="fa-solid fa-' . $icon . '"></i><span>' . $label . '</span></a>';
        };

        $h  = '<section class="urdfs-welcome urdfs-welcome--techno">';
        $h .= '<div class="urdfs-welcome-inner"><div class="urdfs-welcome-copy">';
        $h .= '<div class="urdfs-welcome-eyebrow">Pilotage pédagogique</div>';
        $h .= '<h1 class="urdfs-welcome-title">' . $greeting . ', ' . s($USER->firstname) . '</h1>';
        $h .= '<p class="urdfs-welcome-sub">Vue d\'ensemble de la plateforme : cours, enseignants, étudiants et suivi.</p>';
        $h .= '<div class="urdfs-welcome-actions">';
        $h .= $qa('/course/management.php', 'Gérer les cours', 'layer-group', true);
        $h .= $qa('/course/edit.php', 'Créer un cours', 'circle-plus');
        $h .= $qa('/cohort/index.php', 'Classes', 'user-group');
        $h .= $qa('/admin/user.php', 'Utilisateurs', 'users');
        $h .= '</div></div>';
        $h .= '<div class="urdfs-welcome-badge"><i class="fa-solid fa-compass-drafting"></i></div>';
        $h .= '</div></section>';

        $h .= '<section class="urdfs-kpi-grid">';
        $h .= $kpi($courses, 'Cours', 'book', 'is-blue');
        $h .= $kpi($activecourses, 'Cours actifs', 'circle-check', 'is-green');
        $h .= $kpi($teachers, 'Enseignants', 'chalkboard-user', 'is-blue');
        $h .= $kpi($students, 'Étudiants', 'users', 'is-green');
        $h .= '</section>';

        // --- Graphique : inscriptions des 6 derniers mois ----------------
        // Barres en CSS pur : aucune dependance externe, fonctionne hors ligne.
        $maxv = 1;
        foreach ($series as $v) {
            if ($v > $maxv) {
                $maxv = $v;
            }
        }
        $bars = '';
        $lastidx = count($series) - 1;
        foreach ($series as $i => $v) {
            $pct = (int) round(($v / $maxv) * 100);
            $cls = ($i === $lastidx) ? 'urdfs-bar is-current' : 'urdfs-bar';
            $bars .= '<div class="urdfs-bar-col">'
                . '<div class="urdfs-bar-val">' . (int) $v . '</div>'
                . '<div class="urdfs-bar-track"><div class="' . $cls . '" style="height:' . $pct . '%"></div></div>'
                . '<div class="urdfs-bar-label">' . s($labels[$i]) . '</div></div>';
        }

        // Lien d'action rapide (style « liste » du tableau de bord admin).
        $qalink = function ($url, $label, $icon) use ($CFG) {
            return '<a class="urdfs-qa" href="' . $CFG->wwwroot . $url . '">'
                . '<span class="urdfs-qa-ic"><i class="fa-solid fa-' . $icon . '"></i></span>'
                . '<span class="urdfs-qa-lbl">' . $label . '</span>'
                . '<i class="fa-solid fa-arrow-right urdfs-qa-arw"></i></a>';
        };

        // Graphique + actions rapides, cote a cote.
        $h .= '<div class="urdfs-dash-grid urdfs-techno-dash">';

        $h .= '<div class="card urdfs-chart-card">'
            . '<div class="urdfs-card-head">'
            . '<div class="urdfs-card-title">Inscriptions aux cours</div>'
            . '<div class="urdfs-card-sub">6 derniers mois</div></div>'
            . '<div class="urdfs-bars">' . $bars . '</div></div>';

        $h .= '<div class="card urdfs-actions">'
            . '<div class="urdfs-card-head"><div class="urdfs-card-title">Actions rapides</div></div>'
            . '<div class="urdfs-actions-list">';
        $h .= $qalink('/course/edit.php', 'Créer un cours', 'circle-plus');
        $h .= $qalink('/course/editcategory.php?parent=0', 'Créer une catégorie', 'folder-plus');
        $h .= $qalink('/cohort/edit.php?contextid=1', 'Créer une classe', 'user-group');
        $h .= $qalink('/user/editadvanced.php?id=-1', 'Ajouter un utilisateur', 'user-plus');
        $h .= $qalink('/course/management.php', 'Gérer les cours', 'layer-group');
        $h .= $qalink('/report/log/index.php', 'Voir les rapports', 'chart-column');
        $h .= '</div></div>';

        $h .= '</div>';

        return $h;
    }

    /**
     * Tableau de bord du RESPONSABLE PEDAGOGIQUE.
     *
     * Il suit une ou plusieurs formations : on lui montre son perimetre
     * (formations, cours, etudiants) et un acces direct a chacune de ses
     * formations. Aucune action de creation : ce role observe et rapporte.
     *
     * @return string HTML
     */
    public function urdfs_responsable_hero(): string {
        global $DB, $USER, $CFG, $PAGE;

        if ($PAGE->pagetype !== 'my-index' || $this->urdfs_is_mycourses_page()
                || is_siteadmin() || theme_urdfs_is_technopedagogue()
                || !theme_urdfs_is_responsable()) {
            return '';
        }

        $cats = theme_urdfs_get_responsable_categories();
        $catids = theme_urdfs_get_responsable_category_ids();

        $courses = 0;
        $students = 0;
        try {
            if (!empty($catids)) {
                list($insql, $params) = $DB->get_in_or_equal($catids, SQL_PARAMS_NAMED, 'cat');
                $courses = (int) $DB->count_records_select('course', "category $insql", $params);
                $students = (int) $DB->count_records_sql(
                    "SELECT COUNT(DISTINCT ue.userid)
                       FROM {user_enrolments} ue
                       JOIN {enrol} e ON e.id = ue.enrolid
                       JOIN {course} c ON c.id = e.courseid
                      WHERE c.category $insql", $params);
            }
        } catch (\Throwable $e) {
            $courses = 0;
            $students = 0;
        }

        $hour = (int) date('G');
        $greeting = ($hour < 18) ? 'Bonjour' : 'Bonsoir';

        $kpi = function ($value, $label, $icon, $variant) {
            return '<div class="urdfs-kpi">'
                . '<div class="urdfs-kpi-icon ' . $variant . '"><i class="fa-solid fa-' . $icon . '"></i></div>'
                . '<div class="urdfs-kpi-body"><div class="urdfs-kpi-value">' . (int) $value . '</div>'
                . '<div class="urdfs-kpi-label">' . $label . '</div></div></div>';
        };
        $qa = function ($url, $label, $icon, $primary = false) use ($CFG) {
            $cls = $primary ? 'urdfs-wbtn urdfs-wbtn--solid' : 'urdfs-wbtn';
            return '<a class="' . $cls . '" href="' . $CFG->wwwroot . $url . '">'
                . '<i class="fa-solid fa-' . $icon . '"></i><span>' . $label . '</span></a>';
        };

        $firsturl = !empty($cats)
            ? '/course/index.php?categoryid=' . $cats[0]->id
            : '/course/index.php';

        $sub = !empty($cats)
            ? 'Suivi de ' . count($cats) . ' formation' . (count($cats) > 1 ? 's' : '')
              . ' : progression, assiduité et résultats.'
            : 'Aucune formation ne vous est encore attribuée. Contactez le technopédagogue.';

        $h  = '<section class="urdfs-welcome urdfs-welcome--responsable">';
        $h .= '<div class="urdfs-welcome-inner"><div class="urdfs-welcome-copy">';
        $h .= '<div class="urdfs-welcome-eyebrow">Responsable pédagogique</div>';
        $h .= '<h1 class="urdfs-welcome-title">' . $greeting . ', ' . s($USER->firstname) . '</h1>';
        $h .= '<p class="urdfs-welcome-sub">' . $sub . '</p>';
        $h .= '<div class="urdfs-welcome-actions">';
        $h .= $qa($firsturl, 'Mes formations', 'folder-tree', true);
        $h .= $qa('/calendar/view.php?view=month', 'Calendrier', 'calendar-days');
        $h .= $qa('/message/index.php', 'Messages', 'comments');
        $h .= '</div></div>';
        $h .= '<div class="urdfs-welcome-badge"><i class="fa-solid fa-clipboard-check"></i></div>';
        $h .= '</div></section>';

        $h .= '<section class="urdfs-kpi-grid">';
        $h .= $kpi(count($cats), 'Formations suivies', 'folder-tree', 'is-blue');
        $h .= $kpi($courses, 'Cours du périmètre', 'book', 'is-blue');
        $h .= $kpi($students, 'Étudiants', 'users', 'is-green');
        $h .= '</section>';

        // Acces direct a chaque formation confiee.
        if (!empty($cats)) {
            $h .= '<div class="card urdfs-actions">'
                . '<div class="urdfs-card-head"><div class="urdfs-card-title">Mes formations</div>'
                . '<div class="urdfs-card-sub">Accès direct à chaque périmètre</div></div>'
                . '<div class="urdfs-actions-list">';
            foreach ($cats as $cat) {
                $h .= '<a class="urdfs-qa" href="' . $CFG->wwwroot
                    . '/course/index.php?categoryid=' . (int) $cat->id . '">'
                    . '<span class="urdfs-qa-ic"><i class="fa-solid fa-folder-open"></i></span>'
                    . '<span class="urdfs-qa-lbl">' . format_string($cat->name) . '</span>'
                    . '<i class="fa-solid fa-arrow-right urdfs-qa-arw"></i></a>';
            }
            $h .= '</div></div>';
        }

        return $h;
    }

    /**
     * Sommes-nous sur la page « Mes cours » (/my/courses.php) ?
     *
     * Moodle donne le meme pagetype ('my-index') au tableau de bord et a
     * « Mes cours » : on distingue par l'URL pour ne pas repeter le bandeau.
     *
     * @return bool
     */
    protected function urdfs_is_mycourses_page(): bool {
        global $PAGE;
        $path = $PAGE->url ? $PAGE->url->get_path() : '';
        return strpos($path, '/my/courses.php') !== false;
    }

    protected function urdfs_url(array $item): string {
        global $CFG;
        $url = $item['url'] ?? '#';
        if (!empty($item['external'])) {
            return $url;
        }
        return $CFG->wwwroot . $url;
    }

    /**
     * Indique si une entrée correspond au chemin de la page courante.
     *
     * @param array $item
     * @param string $currentfull URL locale complète de la page courante.
     * @return bool
     */
    protected function urdfs_match(array $item, string $currentfull): bool {
        if (empty($item['url']) || !empty($item['external']) || $currentfull === '') {
            return false;
        }
        $itempath = parse_url($item['url'], PHP_URL_PATH) ?? '';
        $curpath  = parse_url($currentfull, PHP_URL_PATH) ?? '';
        if ($itempath === '' || $itempath !== $curpath) {
            return false;
        }
        // Si l'entrée précise une section/catégorie/type, on l'exige aussi, afin
        // de distinguer p.ex. /admin/settings.php?section=manageenrols de
        // ?section=manageauths : ainsi la bonne entrée est surlignée, et sa
        // section reste dépliée — l'utilisateur sait toujours où il est.
        parse_str((string)(parse_url($item['url'], PHP_URL_QUERY) ?? ''), $iq);
        parse_str((string)(parse_url($currentfull, PHP_URL_QUERY) ?? ''), $cq);
        foreach (['section', 'category', 'type'] as $k) {
            if (isset($iq[$k]) && (!isset($cq[$k]) || (string)$cq[$k] !== (string)$iq[$k])) {
                return false;
            }
        }
        return true;
    }
}
