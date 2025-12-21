<h3>🎓 Centrum Dowodzenia i Pomocy (SQLink RPi Zero Edition)</h3>
<div style="text-align: center; margin-bottom: 20px; font-size: 0.9em; color: #888; background: #222; padding: 5px; border-radius: 4px; border: 1px solid #444;">
    ℹ️ System zaprojektowany wyłącznie dla: <strong style="color: #4CAF50;">Raspberry Pi Zero W</strong> + Karta <strong style="color: #2196F3;">CM108 USB</strong>
</div>
<div class="help-section">
    <div class="help-title"><span class="help-icon">🖥️</span> 1. Twój Kokpit (Dashboard)</div>
    <div class="help-text">
        To tutaj sprawdzasz puls swojego urządzenia. Wszystko powinno świecić na zielono!
        <ul>
            <li><strong>🚦 Pasek Statusu:</strong> To ten kolorowy pasek na samej górze. Jeśli jest <span style="color:#4CAF50; font-weight:bold;">ZIELONY</span>, system działa. Jeśli <span style="color:#F44336; font-weight:bold;">CZERWONY</span>, coś się popsuło (zrób restart w zakładce Zasilanie).</li>
            <li><strong>🌡️ Temperatura:</strong> Raspberry Pi Zero to mały twardziel, ale nie lubi upałów.
                <br><small>✅ 30°C - 55°C: Jest super!<br>🔥 > 70°C: Za gorąco! Zapewnij malinie trochę powietrza.</small>
            </li>
            <li><strong>📺 Wielki Monitor (Live):</strong> Tu widzisz, co się dzieje w eterze:
                <ul>
                    <li>⚪ <strong>Cisza (Standby):</strong> Nikt nie gada, nuda.</li>
                    <li>🟢 <span style="color:#4CAF50; font-weight:bold;">ODBIERANIE (RX):</span> Ty mówisz do radia (Hotspot Cię słyszy).</li>
                    <li>🟠 <span style="color:#FF9800; font-weight:bold;">NADAWANIE (TX):</span> Ktoś mówi z internetu (Słyszysz to w radiu).</li>
                </ul>
            </li>
        </ul>
    </div>
</div>
<div class="help-section" style="border-left: 5px solid #FF9800;">
    <div class="help-title"><span class="help-icon">🆘</span> 2. Tryb Ratunkowy WiFi (Ważne!)</div>
    <div class="help-text">
        Twoje Raspberry Pi Zero W nie ma kabla, więc co zrobić, gdy zmienisz router lub pójdziesz z nim w teren?
        <br><br>
        <div style="border: 3px solid #FF9800; padding: 15px; border-radius: 8px;">
            <strong>🚨 Jak odzyskać łączność bez monitora?</strong><br><br>
            1. Włącz Hotspota tam, gdzie nie ma Twojej domowej sieci WiFi.<br>
            2. Poczekaj cierpliwie około <strong>2 minuty</strong> (system musi "zrozumieć", że nie ma internetu).<br>
            3. Hotspot automatycznie stworzy własną sieć WiFi!<br><br>
            📱 <strong>Szukaj sieci (SSID):</strong> <span style="color:#FF9800; font-size:1.1em; font-weight:bold;">SQLink_WiFi_AP</span><br>
            🔐 <strong>Hasło:</strong> <code>sqlink123</code><br>
            🌐 <strong>Adres strony:</strong> <a href="http://192.168.4.1" target="_blank" style="color:#FF9800; font-weight:bold;">192.168.4.1</a><br><br>
            Połącz się telefonem, wejdź na ten adres, skonfiguruj nowe WiFi w zakładce "WiFi" i zrób Restart. Gotowe!
        </div>
    </div>
