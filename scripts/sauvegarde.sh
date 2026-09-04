#!/usr/bin/env bash
#
# Sauvegarde complète de la plateforme LMS URDFS.
#
# Sauvegarde deux choses, qui sont indissociables :
#   1. la base PostgreSQL  : comptes, cours, notes, inscriptions, réglages
#   2. le volume moodledata : fichiers déposés, images, caches de session
#
# Restaurer l'une sans l'autre donne une plateforme incohérente : la base
# référencerait des fichiers absents, ou l'inverse. Les deux archives portent
# donc le MÊME horodatage, pour qu'on sache toujours lesquelles vont ensemble.
#
# Usage :
#   ./scripts/sauvegarde.sh
#
# Destination et rétention se règlent par variables d'environnement :
#   URDFS_BACKUP_DIR   (défaut : ~/sauvegardes-urdfs)
#   URDFS_BACKUP_KEEP  (défaut : 14 jours)

set -euo pipefail

# --- Réglages ---------------------------------------------------------------

BACKUP_DIR="${URDFS_BACKUP_DIR:-$HOME/sauvegardes-urdfs}"
KEEP_DAYS="${URDFS_BACKUP_KEEP:-14}"

# On se place dans le dossier du projet, quel que soit l'endroit d'où on
# lance le script.
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

STAMP="$(date +%Y-%m-%d_%H-%M-%S)"
DB_FILE="$BACKUP_DIR/urdfs_db_$STAMP.sql.gz"
DATA_FILE="$BACKUP_DIR/urdfs_moodledata_$STAMP.tar.gz"

# --- Vérifications ----------------------------------------------------------

if [ ! -f .env ]; then
  echo "ERREUR : fichier .env introuvable dans $PROJECT_DIR" >&2
  exit 1
fi

# On lit les identifiants dans .env, jamais en dur dans ce script.
# Le fichier peut avoir été enregistré sous Windows, avec des fins de ligne
# CRLF : on retire les retours chariot à la volée, sinon chaque valeur se
# termine par un \r invisible qui fait échouer les commandes Docker.
set -a
# shellcheck disable=SC1090
source <(sed 's/\r$//' .env)
set +a

if ! docker compose ps --status running --quiet postgres > /dev/null 2>&1; then
  echo "ERREUR : le conteneur postgres ne tourne pas. Lancez 'docker compose up -d'." >&2
  exit 1
fi

mkdir -p "$BACKUP_DIR"

echo "Sauvegarde vers $BACKUP_DIR"

# --- 1. Base de données -----------------------------------------------------

echo "  [1/3] Export de la base PostgreSQL..."
docker compose exec -T postgres \
  pg_dump -U "$POSTGRES_USER" -d "$POSTGRES_DB" --clean --if-exists \
  | gzip -9 > "$DB_FILE"

# --- 2. Fichiers moodledata -------------------------------------------------

# On passe par un conteneur jetable qui monte le volume : cela fonctionne même
# si le conteneur moodle est arrêté, et évite de dépendre de son contenu.
echo "  [2/3] Archivage du volume moodledata..."
VOLUME_NAME="$(docker compose config --volumes | grep -x 'moodledata' > /dev/null && echo "$(basename "$PROJECT_DIR")_moodledata" || echo "")"

docker run --rm \
  -v "${VOLUME_NAME}:/data:ro" \
  -v "$BACKUP_DIR:/sauvegarde" \
  alpine:3 \
  tar czf "/sauvegarde/$(basename "$DATA_FILE")" -C /data .

# --- 3. Rotation ------------------------------------------------------------

echo "  [3/3] Suppression des sauvegardes de plus de $KEEP_DAYS jours..."
find "$BACKUP_DIR" -name 'urdfs_db_*.sql.gz'          -mtime "+$KEEP_DAYS" -delete
find "$BACKUP_DIR" -name 'urdfs_moodledata_*.tar.gz'  -mtime "+$KEEP_DAYS" -delete

# --- Compte rendu -----------------------------------------------------------

echo
echo "Terminé."
ls -lh "$DB_FILE" "$DATA_FILE" | awk '{print "  " $9 "  " $5}'
echo
echo "Rappel : une sauvegarde qui n'a jamais été restaurée n'est pas une"
echo "sauvegarde. Testez la restauration au moins une fois."
