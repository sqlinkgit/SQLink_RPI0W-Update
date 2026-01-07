<?php
$custom_dtmf_file = '/var/www/html/dtmf_custom.json';
$custom_buttons = [];
if (file_exists($custom_dtmf_file)) {
    $custom_buttons = json_decode(file_get_contents($custom_dtmf_file), true);
    if (!is_array($custom_buttons)) $custom_buttons = [];
}
?>

<div class="dtmf-columns">
    <div class="panel-box">
        <h4 class="panel-title">Reflector / Grupy</h4>
        
        <div class="dtmf-tabs">
            <div class="dtmf-tab-btn active" id="tab-btn-SQLink" onclick="openDtmfSubTab('SQLink')">SQLink</div>
            <div class="dtmf-tab-btn" id="tab-btn-Mine" onclick="openDtmfSubTab('Mine')">Moje</div>
        </div>

        <div id="DTMF-SQLink" class="dtmf-subtab" style="display:block;">
            <div class="macro-grid">
                <button onclick="sendInstant('*91260#')" class="macro-btn">Ogólnopolska<span class="dtmf-sub">TG 260</span></button>
                <button onclick="sendInstant('*9126077#')" class="macro-btn">Sierra Echo<span class="dtmf-sub">TG 26077</span></button>
                <button onclick="sendInstant('*91260066#')" class="macro-btn">ExtremeLink<span class="dtmf-sub">TG 260066</span></button>
                <button onclick="sendInstant('*91235#')" class="macro-btn">Bridge UK<span class="dtmf-sub">TG 235</span></button>
                <button onclick="sendInstant('*91245#')" class="macro-btn">EchoLink<span class="dtmf-sub">TG 245</span></button>
                <button onclick="sendInstant('*91999#')" class="macro-btn">Testowa<span class="dtmf-sub">TG 999</span></button>
                <button onclick="sendInstant('*912600#')" class="macro-btn">Zagraniczna<span class="dtmf-sub">TG 2600</span></button>

                <button onclick="sendInstant('*#')" class="macro-btn">Status<span class="dtmf-sub">(*#)</span></button>
                <button onclick="sendInstant('910#')" class="macro-btn red">Rozłącz<span class="dtmf-sub">(910#)</span></button>
            </div>
        </div>

        <div id="DTMF-Mine" class="dtmf-subtab" style="display:none;">
            <?php if (empty($custom_buttons)): ?>
                <div style="text-align:center; color:#777; padding:20px; font-size:13px;">Brak własnych przycisków. Dodaj poniżej.</div>
            <?php else: ?>
                <div class="macro-grid">
                    <?php foreach($custom_buttons as $idx => $btn): ?>
                        <div style="position:relative;">
                            <button onclick="sendInstant('*91<?php echo $btn['tg']; ?>#')" class="macro-btn orange" style="color:#fff;">
                                <?php echo htmlspecialchars($btn['name']); ?>
                                <span class="dtmf-sub">TG <?php echo $btn['tg']; ?></span>
                            </button>
                            <form method="post" style="position:absolute; top:-5px; right:-5px; margin:0;">
                                <input type="hidden" name="active_tab" class="active-tab-input" value="DTMF">
                                <input type="hidden" name="del_dtmf_index" value="<?php echo $idx; ?>">
                                <button type="submit" class="dtmf-del-mini" onclick="return confirm('Usunąć?')">x</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div style="margin-top:20px; border-top:1px solid #444; padding-top:10px;">
                <form method="post">
                    <input type="hidden" name="active_tab" class="active-tab-input" value="DTMF">
                    <div style="display:flex; gap:5px;">
                        <input type="text" name="add_dtmf_name" placeholder="Nazwa" class="node-input" style="flex:1; font-size:13px;" required>
                        <input type="number" name="add_dtmf_code" placeholder="TG" class="node-input" style="width:80px; font-size:13px;" required>
                        <button type="submit" class="macro-btn green" style="width:auto; min-height:40px; font-size:20px; padding:0 15px;">+</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="panel-box">
        <h4 class="panel-title blue">EchoLink (Moduł 2)</h4>
        <div style="margin-bottom:15px; border-bottom:1px solid #444; padding-bottom:15px;">
            <button onclick="sendInstant('2#')" class="macro-btn green" style="margin-bottom:10px; height: auto;">1. Aktywuj Moduł (2#)</button>
            <div class="node-input-group">
                <input type="text" id="el-node-id" class="node-input" placeholder="Nr Noda (np. 459342)">
                <button onclick="connectEchoLink()" class="macro-btn blue" style="width: auto; min-height: 40px;">Połącz</button>
            </div>
            <div class="macro-grid">
                <button onclick="sendInstant('9999#')" class="macro-btn">Test Echo (9999)</button>
                <button onclick="sendInstant('#')" class="macro-btn red">Rozłącz (#)</button>
            </div>
        </div>
        <div id="el-live-status">Sprawdzanie statusu...</div>
    </div>

    <div class="panel-box" style="grid-column: 1 / -1; border-color: #FF9800;">
        <h4 class="panel-title" style="color: #FF9800; border-color: #FF9800;">🦜 Papuga (Test Audio)</h4>
        <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
            <div style="flex: 1; min-width: 200px;">
                <div class="macro-grid">
                    <button onclick="sendInstant('1#')" class="macro-btn orange">▶️ Włącz Papugę (1#)</button>
                    <button onclick="sendInstant('#')" class="macro-btn red">⏹️ Wyłącz / Stop (#)</button>
                </div>
            </div>
            <div style="flex: 1; font-size: 13px; color: #ccc; background: #222; padding: 10px; border-radius: 5px; border-left: 3px solid #FF9800;">
                <strong>Jak używać?</strong>
                <ol style="margin: 5px 0; padding-left: 20px; line-height: 1.6;">
                    <li>Kliknij <span style="color:#FF9800; font-weight:bold;">Włącz Papugę</span>. Usłyszysz komunikat "Moduł Papuga".</li>
                    <li>Wciśnij PTT w radiu, powiedz kilka słów ("Test, raz, dwa...") i puść PTT.</li>
                    <li>Hotspot powinien odesłać Twój głos. Jeśli słyszysz siebie czysto – audio jest OK.</li>
                    <li>Kliknij <span style="color:#F44336; font-weight:bold;">Wyłącz</span>, aby zakończyć test.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<h3 style="border-top:1px solid #444; paddingTop:20px;">Klawiatura Numeryczna</h3>
<div class="dtmf-display" id="dtmf-screen">...</div>
<div class="dtmf-grid"><button onclick="typeKey('1')" class="dtmf-btn">1</button><button onclick="typeKey('2')" class="dtmf-btn">2</button><button onclick="typeKey('3')" class="dtmf-btn">3</button><button onclick="typeKey('4')" class="dtmf-btn">4</button><button onclick="typeKey('5')" class="dtmf-btn">5</button><button onclick="typeKey('6')" class="dtmf-btn">6</button><button onclick="typeKey('7')" class="dtmf-btn">7</button><button onclick="typeKey('8')" class="dtmf-btn">8</button><button onclick="typeKey('9')" class="dtmf-btn">9</button><button onclick="typeKey('*')" class="dtmf-btn">*</button><button onclick="typeKey('0')" class="dtmf-btn">0</button><button onclick="typeKey('#')" class="dtmf-btn">#</button><button onclick="clearKey()" class="dtmf-btn dtmf-clear">C</button><button onclick="submitTG()" class="dtmf-btn dtmf-tg">TG</button><button onclick="submitKey()" class="dtmf-btn dtmf-send">TX</button></div>
<p style="font-size:12px; color:#888; text-align:center;"><b>C</b>=Kasuj, <b>TG</b>=Ustaw Grupę (*91..#), <b>TX</b>=Wyślij Kod</p>