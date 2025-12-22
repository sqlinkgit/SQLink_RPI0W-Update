#!/bin/bash

GIT_URL="https://github.com/sqlinkgit/SQLink_RPI0W-Update.git"
GIT_DIR="/root/SQLink_RPI0W-Update"
WWW_DIR="/var/www/html"

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
        if grep -q "exit 0" "$RC_LOCAL"; then
            sudo sed -i '/exit 0/i \/usr/local/bin/clean_logs_on_boot.sh &' "$RC_LOCAL"
        else
            sudo sed -i '$a \/usr/local/bin/clean_logs_on_boot.sh &' "$RC_LOCAL"
        fi
    fi
fi

NM_CONF="/etc/NetworkManager/conf.d/default-wifi-powersave-on.conf"
if [ ! -f "$NM_CONF" ]; then
    sudo mkdir -p /etc/NetworkManager/conf.d
    echo -e "[connection]\nwifi.powersave = 2" | sudo tee "$NM_CONF" > /dev/null
    sudo systemctl restart NetworkManager
fi

sudo sed -i '/ping -i/d' "$RC_LOCAL"

SERVICE_FILE="/etc/systemd/system/ping-keepalive.service"
if [ ! -f "$SERVICE_FILE" ]; then
    echo "[Unit]" | sudo tee "$SERVICE_FILE"
    echo "Description=Ping Keepalive" | sudo tee -a "$SERVICE_FILE"
    echo "After=network-online.target" | sudo tee -a "$SERVICE_FILE"
    echo "Wants=network-online.target" | sudo tee -a "$SERVICE_FILE"
    echo "" | sudo tee -a "$SERVICE_FILE"
    echo "[Service]" | sudo tee -a "$SERVICE_FILE"
    echo "ExecStart=/bin/ping -i 15 8.8.8.8" | sudo tee -a "$SERVICE_FILE"
    echo "Restart=always" | sudo tee -a "$SERVICE_FILE"
    echo "RestartSec=10" | sudo tee -a "$SERVICE_FILE"
    echo "" | sudo tee -a "$SERVICE_FILE"
    echo "[Install]" | sudo tee -a "$SERVICE_FILE"
    echo "WantedBy=multi-user.target" | sudo tee -a "$SERVICE_FILE"
    
    sudo systemctl daemon-reload
    sudo systemctl enable ping-keepalive
    sudo systemctl start ping-keepalive
fi

if ! cmp -s "$GIT_DIR/update_dashboard.sh" "/usr/local/bin/update_dashboard.sh"; then
    sudo cp "$GIT_DIR/update_dashboard.sh" /usr/local/bin/
    sudo chmod +x /usr/local/bin/update_dashboard.sh
fi