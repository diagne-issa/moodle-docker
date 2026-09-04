#!/usr/bin/env bash
#
# Restauration de la plateforme LMS URDFS à partir d'une sauvegarde.
#
# ATTENTION : cette opération ÉCRASE la base et les fichiers actuels.
# Elle demande donc une confirmation explicite.
#
# Usage :
#   ./scripts/restauration.sh urdfs_db_2026-09-04_08-00-00.sql.gz \
#                             urdfs_moodledata_2026-09-04_08-00-00.tar.gz
#
# Les deux fichiers doivent porter le MÊME horodatage : la base et les
# fichiers forment un tout cohérent.

set -euo pipefail

BACKUP_DIR="${URDFS_BACKUP_DIR:-$HOME/sauvegardes-urdfs}"
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_DIR"

if [ $# -ne 2 ]; then
  echo "Usage : $0 <archive_base.sql.gz> <archive_moodledata.tar.gz>" >&2
  echo >&2
  echo "Sauvegardes disponibles dans $BACKUP_DIR :" >&2
  ls -1 "$BACKUP_DIR" 2>/dev/null | sed 's/^/  /' >&2 || echo "  (aucune)" >&2
  exit 1
fi

DB_ARCHIVE="$1"
DATA_ARCHIVE="$2"
[ -f "$DB_ARCHIVE" ]   || DB_ARCHIVE="$BACKUP_DIR/$1"
[ -f "$DATA_ARCHIVE" ] || DATA_ARCHIVE="$BACKUP_DIR/$2"

for f in "$DB_ARCHIVE" "$DATA_ARCHIVE"; do
  if [ ! -f "$f" ]; then
    echo "ERREUR : fichier introuvable : $f" >&2
    exit 1
  fi
done

# Tolérant aux fins de ligne Windows (CRLF) dans .env.
set -a
# shellcheck disable=SC1090
source <(sed 's/\r$//' .env)
set +a

echo "Vous allez ÉCRASER la plateforme actuelle avec :"
echo "  base       : $(basename "$DB_ARCHIVE")"
echo "  fichiers   : $(basename "$DATA_ARCHIVE")"
echo
read -r -p "Taper RESTAURER en majuscules pour confirmer : " reponse
if [ "$reponse" != "RESTAURER" ]; then
  echo "Annulé."
  exit 1
fi

VOLUME_NAME="$(basename "$PROJECT_DIR")_moodledata"

echo "[1/4] Arrêt des services applicatifs..."
docker compose stop moodle moodle-cron

echo "[2/4] Restauration de la base..."
gunzip -c "$DB_ARCHIVE" \
  | docker compose exec -T postgres psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" > /dev/null

echo "[3/4] Restauration des fichiers moodledata..."
docker run --rm \
  -v "${VOLUME_NAME}:/data" \
  -v "$(dirname "$DATA_ARCHIVE"):/sauvegarde:ro" \
  alpine:3 \
  sh -c "rm -rf /data/* /data/..?* 2>/dev/null; tar xzf /sauvegarde/$(basename "$DATA_ARCHIVE") -C /data"

echo "[4/4] Redémarrage et purge des caches..."
docker compose start moodle moodle-cron
sleep 8
docker compose exec -u www-data -w /var/www/html/public moodle \
  php ../admin/cli/purge_caches.php

echo
echo "Restauration terminée. Vérifiez la connexion sur $MOODLE_WWWROOT"
