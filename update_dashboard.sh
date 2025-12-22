#!/bin/bash

# Ustawienia pod nowe repozytorium RPi Zero W
GIT_URL="https://github.com/SQLinkgit/SQLink_RPI0W-Update.git"
GIT_DIR="/root/SQLink_RPI0W-Update"
WWW_DIR="/var/www/html"

echo "--- START AKTUALIZACJI (RPi Zero W) ---"
date

sleep 5

if [ ! -d "$GIT_DIR" ]; then
    echo "⚠️ Folder repozytorium RPi nie istnieje. Pobieram go od nowa..."
    cd /root
    git clone $GIT_URL
    if [ $? -ne 0 ]; then
        echo "❌ BŁĄD KRYTYCZNY: Nie udało się sklonować repozytorium."
        exit 1
    fi
fi

# 2. Pobierz zmiany z GitHub (Force Pull)
echo "Pobieram najnowszą wersję z GitHub..."
cd $GIT_DIR
git reset --hard
git pull origin main

# 3. AKTUALIZACJA NARZĘDZI SYSTEMOWYCH (To musisz dodać!)
echo "Instaluję narzędzia systemowe (Python/Bash)..."
if ls $GIT_DIR/*.py 1> /dev/null 2>&1; then
    sudo cp $GIT_DIR/*.py /usr/local/bin/
    sudo chmod +x /usr/local/bin/*.py
fi

if ls $GIT_DIR/*.sh 1> /dev/null 2>&1; then
    sudo cp $GIT_DIR/*.sh /usr/local/bin/
    sudo chmod +x /usr/local/bin/*.sh
fi

# 4. Kopiuj pliki na stronę WWW
echo "Kopiuję pliki dashboardu na stronę..."
sudo cp $GIT_DIR/*.php $WWW_DIR/
sudo cp $GIT_DIR/*.css $WWW_DIR/
sudo cp $GIT_DIR/*.js $WWW_DIR/
sudo cp $GIT_DIR/*.png $WWW_DIR/
if [ ! -f "$WWW_DIR/radio_config.json" ]; then
    sudo cp $GIT_DIR/radio_config.json $WWW_DIR/
fi

# 5. Obsługa Dźwięków
if [ -d "$GIT_DIR/sounds" ]; then
    echo "Aktualizuję komunikaty dźwiękowe..."
    sudo mkdir -p /usr/local/share/svxlink/sounds/pl_PL/
    sudo cp -r $GIT_DIR/sounds/* /usr/local/share/svxlink/sounds/pl_PL/
    sudo chown -R svxlink:daemon /usr/local/share/svxlink/sounds/pl_PL/
    sudo chmod -R 755 /usr/local/share/svxlink/sounds/pl_PL/
fi

# 6. Napraw uprawnienia WWW
sudo chown -R www-data:www-data $WWW_DIR
sudo chmod -R 755 $WWW_DIR

echo "--- KONIEC AKTUALIZACJI ---"