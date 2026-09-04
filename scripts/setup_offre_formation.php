<?php
/**
 * URDFS - Mise en place de TOUTE l'offre de formation.
 *
 * Cree, pour la promotion 2026-2027 :
 *   Domaine  ->  Formation  ->  Annee  ->  Niveau   (categories)
 *   + une cohorte (classe) par niveau
 *
 * Les COURS ne sont pas crees ici : ce sont les technopedagogues qui les
 * creeront dans la bonne categorie (c'est leur travail de rentree).
 *
 * Idempotent : relançable sans creer de doublon.
 *
 * Lancement (depuis moodle-docker/) :
 *   cat scripts/setup_offre_formation.php | docker compose exec -T -u www-data moodle php
 *
 * @package   local_urdfs
 * @copyright 2026 URDFS
 */

define('CLI_SCRIPT', true);
require('/var/www/html/config.php');
global $DB, $CFG;
require_once($CFG->dirroot . '/cohort/lib.php');

$annee     = '2026-2027';
$anneecode = '2026';

// =====================================================================
// OFFRE DE FORMATION (source : Presentation de l'offre de formation)
//   'code'    -> sert aux identifiants de categories et de cohortes
//   'niveaux' -> [] pour les certificats (session unique)
// =====================================================================
$offre = [
    'Industrie et innovation durable' => [
        'code' => 'IND',
        'formations' => [
            ['nom' => 'LP/BUT Génie industriel et maintenance (GIM)', 'code' => 'GIM', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'LP/BUT Génie mécanique et productique (GMP)', 'code' => 'GMP', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'LP/BUT Génie civil – construction durable (GC-CD)', 'code' => 'GCCD', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'Certificat Performance industrielle', 'code' => 'CPI', 'niveaux' => []],
            ['nom' => 'Certificat Maintenance', 'code' => 'CMA', 'niveaux' => []],
        ],
    ],
    'Numérique et intelligence artificielle' => [
        'code' => 'NUM',
        'formations' => [
            ['nom' => 'LP Data & IA', 'code' => 'DATAIA', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'LP/BUT Réseaux et télécommunications (R&T)', 'code' => 'RT', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'Certificat Biais cognitifs et sécurité numérique', 'code' => 'CBCSN', 'niveaux' => []],
            ['nom' => 'Certificat IA as a Tool', 'code' => 'CIAT', 'niveaux' => []],
            ['nom' => 'Certificat IA as a culture', 'code' => 'CIAC', 'niveaux' => []],
            ['nom' => 'Certificat Référent IA générative', 'code' => 'CRIAG', 'niveaux' => []],
        ],
    ],
    'Santé et souveraineté pharmaceutique' => [
        'code' => 'SAN',
        'formations' => [
            ['nom' => 'LP/BUT Génie Biologique – Biologie Médicale et Biotechnologie (GB-BMB)', 'code' => 'GBBMB', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'Master Innovation en soin et Recherche clinique', 'code' => 'MISRC', 'niveaux' => ['M1', 'M2']],
            ['nom' => 'Certificat Maintenance biomédicale', 'code' => 'CMB', 'niveaux' => []],
            ['nom' => 'Certificat Bioproduction', 'code' => 'CBP', 'niveaux' => []],
        ],
    ],
    'Souveraineté agroalimentaire et gestion des ressources' => [
        'code' => 'AGR',
        'formations' => [
            ['nom' => 'LP/BUT Génie Biologique – Sciences de l\'Aliment et Biotechnologie (GB-SAB)', 'code' => 'GBSAB', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'LP Gestion et valorisation des déchets', 'code' => 'GVD', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'Master Développement Agricole et Souveraineté Alimentaire (MDASA)', 'code' => 'MDASA', 'niveaux' => ['M1', 'M2']],
        ],
    ],
    'Services à haute valeur ajoutée' => [
        'code' => 'SER',
        'formations' => [
            ['nom' => 'LP/BUT Management de la logistique et des transports (MLT)', 'code' => 'MLT', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'LP/BUT Gestion administrative et commerciale des organisations (GACO)', 'code' => 'GACO', 'niveaux' => ['L1', 'L2', 'L3']],
            ['nom' => 'Certificat Intelligence Technologique (CIT)', 'code' => 'CIT', 'niveaux' => []],
        ],
    ],
];

/**
 * Cree une categorie si elle n'existe pas (identifiee par son idnumber).
 */
function urdfs_cat(string $name, int $parentid, string $idnumber): int {
    global $DB;
    if ($rec = $DB->get_record('course_categories', ['idnumber' => $idnumber])) {
        return (int) $rec->id;
    }
    if ($rec = $DB->get_record('course_categories', ['name' => $name, 'parent' => $parentid])) {
        return (int) $rec->id;
    }
    $cat = core_course_category::create([
        'name' => $name, 'parent' => $parentid, 'idnumber' => $idnumber, 'visible' => 1,
    ]);
    return (int) $cat->id;
}

/**
 * Cree une cohorte (classe) si elle n'existe pas.
 */
function urdfs_cohorte(string $name, string $idnumber): bool {
    global $DB;
    if ($DB->record_exists('cohort', ['idnumber' => $idnumber])) {
        return false;
    }
    cohort_add_cohort((object) [
        'name'        => $name,
        'idnumber'    => $idnumber,
        'contextid'   => context_system::instance()->id,
        'visible'     => 1,
        'description' => 'Classe / promotion ' . $name,
    ]);
    return true;
}

$nbcat = 0;
$nbcoh = 0;

foreach ($offre as $domaine => $d) {
    $domid = urdfs_cat($domaine, 0, 'DOM-' . $d['code']);
    echo "\n== $domaine\n";

    foreach ($d['formations'] as $f) {
        $formid  = urdfs_cat($f['nom'], $domid, 'FORM-' . $f['code']);
        $anneeid = urdfs_cat($annee, $formid, 'AN-' . $f['code'] . '-' . $anneecode);
        $nbcat += 2;
        echo "   - " . $f['nom'] . "\n";

        if (empty($f['niveaux'])) {
            // Certificat : pas de niveau, une session par an.
            $cohname = $f['nom'] . ' — ' . $annee;
            $cohidn  = 'CERT-' . $f['code'] . '-' . $anneecode;
            if (urdfs_cohorte($cohname, $cohidn)) {
                $nbcoh++;
                echo "        cohorte : $cohidn\n";
            }
            continue;
        }

        foreach ($f['niveaux'] as $niv) {
            urdfs_cat($niv, $anneeid, 'NIV-' . $f['code'] . '-' . $niv . '-' . $anneecode);
            $nbcat++;
            $cohname = $niv . ' ' . $f['code'] . ' — ' . $anneecode;
            $cohidn  = $niv . '-' . $f['code'] . '-' . $anneecode;
            if (urdfs_cohorte($cohname, $cohidn)) {
                $nbcoh++;
                echo "        $niv -> cohorte : $cohidn\n";
            }
        }
    }
}

purge_all_caches();

echo "\n=========================================\n";
echo "Termine. Categories traitees : $nbcat | Cohortes creees : $nbcoh\n";
echo "Les technopedagogues peuvent maintenant creer les cours dans\n";
echo "la categorie du niveau concerne, puis y brancher la cohorte.\n";
