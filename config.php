<?php  // Moodle configuration file - URDFS (persiste via bind-mount Docker)

// -----------------------------------------------------------------------------
// AUCUN SECRET DANS CE FICHIER.
//
// Les identifiants viennent des variables d'environnement passées aux
// conteneurs par docker-compose, qui les lit lui-même dans le fichier .env
// (non versionné, voir .gitignore). Ce fichier peut donc être partagé sur
// GitHub sans rien exposer.
//
// Pour installer le projet : copier .env.example vers .env et y renseigner
// ses propres valeurs.
// -----------------------------------------------------------------------------

unset($CFG);
global $CFG;
$CFG = new stdClass();

$CFG->dbtype    = getenv('MOODLE_DB_TYPE')     ?: 'pgsql';
$CFG->dblibrary = 'native';
$CFG->dbhost    = getenv('MOODLE_DB_HOST')     ?: 'postgres';
$CFG->dbname    = getenv('MOODLE_DB_NAME')     ?: 'moodle';
$CFG->dbuser    = getenv('MOODLE_DB_USER')     ?: 'moodle';
$CFG->dbpass    = getenv('MOODLE_DB_PASSWORD') ?: '';
$CFG->prefix    = 'mdl_';
$CFG->dboptions = array (
  'dbpersist' => 0,
  'dbport' => '',
  'dbsocket' => '',
  'dbcollation' => 'utf8mb4_unicode_ci',
);

$CFG->wwwroot   = getenv('MOODLE_WWWROOT')   ?: 'http://localhost:8090';
$CFG->dataroot  = getenv('MOODLE_DATA_ROOT') ?: '/var/www/moodledata';
$CFG->admin     = 'admin';

$CFG->directorypermissions = 0777;

require_once(__DIR__ . '/lib/setup.php');

// There is no php closing tag in this file,
// it is intentional because it prevents trailing whitespace problems!
