<?php
$jsonFile = '/var/www/html/radio_config.json';
$radio_display = [
    "rx" => "432.8000", "tx" => "432.8000", "ctcss" => "0000", "desc" => "Brak opisu",
    "gpio_ptt" => "12", "gpio_sql" => "16"
];

$CTCSS_TONES = [
    "0000" => "Brak (CSQ)", "0670" => "67.0 Hz", "0719" => "71.9 Hz", "0744" => "74.4 Hz", "0770" => "77.0 Hz",
    "0797" => "79.7 Hz", "0825" => "82.5 Hz", "0854" => "85.4 Hz", "0885" => "88.5 Hz", "0915" => "91.5 Hz",
    "0948" => "94.8 Hz", "0974" => "97.4 Hz", "1000" => "100.0 Hz", "1035" => "103.5 Hz", "1072" => "107.2 Hz",
    "1109" => "110.9 Hz", "1148" => "114.8 Hz", "1188" => "118.8 Hz", "1230" => "123.0 Hz", "1273" => "127.3 Hz",
    "1318" => "131.8 Hz", "1365" => "136.5 Hz", "1413" => "141.3 Hz", "1462" => "146.2 Hz", "1514" => "151.4 Hz",
    "1567" => "156.7 Hz", "1622" => "162.2 Hz", "1679" => "167.9 Hz", "1738" => "173.8 Hz", "1799" => "179.9 Hz",
    "1862" => "186.2 Hz", "1928" => "192.8 Hz", "2035" => "203.5 Hz", "2107" => "210.7 Hz", "2181" => "218.1 Hz",
    "2257" => "225.7 Hz", "2336" => "233.6 Hz", "2418" => "241.8 Hz", "2503" => "250.3 Hz"
];

if (file_exists($jsonFile)) {
    $loaded = json_decode(file_get_contents($jsonFile), true);
    if ($loaded) {
        $radio_display = array_merge($radio_display, $loaded);
    }
}
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div class="panel-box" style="border-top: 3px solid #2196F3;">
        <h4 class="panel-title blue">📝 Wizytówka Dashboardu</h4>
        <div style="font-size: 12px; color: #aaa; margin-bottom: 15px; font-style: italic;">
            Dane wyświetlane na stronie głównej. Nie wpływają na fizyczne strojenie radia (wymagane ręczne ustawienie gałki).
        </div>
        <form method="post">
            <input type="hidden" name="active_tab" class="active-tab-input" value="Radio">
            <div class="form-group">
                <label>Opis Sprzętu</label>
                <input type="text" name="radio_desc" value="<?php echo htmlspecialchars($radio_display['desc']); ?>" placeholder="np. Motorola GM360">
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>RX Freq (MHz)</label>
                    <input type="text" name="rx_freq" value="<?php echo htmlspecialchars($radio_display['rx']); ?>">
                </div>
                <div class="form-group">
                    <label>TX Freq (MHz)</label>
                    <input type="text" name="tx_freq" value="<?php echo htmlspecialchars($radio_display['tx']); ?>">
                </div>
                <div class="form-group">
                    <label>CTCSS (Info)</label>
                    <select name="ctcss_val">
                        <?php foreach($CTCSS_TONES as $code => $label): ?>
                            <option value="<?php echo $code; ?>" <?php if(isset($radio_display['ctcss']) && $radio_display['ctcss'] == $code) echo 'selected'; ?>>
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <hr style="border:0; border-top:1px solid #444; margin: 20px 0;">
            
            <h4 class="panel-title blue">⚙️ Konfiguracja GPIO (Hardware)</h4>
            <div style="font-size: 12px; color: #aaa; margin-bottom: 15px;">
                Zdefiniuj piny Raspberry Pi sterujące radiem. Zmiana wymaga restartu usługi.
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                <div class="form-group">
                    <label>GPIO PTT (Nadawanie)</label>
                    <input type="number" name="gpio_ptt" value="<?php echo htmlspecialchars($radio_display['gpio_ptt']); ?>">
                    <small style="color:#888; font-size:9px;">Domyślnie: 12</small>
                </div>
                <div class="form-group">
                    <label>GPIO SQL (Blokada)</label>
                    <input type="number" name="gpio_sql" value="<?php echo htmlspecialchars($radio_display['gpio_sql']); ?>">
                    <small style="color:#888; font-size:9px;">Domyślnie: 16</small>
                </div>
            </div>

            <button type="submit" name="save_radio" class="btn btn-blue" style="margin-top:15px;">💾 Zapisz Konfigurację i Restartuj</button>
        </form>
    </div>

    <div>
        <div class="panel-box" style="border-left: 5px solid #FF9800; background: #26201b;">
            <h4 class="panel-title" style="color: #FF9800; border: none;">⚠️ Ustawienia Radia Analogowego</h4>
            <div style="font-size: 13px; color: #ddd; line-height: 1.6;">
                <div style="display: flex; align-items: flex-start; gap: 10px; margin-bottom: 15px; background: rgba(0,0,0,0.2); padding: 8px; border-radius: 5px;">
                    <div style="font-size: 24px;">☝️</div>
                    <div>
                        <b style="color: #FF9800;">INFO:</b> Ten panel steruje tylko logiką SvxLink. Częstotliwość i CTCSS musisz ustawić <b>fizycznie na radiu</b>.
                    </div>
                </div>
                <ul style="list-style: none; padding: 0; margin-top: 10px;">
                    <li style="margin-bottom: 8px;">
                        🔊 <b>Głośność Radia (RX):</b> Ustaw tak, aby w zakładce Audio wskaźnik był na zielono, ale nie przesterowany.
                    </li>
                    <li style="margin-bottom: 8px;">
                        🎤 <b>Poziom Modulacji (TX):</b> Reguluj suwakiem "Speaker Volume" w zakładce Audio.
                    </li>
                    <li style="margin-bottom: 8px;">
                        🚫 <b>Funkcje Radia:</b> Wyłącz <i>Battery Save</i>, <i>Roger Beep</i> i <i>VOX</i> w menu radia.
                    </li>
                </ul>
            </div>
        </div>
        
        <div class="panel-box" style="margin-top: 20px; text-align: center; color: #888; font-size: 11px; padding: 10px;">
            <strong>ℹ️ Schemat połączeń (GPIO):</strong><br>
            <span style="color: #E91E63;">● PTT (TX)</span>: GPIO <?php echo htmlspecialchars($radio_display['gpio_ptt']); ?> ➜ Sterowanie 2N7000 Mosfet (Low/High)<br>
            <span style="color: #2196F3;">● SQL (RX)</span>: GPIO <?php echo htmlspecialchars($radio_display['gpio_sql']); ?> ➜ Sygnał COS z radia (3.3V Logic)<br>
        </div>
    </div>
</div>