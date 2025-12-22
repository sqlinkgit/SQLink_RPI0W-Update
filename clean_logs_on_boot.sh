#!/bin/bash

LOG_FILE="/var/log/svxlink"
ARCHIVE_DIR="/var/log/svxlink_history"
MAX_ARCHIVES=3

systemctl stop svxlink

if [ ! -d "$ARCHIVE_DIR" ]; then
    mkdir -p "$ARCHIVE_DIR"
    chown svxlink:daemon "$ARCHIVE_DIR"
    chmod 755 "$ARCHIVE_DIR"
fi

if [ -s "$LOG_FILE" ]; then
    mv "$LOG_FILE" "$ARCHIVE_DIR/svxlink_$(date +"%Y%m%d_%H%M%S").log"
fi

touch "$LOG_FILE"
chown svxlink:daemon "$LOG_FILE"
chmod 644 "$LOG_FILE"

ls -1t "$ARCHIVE_DIR"/svxlink_*.log 2>/dev/null | tail -n +$((MAX_ARCHIVES + 1)) | xargs -r rm --

systemctl start svxlink