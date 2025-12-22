#!/bin/bash

GIT_URL="https://github.com/sqlinkgit/SQLink_RPI0W-Update.git"
GIT_DIR="/root/SQLink_RPI0W-Update"
WWW_DIR="/var/www/html"

echo "--- UPDATE START (RPi Zero W) ---"
date

sleep 5

# 1. Pobierz repozytorium
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

# 2. Python tools
if compgen -G "$GIT_DIR/*.py" > /dev/null; then
    sudo cp $GIT_DIR/*.py /usr/local/bin/
    sudo chmod +x /usr/local/bin/*.py
fi

# 3. Skrypty SH
for script in $GIT_DIR/*.sh; do
    filename=$(basename "$script")
    if [ "$filename" != "update_dashboard.sh" ]; then
        sudo cp "$script" /usr/local/bin/
        sudo chmod +x "/usr/local/bin/$filename"
    fi
done

# 4. WWW i Configi
sudo cp $GIT_DIR/*.css $WWW_DIR/ 2>/dev/null
sudo cp $GIT_DIR/*.js $WWW_DIR/ 2>/dev/null
sudo cp $GIT_DIR/*.png $WWW_DIR/ 2>/dev/null
sudo cp $GIT_DIR/*.php $WWW_DIR/

if [ ! -f "$WWW_DIR/radio_config.json" ]; then
    if [ -f "$GIT_DIR/radio_config.json" ]; then
        sudo cp $GIT_DIR/radio_config.json $WWW_DIR/
    fi
fi

# 5. Dźwięki
if [ -d "$GIT_DIR/sounds" ]; then
    sudo mkdir -p /usr/local/share/svxlink/sounds/pl_PL/
    sudo cp -r $GIT_DIR/sounds/* /usr/local/share/svxlink/sounds/pl_PL/
    sudo chown -R svxlink:daemon /usr/local/share/svxlink/sounds/pl_PL/
    sudo chmod -R 755 /usr/local/share/svxlink/sounds/pl_PL/
fi

# 6. Uprawnienia WWW
sudo chown -R www-data:www-data $WWW_DIR
sudo chmod -R 755 $WWW_DIR

# --- 7. AUTOSTART: Fix Logów ---
RC_LOCAL="/etc/rc.local"
CLEANER_SCRIPT="/usr/local/bin/clean_logs_on_boot.sh"

if [ -f "$CLEANER_SCRIPT" ]; then
    if ! grep -q "clean_logs_on_boot.sh" "$RC_LOCAL"; then
        echo "🔧 Dodaję logger do rc.local..."
        sudo sed -i -e '$i \/usr/local/bin/clean_logs_on_boot.sh &\n' "$RC_LOCAL"
    fi
fi

# --- 8. FIX WIFI POWER SAVE (NetworkManager) ---
NM_CONF="/etc/NetworkManager/conf.d/default-wifi-powersave-on.conf"
if [ ! -f "$NM_CONF" ]; then
    echo "🔧 Konfiguracja NetworkManager (Power Save OFF)..."
    sudo mkdir -p /etc/NetworkManager/conf.d
    echo -e "[connection]\nwifi.powersave = 2" | sudo tee "$NM_CONF" > /dev/null
    sudo systemctl restart NetworkManager
fi

# --- 9. FIX 5-MIN DROPS (PING KEEPALIVE) ---
# To jest kluczowe dla Twojego problemu z rozłączaniem co 5 minut.
if ! grep -q "ping -i 15" "$RC_LOCAL"; then
    echo "🔧 Dodaję Ping Keepalive (Fix 5-min drop)..."
    # Wysyła ping co 15 sekund w tle
    sudo sed -i -e '$i \/bin/ping -i 15 8.8.8.8 > /dev/null 2>&1 &\n' "$RC_LOCAL"
fi

# 10. Aktualizacja samego siebie
if ! cmp -s "$GIT_DIR/update_dashboard.sh" "/usr/local/bin/update_dashboard.sh"; then
    sudo cp "$GIT_DIR/update_dashboard.sh" /usr/local/bin/
    sudo chmod +x /usr/local/bin/update_dashboard.sh
fi

echo "--- UPDATE DONE ---"