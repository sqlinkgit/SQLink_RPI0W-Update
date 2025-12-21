#!/bin/bash

# Ustawienia
GIT_URL="https://github.com/SQLinkgit/SQLink_RPI0W-Update.git"
GIT_DIR="/root/SQLink_RPI0W-Update"
WWW_DIR="/var/www/html"

echo "--- START AKTUALIZACJI (RPi Zero W) ---"
date

sleep 10

if [ ! -d "$GIT_DIR" ]; then
    echo "⚠️ Folder repozytorium RPi nie istnieje. Pobieram go od nowa..."
    cd /root
    git clone $GIT_URL
    if [ $? -ne 0 ]; then
        echo "❌ BŁĄD KRYTYCZNY: Nie udało się sklonować repozytorium RPi. Sprawdź internet i nazwę repo."
        exit 1
    fi
fi

echo "Pobieram najnowszą wersję z GitHub (SQLink_RPI0W-Update)..."
cd $GIT_DIR
git reset --hard
git pull origin main

# 3. SAMO-AKTUALIZACJA SKRYPTU
if ! cmp -s "$GIT_DIR/update_dashboard.sh" "/usr/local/bin/update_dashboard.sh"; then
    echo "Znaleziono nową wersję skryptu aktualizacji. Instaluję..."
    sudo cp "$GIT_DIR/update_dashboard.sh" /usr/local/bin/
    sudo chmod +x /usr/local/bin/update_dashboard.sh
fi

# 4. Kopiuj pliki na stronę WWW
echo "Kopiuję pliki dashboardu na stronę..."
sudo cp $GIT_DIR/*.php $WWW_DIR/
sudo cp $GIT_DIR/*.css $WWW_DIR/
sudo cp $GIT_DIR/*.js $WWW_DIR/
sudo cp $GIT_DIR/*.png $WWW_DIR/

# 5. Obsługa Dźwięków (jeśli są w repo)
if [ -d "$GIT_DIR/sounds" ]; then
    echo "Wykryto folder dźwięków - aktualizuję komunikaty..."
    sudo mkdir -p /usr/local/share/svxlink/sounds/pl_PL/
    sudo cp -r $GIT_DIR/sounds/* /usr/local/share/svxlink/sounds/pl_PL/
    sudo chown -R svxlink:daemon /usr/local/share/svxlink/sounds/pl_PL/
    sudo chmod -R 755 /usr/local/share/svxlink/sounds/pl_PL/
fi

# 6. Napraw uprawnienia WWW
sudo chown -R www-data:www-data $WWW_DIR
sudo chmod -R 755 $WWW_DIR

echo "--- KONIEC AKTUALIZACJI ---"