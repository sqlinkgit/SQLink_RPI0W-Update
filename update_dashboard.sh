#!/bin/bash

GIT_URL="https://github.com/sqlinkgit/SQLink_RPI0W-Update.git"
GIT_DIR="/root/SQLink_RPI0W-Update"
WWW_DIR="/var/www/html"

echo "--- UPDATE START (RPi Zero W) ---"
date

sleep 5

if [ ! -d "$GIT_DIR" ]; then
    cd /root
    git clone $GIT_URL
else
    cd $GIT_DIR
    git config core.fileMode false
    git fetch --all
    git reset --hard origin/main
    git pull origin main
fi


if compgen -G "$GIT_DIR/*.py" > /dev/null; then
    sudo cp $GIT_DIR/*.py /usr/local/bin/
    sudo chmod +x /usr/local/bin/*.py
fi


for script in $GIT_DIR/*.sh; do
    filename=$(basename "$script")
    if [ "$filename" != "update_dashboard.sh" ]; then
        sudo cp "$script" /usr/local/bin/
        sudo chmod +x "/usr/local/bin/$filename"
    fi
done


sudo cp $GIT_DIR/*.css $WWW_DIR/ 2>/dev/null
sudo cp $GIT_DIR/*.js $WWW_DIR/ 2>/dev/null
sudo cp $GIT_DIR/*.png $WWW_DIR/ 2>/dev/null
sudo cp $GIT_DIR/*.php $WWW_DIR/

if [ ! -f "$WWW_DIR/radio_config.json" ]; then
    if [ -f "$GIT_DIR/radio_config.json" ]; then
        sudo cp $GIT_DIR/radio_config.json $WWW_DIR/
    fi
fi


if [ -d "$GIT_DIR/sounds" ]; then
    sudo mkdir -p /usr/local/share/svxlink/sounds/pl_PL/
    sudo cp -r $GIT_DIR/sounds/* /usr/local/share/svxlink/sounds/pl_PL/
    sudo chown -R svxlink:daemon /usr/local/share/svxlink/sounds/pl_PL/
    sudo chmod -R 755 /usr/local/share/svxlink/sounds/pl_PL/
fi


sudo chown -R www-data:www-data $WWW_DIR
sudo chmod -R 755 $WWW_DIR


RC_LOCAL="/etc/rc.local"
CLEANER_SCRIPT="/usr/local/bin/clean_logs_on_boot.sh"

if [ -f "$CLEANER_SCRIPT" ]; then
    if ! grep -q "clean_logs_on_boot.sh" "$RC_LOCAL"; then
        echo "🔧 Dodaję logger do rc.local..."

        sudo sed -i -e '$i \/usr/local/bin/clean_logs_on_boot.sh &\n' "$RC_LOCAL"
    fi
fi


if ! grep -q "iw wlan0 set power_save off" "$RC_LOCAL"; then
    echo "🔧 Wyłączanie WiFi Power Save..."
    sudo sed -i -e '$i \/sbin/iw wlan0 set power_save off\n' "$RC_LOCAL"
fi


if ! cmp -s "$GIT_DIR/update_dashboard.sh" "/usr/local/bin/update_dashboard.sh"; then
    sudo cp "$GIT_DIR/update_dashboard.sh" /usr/local/bin/
    sudo chmod +x /usr/local/bin/update_dashboard.sh
fi

echo "--- UPDATE DONE ---"