</div>
<div class="help-section">
    <div class="help-title"><span class="help-icon">🔄</span> 3. Dwa Światy: Reflektor i EchoLink</div>
    <div class="help-text">
        Pamiętaj: Możesz być tylko w jednym miejscu naraz!
        <div class="help-step">
            <strong>🅰️ Świat A: Reflektor (SQLink)</strong><br>
            To jest Twój "dom". Jesteś tu zawsze po uruchomieniu.<br>
            Rozmawiasz z polskimi stacjami na grupach (np. Ogólnopolska).
        </div>
        <div class="help-step" style="border-left-color: #2196F3;">
            <strong>🅱️ Świat B: EchoLink (Światowy)</strong><br>
            Chcesz pogadać z kimś z USA, Japonii czy innego miasta?<br>
            1. Wejdź w zakładkę EchoLink.<br>
            2. Wybierz numer węzła i kliknij <strong>📞 Połącz</strong>.<br>
            <hr style="border: 0; border-top: 1px dashed #ccc; margin: 10px 0;">
            🛑 <strong>BARDZO WAŻNE:</strong> Kiedy skończysz rozmawiać, <strong>MUSISZ SIĘ ROZŁĄCZYĆ!</strong><br>
            👉 Kliknij przycisk <span style="color:#F44336; font-weight:bold;">📵 Rozłącz (#)</span> lub wpisz <strong>#</strong> na klawiaturze radia.<br>
            Dopiero gdy usłyszysz "Deactivating module EchoLink", wracasz do polskiej sieci.
        </div>
    </div>
</div>
<div class="help-section">
    <div class="help-title"><span class="help-icon">🎚️</span> 4. Audio (CM108) - Nie kręć bez potrzeby!</div>
    <div class="help-text">
        Suwaki w zakładce Audio są bardzo czułe i skalibrowane pod karty CM108.
        <ul>
            <li><strong>🎧 Diagnostyka CM108:</strong> Na górze zakładki Audio jest niebieski panel. 
                <br>👉 Użyj go, jeśli po zmianie sprzętu lub wgraniu kopii zapasowej <strong>nie masz dźwięku</strong>.
                <br>Kliknij <strong>🔍 Znajdź i Napraw Audio</strong> - system sam ustawi odpowiedni port USB dla Twojej karty.
            </li>
            <li><strong>📢 Suwak TX (Głośność):</strong> To jak głośno koledzy "krzyczą" z Twojego radia.</li>
            <li><strong>🎙️ Suwak MIC (Czułość):</strong> Reguluje poziom Twojego głosu wysyłanego w świat.</li>
            <li><strong>🔊 Pokrętło głośności w Radiu:</strong> W radiach Quansheng/Baofeng działa ono jak "wstępne wzmocnienie mikrofonu".
                <br><small>👉 Ustaw je raz na ok. 1/3 zakresu i staraj się go nie dotykać. Jeśli je przekręcisz na MAX, hotspot będzie "pierdział" i nikt Cię nie zrozumie!</small>
            </li>
        </ul>
        <div class="help-warn">
            💡 <strong>Testuj mądrze:</strong> Użyj funkcji <strong>🦜 Papuga</strong> w zakładce DTMF, żeby posłuchać samego siebie. To najlepszy tester!
        </div>
    </div>
</div>
<div class="help-section">
    <div class="help-title"><span class="help-icon">⚡</span> 5. Zasilanie i Aktualizacje</div>
    <div class="help-text">
        W zakładce <strong>Zasilanie</strong> masz centrum sterowania życiem maliny.
        <ul>
            <li><strong>🔄 Reboot / Wyłącz:</strong> Bezpieczne zamykanie systemu. Nie wyrywaj wtyczki z prądu, bo karta pamięci tego nie lubi!</li>
            <li><strong>☁️ Aktualizuj System:</strong> Kliknij zielony przycisk, żeby pobrać nowości. Hotspot sam połączy się z GitHubem i ściągnie poprawki.</li>
            <li><strong>♻️ Restart Usługi SvxLink:</strong> "Lekarstwo na wszystko". Jeśli dashboard się zawiesi albo dźwięk zniknie - kliknij to. Trwa to tylko 5-10 sekund.</li>
        </ul>
    </div>
</div>
<div class="help-section" style="border:none;">
    <div class="help-title"><span class="help-icon">🔧</span> Szybka Pomoc (Q&A)</div>
    <div class="help-text">
        <strong>❓ Wgrałem backup na inną kartę i nie mam dźwięku!</strong><br>
        ✅ Spokojnie! Każde RPi Zero może inaczej zaindeksować kartę USB. Wejdź w zakładkę <strong>Audio</strong> i kliknij niebieski przycisk <strong>🔍 Znajdź i Napraw Audio</strong>. System znajdzie Twoją kartę CM108.<br><br>
        <strong>❓ EchoLink nie łączy (Status: Disconnected).</strong><br>
        ✅ Masz internet z telefonu (LTE)? Operatorzy często blokują porty. Wejdź w zakładkę <strong>Config</strong> i kliknij zielony przycisk <strong>♻️ Auto-Proxy</strong>. To "magiczny przycisk", który omija blokady.<br><br>
        <strong>❓ Radio milczy, a na ekranie widać, że ktoś gada (RX).</strong><br>
        ✅ Sprawdź w swoim radiu ręcznym kody <strong>CTCSS / Tone Squelch</strong>. Muszą być identyczne jak w ustawieniach Hotspota (zakładka Radio). Najlepiej na początek wyłącz kody w radiu i w Hotspocie (ustaw 0).<br><br>
        <strong>❓ W logach widzę "Distortion detected".</strong><br>
        ✅ Twoje radio krzyczy do Hotspota za głośno! Ścisz radio (jeśli masz kabel) lub zmniejsz suwak <strong>MIC / ADC Gain</strong> w zakładce Audio.
    </div>
</div>