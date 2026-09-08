#!/usr/bin/env bash
#
# Enveloppe de sauvegarde.sh destinee a cron.
#
# POURQUOI UN SCRIPT SEPARE PLUTOT QU'UNE LIGNE DE CRONTAB ?
# Cron n'execute pas un shell de connexion : il ne lit ni .bashrc ni
# .profile. Son PATH se limite generalement a /usr/bin:/bin, et la
# commande « docker » de Docker Desktop n'y figure pas. Une ligne de
# crontab appelant directement sauvegarde.sh echoue donc avec un
# laconique « docker: command not found », en silence, et on ne s'en
# apercoit que le jour ou l'on a besoin d'une sauvegarde.
#
# Ce script retablit un environnement complet, journalise tout, et
# signale les echecs de facon visible.
#
# Usage (cron) :
#   0 2 * * * /home/issa/projets/lms-plateform/moodle-docker/scripts/cron-sauvegarde.sh
#
# Il peut aussi se lancer a la main pour verifier qu'il fonctionne :
#   ./scripts/cron-sauvegarde.sh

set -uo pipefail

# --- Environnement ----------------------------------------------------------

# Chemins ou peut se trouver docker selon l'installation :
#   /usr/bin, /usr/local/bin  : Docker Engine installe dans la distribution
#   /mnt/wsl/docker-desktop/... : integration WSL de Docker Desktop
export PATH="/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/mnt/wsl/docker-desktop/cli-tools/usr/bin"

# sauvegarde.sh utilise $HOME pour le dossier de destination par defaut.
# Cron ne le definit pas toujours : on le fixe explicitement.
export HOME="${HOME:-/home/$(id -un)}"

PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="${URDFS_BACKUP_DIR:-$HOME/sauvegardes-urdfs}"
LOG_FILE="$BACKUP_DIR/sauvegarde.log"

mkdir -p "$BACKUP_DIR"

# --- Execution --------------------------------------------------------------

{
    echo "============================================================"
    echo "Sauvegarde automatique du $(date '+%Y-%m-%d a %H:%M:%S')"
    echo "============================================================"
} >> "$LOG_FILE"

if "$PROJECT_DIR/scripts/sauvegarde.sh" >> "$LOG_FILE" 2>&1; then
    echo "RESULTAT : succes" >> "$LOG_FILE"
    STATUS=0
else
    STATUS=$?
    echo "RESULTAT : ECHEC (code $STATUS)" >> "$LOG_FILE"
    # Trace visible hors du journal : si la sauvegarde echoue plusieurs
    # nuits de suite, ce fichier saute aux yeux dans le dossier.
    date '+%Y-%m-%d %H:%M:%S' > "$BACKUP_DIR/DERNIERE-SAUVEGARDE-EN-ECHEC.txt"
fi

echo >> "$LOG_FILE"

# --- Rotation du journal ----------------------------------------------------
# Sans cela, le fichier grossit indefiniment. On ne garde que les
# 2000 dernieres lignes, soit plusieurs mois d'historique.
if [ -f "$LOG_FILE" ] && [ "$(wc -l < "$LOG_FILE")" -gt 2000 ]; then
    tail -n 2000 "$LOG_FILE" > "$LOG_FILE.tmp" && mv "$LOG_FILE.tmp" "$LOG_FILE"
fi

exit "$STATUS"
