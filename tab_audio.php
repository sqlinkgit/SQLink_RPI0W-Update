<?php echo isset($audio_msg) ? $audio_msg : ''; ?>

<div style="border: 1px solid #444; border-left: 5px solid #2196F3; padding: 15px; margin-bottom: 25px; background: rgba(33, 150, 243, 0.1); border-radius: 4px;">
    <h4 style="margin-top: 0; margin-bottom: 10px; color: #2196F3;">🎧 Diagnostyka Karty Dźwiękowej</h4>
    <p style="font-size: 0.9em; color: #ccc; margin-bottom: 15px;">
        Jeśli po zmianie portu USB lub wgraniu backupu nie ma dźwięku, użyj tego przycisku. System sam znajdzie kartę i poprawi konfigurację.
    </p>
    
    <form method="post">
        <input type="hidden" name="active_tab" class="active-tab-input" value="Audio">
        <button type="submit" name="fix_audio_btn" class="btn" style="background-color: #2196F3; color: white; border: none; padding: 8px 15px; cursor: pointer;">🔍 Znajdź i Napraw Audio</button>
    </form>

    <?php
    if (isset($_POST['fix_audio_btn'])) {
        echo '<div style="margin-top: 15px; padding: 10px; background: #111; border: 1px solid #444; color: #4CAF50; font-family: monospace; font-size: 0.9em;">';
        $output = shell_exec('sudo /usr/local/bin/fix_audio.py 2>&1');
        echo nl2br(htmlspecialchars($output));
        echo '</div>';
    }
    ?>
</div>

<h3>Mikser Audio (CM108 USB)</h3>

<form method="post">
    <input type="hidden" name="active_tab" class="active-tab-input" value="Audio">
    <div class="form-grid" style="grid-template-columns: 1fr 1fr;">

        <div class="audio-card highlight">
            <h4 class="audio-title green">Odbiór (RX) - Czułość Mikrofonu</h4>
            
            <div class="switch-row">
                <div class="switch-label">Mic Capture (Włącz)</div>
                <input type="checkbox" name="Mic_Cap_Sw" value="1" <?php if($audio['Mic_Cap_Sw']) echo "checked"; ?>>
            </div>

            <div class="slider-group">
                <div class="slider-header">
                    <span>Mic Volume (Czułość)</span>
                    <span class="slider-val"><span id="v_rx"><?php echo $audio['Mic_Cap_Vol']; ?></span>/35</span>
                </div>
                <input type="range" name="mic_cap_vol" min="0" max="35" value="<?php echo $audio['Mic_Cap_Vol']; ?>" oninput="document.getElementById('v_rx').innerText=this.value">
            </div>

            <div class="switch-row">
                <div class="switch-label">Auto Gain Control (AGC)</div>
                <input type="checkbox" name="Auto_Gain_Ctrl" value="1" <?php if($audio['Auto_Gain_Ctrl']) echo "checked"; ?>>
            </div>
            <small style="color:#FF9800;">*Dla stabilnej pracy AGC musi być WYŁĄCZONE (off)!</small>
        </div>

        <div class="audio-card highlight" style="border-color:#2196F3;">
            <h4 class="audio-title" style="color:#2196F3;">Nadawanie (TX) - Głośność na Radio</h4>
            
            <div class="switch-row">
                <div class="switch-label">Speaker Playback (Włącz)</div>
                <input type="checkbox" name="Spk_Play_Sw" value="1" <?php if($audio['Spk_Play_Sw']) echo "checked"; ?>>
            </div>

            <div class="slider-group">
                <div class="slider-header">
                    <span>Speaker Volume (Moc Audio)</span>
                    <span class="slider-val"><span id="v_tx"><?php echo $audio['Spk_Play_Vol']; ?></span>/37</span>
                </div>
                <input type="range" name="spk_play_vol" min="0" max="37" value="<?php echo $audio['Spk_Play_Vol']; ?>" oninput="document.getElementById('v_tx').innerText=this.value">
            </div>
        </div>

    </div>
    <button type="submit" name="save_audio" class="btn btn-green">Zapisz Ustawienia Audio</button>
</form>

<hr style="border:0; border-top:1px solid #444; margin:30px 0;">

<form method="post">
    <input type="hidden" name="active_tab" class="active-tab-input" value="Audio">
    <button type="submit" name="reset_audio_defaults" class="btn btn-red" onclick="return confirm('To zresetuje suwaki do bezpiecznych wartości. Kontynuować?')">⚠️ Reset Audio</button>
</form>
