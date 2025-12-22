#!/bin/bash

LOG_SOURCE="/var/log/svxlink"
LOG_DEST="/var/www/html/svx_events.log"

# Upewnij się, że plik docelowy istnieje i ma prawa
touch $LOG_DEST
chown www-data:www-data $LOG_DEST
chmod 644 $LOG_DEST

# Start filtrowania w tle
# Dodano: "Connected nodes"
tail -F -n 0 "$LOG_SOURCE" | grep --line-buffered -E "ReflectorLogic: Connection established|ReflectorLogic: Disconnected|ReflectorLogic: Authentication failed|Node joined|Node left|Talker start|Talker stop|Connected nodes" >> "$LOG_DEST" &