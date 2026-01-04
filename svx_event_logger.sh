#!/bin/bash
LOG_SOURCE="/var/log/svxlink"
LOG_DEST="/var/www/html/svx_events.log"
FLAG_ONLINE="/var/www/html/el_online.flag"
FLAG_ERROR="/var/www/html/el_error.flag"

pkill -f "tail -F -n +1 $LOG_SOURCE"
pkill -f "tail -F -n 0 $LOG_SOURCE"

rm -f "$FLAG_ONLINE"
rm -f "$FLAG_ERROR"


touch "$LOG_DEST"
chown www-data:www-data "$LOG_DEST"
chmod 644 "$LOG_DEST"


tail -F -n 0 "$LOG_SOURCE" | while read -r line; do
    

    if echo "$line" | grep -qE "ReflectorLogic|EchoLink|Tx1|Rx1|Node|Talker|Underrun|Clipping|Distortion|ModuleEchoLink"; then
        echo "$line" >> "$LOG_DEST"
    fi


    case "$line" in

        *"EchoLink directory status changed to ON"*)
            touch "$FLAG_ONLINE"
            rm -f "$FLAG_ERROR"
            chown www-data:www-data "$FLAG_ONLINE"
            ;;
            

        *"EchoLink directory status changed to"*"OFF"*)
            rm -f "$FLAG_ONLINE"
            ;;


        *"EchoLink authentication failed"*|*"Connection failed"*|*"Disconnected from EchoLink proxy"*)
            rm -f "$FLAG_ONLINE"
            touch "$FLAG_ERROR"
            chown www-data:www-data "$FLAG_ERROR"
            ;;
    esac
done &