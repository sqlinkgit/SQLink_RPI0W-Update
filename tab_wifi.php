<div class="panel-box" style="text-align:center; margin-bottom:15px;">
    <h4 class="panel-title blue">Status Połączenia</h4>
    <div id="wifi-tab-status" style="font-weight:bold; font-size:18px; color:#fff;">Ładowanie...</div>
    <div id="wifi-tab-ip" style="color:#4CAF50; margin-top:5px;">...</div>
</div>

<h3>Zapamiętane Sieci</h3>
<?php if (!empty($saved_wifi_list)): ?>
<table class="wifi-saved-table">
    <thead><tr><th>SSID</th><th>Akcja</th></tr></thead>
    <tbody>
        <?php foreach($saved_wifi_list as $sw): ?>
        <tr>
            <td><b><?php echo htmlspecialchars($sw); ?></b></td>
            <td style="text-align:right;">
                <form method="post" style="margin:0;">
                    <input type="hidden" name="active_tab" class="active-tab-input" value="WiFi">
                    <input type="hidden" name="ssid" value="<?php echo htmlspecialchars($sw); ?>">
                    <button type="submit" name="wifi_delete" class="btn-small-del" onclick="return confirm('Czy na pewno usunąć zapamiętaną sieć: <?php echo htmlspecialchars($sw); ?>?')">X Usuń</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>
<p style="color:#888; font-style:italic;">Brak zapamiętanych sieci.</p>
<?php endif; ?>

<h3>Połącz z nową siecią</h3>
<form method="post" style="margin-bottom: 20px;" onsubmit="document.getElementById('scan-btn').innerText = '⏳ Skanowanie...';">
    <input type="hidden" name="active_tab" class="active-tab-input" value="WiFi">
    <button type="submit" name="wifi_scan" id="scan-btn" class="btn btn-blue">📡 Skanuj Sieci WiFi</button>
</form>

<?php if (!empty($wifi_scan_results)): ?>
<div style="max-height:200px; overflow-y:auto; border:1px solid #333;">
    <table class="lh-table"><tbody><?php foreach($wifi_scan_results as $w): ?><tr class="wifi-row" onclick="selectWifi('<?php echo htmlspecialchars($w['ssid']); ?>')"><td><b><?php echo $w['ssid']; ?></b></td><td><?php echo $w['signal']; ?>%</td></tr><?php endforeach; ?></tbody></table>
</div>
<?php endif; ?>

<form method="post" style="margin-top:10px;" onsubmit="document.getElementById('btn-conn').innerHTML = '⏳ Łączenie... <span style=\'font-size:0.8em; font-weight:normal\'>(Czekaj...)</span>';">
    <input type="hidden" name="active_tab" class="active-tab-input" value="WiFi">
    
    <input type="text" id="wifi-ssid" name="ssid" placeholder="SSID">
    <input type="password" name="pass" placeholder="Hasło" style="margin-top:5px;">
    
    <button type="submit" name="wifi_connect" id="btn-conn" class="btn btn-green">Połącz</button>
</form>