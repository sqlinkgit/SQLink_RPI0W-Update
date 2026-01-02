<?php
$jsonFile = '/var/www/html/radio_config.json';
$radio_display = [
    "rx" => "432.8000",
    "tx" => "432.8000",
    "ctcss" => "0000",
    "desc" => "Baofeng / Quansheng"
];
if (file_exists($jsonFile)) {
    $loaded = json_decode(file_get_contents($jsonFile), true);
    if ($loaded) {
        $radio_display = array_merge($radio_display, $loaded);
    }
}
if (isset($_POST['update_radio_display'])) {
    $newData = [
        "rx" => $_POST['rx_freq'],
        "tx" => $_POST['tx_freq'],
        "ctcss" => $_POST['ctcss_tone'],
        "desc" => $_POST['radio_desc']
    ];
    file_put_contents($jsonFile, json_encode($newData));
    $radio_display = $newData;
    echo "<div class='alert alert-success'>✅ Opis Dashboardu został zaktualizowany!</div>";
    echo "<meta http-equiv='refresh' content='2'>";
}
$CTCSS_MAP = [
    "0000" => "Brak (CSQ)", "0670" => "67.0 Hz", "0719" => "71.9 Hz", "0744" => "74.4 Hz", "0770" => "77.0 Hz",
    "0797" => "79.7 Hz", "0825" => "82.5 Hz", "0854" => "85.4 Hz", "0885" => "88.5 Hz", "0915" => "91.5 Hz",
    "0948" => "94.8 Hz", "0974" => "97.4 Hz", "1000" => "100.0 Hz", "1035" => "103.5 Hz", "1072" => "107.2 Hz",
    "1109" => "110.9 Hz", "1148" => "114.8 Hz", "1188" => "118.8 Hz", "1230" => "123.0 Hz", "1273" => "127.3 Hz",
    "1318" => "131.8 Hz", "1365" => "136.5 Hz", "1413" => "141.3 Hz", "1462" => "146.2 Hz", "1514" => "151.4 Hz",
    "1567" => "156.7 Hz", "1622" => "162.2 Hz", "1679" => "167.9 Hz", "1738" => "173.8 Hz", "1799" => "179.9 Hz",
    "1862" => "186.2 Hz", "1928" => "192.8 Hz", "2035" => "203.5 Hz", "2107" => "210.7 Hz", "2181" => "218.1 Hz",
    "2257" => "225.7 Hz", "2336" => "233.6 Hz", "2418" => "241.8 Hz", "2503" => "250.3 Hz"
];
?>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="panel-box" style="border-top: 3px solid #2196F3;">
        <h4 class="panel-title blue">📝 Edycja Opisu Dashboardu</h4>
        <div style="font-size: 12px; color: #aaa; margin-bottom: 15px; font-style: italic;">
            Tutaj wprowadź dane, które masz <b>fizycznie</b> ustawione w radiu. <br>
            Te informacje pojawią się w nagłówku strony.
        </div>
        <form method="post">
            <input type="hidden" name="active_tab" class="active-tab-input" value="Radio">
            <div class="form-group">
                <label>Opis Sprzętu (np. Model Radia)</label>
                <input type="text" name="radio_desc" value="<?php echo htmlspecialchars($radio_display['desc']); ?>" placeholder="np. Baofeng UV-5R">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>RX Freq (MHz)</label>
                    <input type="text" name="rx_freq" value="<?php echo htmlspecialchars($radio_display['rx']); ?>">
                </div>
                <div class="form-group">
                    <label>TX Freq (MHz)</label>
                    <input type="text" name="tx_freq" value="<?php echo htmlspecialchars($radio_display['tx']); ?>">
                </div>
            </div>
            <div class="form-group">
                <label>Ton CTCSS (Blokada)</label>
                <select name="ctcss_tone">
                    <?php
                        foreach($CTCSS_MAP as $code => $label) {
                            $sel = ($radio_display['ctcss'] == $code) ? 'selected' : '';
                            echo "<option value='$code' $sel>$label</option>";
                        }
                    ?>
                </select>
            </div>
            <button type="submit" name="update_radio_display" class="btn btn-blue">💾 Zaktualizuj Wizytówkę</button>
        </form>
    </div>
    <div>
        <div class="panel-box" style="border-left: 5px solid #FF9800; background: #26201b;">
            <h4 class="panel-title" style="color: #FF9800; border: none;">⚠️ Ustawienia Audio (KRYTYCZNE)</h4>
            <div style="font-size: 13px; color: #ddd; line-height: 1.6;">
                
                <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 15px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 5px;">
                    <div style="font-size: 24px;">☝️</div>
                    <div>
                        <b style="color: #FF9800;">PAMIĘTAJ:</b> Pokrętło głośności w Twoim radiu (np. Quansheng) steruje tym, jak <u>głośno słyszą Cię inni</u> w sieci!
                    </div>
                </div>

                <ul style="list-style: none; padding: 0; margin-top: 10px;">
                    <li style="margin-bottom: 8px;">
                        🎚️ <b>Głośność w radiu:</b> Ustaw pokrętło na <b>ok. 30-40%</b>.
                        <br><small style="color: #bbb;">Zbyt głośno = charczenie i "Distortion detected".</small>
                    </li>
                    <li style="margin-bottom: 8px;">
                        🚫 <b>MIC GAIN w Menu:</b> Ustaw na <b>OFF / MINIMUM</b>.
                    </li>
                    <li style="margin-bottom: 8px;">
                        🔇 <b>COMPANDER:</b> Musi być <b>OFF</b>.
                    </li>
                </ul>
                <div style="background: rgba(255,152,0,0.1); padding: 5px; border: 1px dashed #FF9800; font-size: 11px; margin-top: 5px;">
                    💡 <b>Wskazówka:</b> Jeśli koledzy mówią, że jesteś za cicho, najpierw lekko podkręć głośność w radiu, zanim zaczniesz zmieniać czułość w zakładce Audio.
                </div>
            </div>
        </div>
        <div class="panel-box" style="border-top: 3px solid #4CAF50;">
            <h4 class="panel-title green">✅ Pozostałe Ustawienia Radia</h4>
            <div style="font-size: 13px; color: #ccc;">
                <div style="display: flex; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px;">
                    <div style="font-size: 20px; margin-right: 10px;">🗣️</div>
                    <div>
                        <strong>VOX</strong> ➜ <span style="color: #F44336; font-weight: bold;">OFF (Wyłączony)</span>
                        <br><small>Absolutnie konieczne!</small>
                    </div>
                </div>
                <div style="display: flex; align-items: center; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px;">
                    <div style="font-size: 20px; margin-right: 10px;">🔋</div>
                    <div>
                        <strong>POWER SAVE</strong> ➜ <span style="color: #F44336; font-weight: bold;">OFF (Wyłączony)</span>
                        <br><small>Tryb oszczędzania ucina początek rozmowy.</small>
                    </div>
                </div>
                <div style="display: flex; align-items: center;">
                    <div style="font-size: 20px; margin-right: 10px;">📟</div>
                    <div>
                        <strong>ROGER BEEP</strong> ➜ <span style="color: #F44336; font-weight: bold;">OFF</span>
                        <br><small>Wyłącz "piknięcie" w radiu.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="panel-box" style="margin-top: 20px; text-align: center; color: #888; font-size: 11px; padding: 10px;">
    <strong>ℹ️ DIY Interface (Schemat):</strong><br>
    <span style="color: #E91E63;">● Duży Jack (3.5mm)</span>: MIC+ (Pierścień) / PTT-GND (Tuleja) ➜ Sygnał Z RPi<br>
    <span style="color: #2196F3;">● Mały Jack (2.5mm)</span>: SPK+ (Czubek/Pierścień) / GND (Tuleja) ➜ Sygnał DO RPi<br>
    <span style="color: #FFC107;">⚡ PTT:</span> Sterowane tranzystorem (NPN) przez GPIO 12
</div>