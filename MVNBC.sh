#!/bin/bash
umask 002

# ==================================================
# CRON-PROOF BASE PATH (NO PWD DEPENDENCY)
# ==================================================
SCRIPT_PATH="$(readlink -f "${BASH_SOURCE[0]}")"
SCRIPT_DIR="$(dirname "$SCRIPT_PATH")"

BASE_DIR="$SCRIPT_DIR"

CONFIG_FILE="$BASE_DIR/config/devices.conf"
BACKUP_DIR="$BASE_DIR/backups"
TEMPLATE_DIR="$BASE_DIR/templates"
LOG_DIR="$BASE_DIR/logs"

mkdir -p "$BACKUP_DIR" "$LOG_DIR"

# ==================================================
# GLOBAL RUN LOG (CRON SAFE)
# ==================================================
RUN_ID=$(date +%Y%m%d_%H%M%S)
RUN_LOG="$LOG_DIR/run_${RUN_ID}.log"

exec > >(tee -a "$RUN_LOG") 2>&1

echo "======================================"
echo " Multi-Vendor Auto Backup Engine"
echo "======================================"
echo "[INFO] RUN ID: $RUN_ID"
echo "[INFO] BASE DIR: $BASE_DIR"
echo "[INFO] STARTED"
echo ""

# ==================================================
# BACKUP FUNCTION
# ==================================================
run_backup() {

    if [[ ! -f "$CONFIG_FILE" ]]; then
        echo "[FATAL] devices.conf not found: $CONFIG_FILE"
        exit 1
    fi

    while IFS=',' read -r vendor ip username password enable_secret; do

        [[ -z "$vendor" || "$vendor" == \#* ]] && continue

        echo "[INFO] Processing $vendor $ip"

        # Vendor folder
        TARGET_DIR="$BACKUP_DIR/$vendor"
        mkdir -p "$TARGET_DIR"

        # Device timestamp
        TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

        # Backup file
        BACKUP_FILE="${ip}_${TIMESTAMP}.cfg"
        BACKUP_PATH="$TARGET_DIR/$BACKUP_FILE"

        # Log file per device
        LOG_FILE="$LOG_DIR/${vendor}_${ip}_${TIMESTAMP}.log"

        case "$vendor" in

            cisco)
                expect "$TEMPLATE_DIR/cisco.exp" \
                    "$ip" "$username" "$password" "$enable_secret" \
                    "$BACKUP_PATH" >> "$LOG_FILE" 2>&1
                ;;
            ciscoasa)
                expect "$TEMPLATE_DIR/ciscoasa.exp" \
                    "$ip" "$username" "$password" "$enable_secret" \
                    "$BACKUP_PATH" >> "$LOG_FILE" 2>&1
                ;;
            h3c)
                expect "$TEMPLATE_DIR/h3c.exp" \
                    "$ip" "$username" "$password" \
                    "$BACKUP_PATH" >> "$LOG_FILE" 2>&1
                ;;

            arista)
                expect "$TEMPLATE_DIR/arista.exp" \
                    "$ip" "$username" "$password" "$enable_secret" \
                    "$BACKUP_PATH" >> "$LOG_FILE" 2>&1
                ;;

            mikrotik)
				expect "$TEMPLATE_DIR/mikrotik.exp" \
					"$ip" "$username" "$password" \
					"$BACKUP_PATH" >> "$LOG_FILE" 2>&1

				# Cleanup MikroTik terminal noise
					if [[ -f "$BACKUP_PATH" ]]; then

				# Remove SSH spawn line
					sed -i '/^spawn ssh /d' "$BACKUP_PATH"

				# Remove password prompt
					sed -i "/'s password:/d" "$BACKUP_PATH"

				# Remove export command echoes
					sed -i '/\/export show-sensitive terse/d' "$BACKUP_PATH"

				# Remove prompt-only lines
					sed -i '/^\[.*@MikroTik\].*>$/d' "$BACKUP_PATH"

					fi
				;;
            aruba)
                expect "$TEMPLATE_DIR/arubacx.exp" \
                    "$ip" "$username" "$password" \
                    "$BACKUP_PATH" >> "$LOG_FILE" 2>&1
                ;;
            *)
                echo "[WARN] Unknown vendor: $vendor"
                continue
                ;;
        esac

        # Verify
        if [[ "$vendor" == "mikrotik" ]]; then

		if grep -q "^#.*RouterOS" "$BACKUP_PATH" 2>/dev/null; then
			echo "[OK] Backup saved: $BACKUP_PATH"
		else
			echo "[FAIL] MikroTik export failed: $ip"
		fi

		else

			if [[ -s "$BACKUP_PATH" ]]; then
				echo "[OK] Backup saved: $BACKUP_PATH"
			else
				echo "[FAIL] Backup empty/missing: $ip"
			fi

		fi

    done < "$CONFIG_FILE"

    echo ""
    echo "[DONE] Backup completed"
    echo "[LOG] $RUN_LOG"
}

# ==================================================
# SCHEDULE FUNCTION (CRON SAFE)
# ==================================================
schedule_backup() {

    echo ""
    echo "======================================"
    echo " Schedule Backup"
    echo "======================================"
    echo ""

    echo "1) Daily 2AM"
    echo "2) Weekly Sunday 2AM"
    echo "3) Custom cron"
    echo ""

    read -p "Choose option: " opt

    case "$opt" in
        1) CRON="0 2 * * *" ;;
        2) CRON="0 2 * * 0" ;;
        3) read -p "Enter cron format (e.g */5 * * * *): " CRON ;;
        *) echo "[ERROR] Invalid option"; return ;;
    esac

    JOB="$CRON $BASE_DIR/MVNBC.sh --auto"

    # install cron safely (no duplicates)
    (crontab -l 2>/dev/null | grep -v "$BASE_DIR/MVNBC.sh"; echo "$JOB") | crontab -

    # get PID of cron service (for reference only)
    CRON_PID=$(pgrep cron | head -n 1)

    echo "[OK] Schedule successfully added"
    echo "[INFO] CRON: $CRON"
    echo "[INFO] COMMAND: $JOB"
    echo "[INFO] CRON PID: $CRON_PID"
}

# ==================================================
# AUTO MODE
# ==================================================
if [[ "$1" == "--auto" ]]; then
    run_backup
    exit 0
fi

# ==================================================
# MENU
# ==================================================
echo "1) Run Backup Now"
echo "2) Schedule Backup"
echo "3) Exit"
echo ""

read -p "Choose option: " opt

case "$opt" in
    1) run_backup ;;
    2) schedule_backup ;;
    *) exit 0 ;;
esac
