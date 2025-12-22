#!/bin/bash

LOG_SOURCE="/var/log/svxlink"
LOG_DEST="/var/www/html/svx_events.log"

touch $LOG_DEST
chown www-data:www-data $LOG_DEST
chmod 644 $LOG_DEST

# Start filtrowania w tle (Node joined/left, Reflector connect/disconnect, Talker)
tail -F -n 0 "$LOG_SOURCE" | grep --line-buffered -E "ReflectorLogic: Connection established|ReflectorLogic: Disconnected|ReflectorLogic: Authentication failed|Node joined|Node left|Talker start|Talker stop" >> "$LOG_DEST" &