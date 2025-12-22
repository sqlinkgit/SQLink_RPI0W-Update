<?php
    // --- 1. API TELEMETRII (Raspberry Pi) ---
    if (isset($_GET['ajax_stats'])) {
        header('Content-Type: application/json');
        $stats = [];
        $model = @file_get_contents('/sys/firmware/devicetree/base/model');
        $stats['hw'] = $model ? str_replace("\0", "", trim($model)) : "Raspberry Pi";
        $temp_raw = @file_get_contents('/sys/class/thermal/thermal_zone0/temp');
        $stats['temp'] = $temp_raw ? round($temp_raw / 1000, 1) : 0;
        $free = shell_exec('free -m');
        $free_arr = explode("\n", (string)trim($free));
        $mem = preg_split("/\s+/", $free_arr[1]);
        $stats['ram_percent'] = round(($mem[2] / $mem[1]) * 100, 1);
        $dt = disk_total_space('/');
        $df = disk_free_space('/');
        $stats['disk_percent'] = round((($dt - $df) / $dt) * 100, 1);
        $ip = trim(shell_exec("hostname -I | awk '{print $1}'"));
        $stats['ip'] = empty($ip) ? "Brak IP" : $ip;
        $ssid = trim(shell_exec("iwgetid -r"));
        if (!empty($ssid)) { $stats['net_type'] = "WiFi"; $stats['ssid'] = $ssid; }
        elseif (!empty($ip) && $ip != "127.0.0.1") { $stats['net_type'] = "LAN"; $stats['ssid'] = ""; }
        else { $stats['net_type'] = "Offline"; $stats['ssid'] = ""; }
        echo json_encode($stats); exit;
    }

    // --- 2. OBSŁUGA DTMF ---
    if (isset($_POST['ajax_dtmf'])) {
        $code = $_POST['ajax_dtmf'];
        if (preg_match('/^[0-9A-D*#]+$/', $code)) {
            shell_exec("sudo /usr/local/bin/send_dtmf.sh " . escapeshellarg($code));
            echo "OK: $code";
        } else { echo "ERROR"; } exit;
    }

    // --- 3. AUDIO (RPi Simple Mixer + Nowe Funkcje) ---
    $CARD_ID = 0;
    $MIXER_IDS = ['Mic_Cap_Sw' => 7, 'Mic_Cap_Vol' => 8, 'Auto_Gain_Ctrl' => 9, 'Spk_Play_Sw' => 5, 'Spk_Play_Vol' => 6];
    $audio = []; $audio_msg = '';
    
    function get_alsa_value($card, $numid) {
        $cmd = "sudo /usr/bin/amixer -c $card cget numid=$numid 2>&1";
        $output = shell_exec($cmd);
        if (preg_match('/: values=(\d+)/', $output, $matches)) return (int)$matches[1];
        if (preg_match('/: values=(on|off)/', $output, $matches)) return $matches[1] === 'on' ? 1 : 0;
        return 0;
    }

    // Zapis Audio (Suwaki)
    if (isset($_POST['save_audio'])) {
        foreach (['mic_cap_vol' => 'Mic_Cap_Vol', 'spk_play_vol' => 'Spk_Play_Vol'] as $p => $m) {
            $numid = $MIXER_IDS[$m]; $val = (int)$_POST[$p];
            if ($numid > 0) shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=$numid $val");
        }
        foreach (['Mic_Cap_Sw', 'Auto_Gain_Ctrl', 'Spk_Play_Sw'] as $m) {
            $numid = $MIXER_IDS[$m]; $state = isset($_POST[$m]) && $_POST[$m] == '1' ? 'on' : 'off';
            if ($numid > 0) shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=$numid $state");
        }
        shell_exec("sudo /usr/sbin/alsactl store $CARD_ID");
        $audio_msg = '<div class="alert alert-success">✅ Audio ZAPISANE.</div>';
    }

    // [NOWE] Reset Audio do bezpiecznych wartości
    if (isset($_POST['reset_audio_defaults'])) {
        shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=7 1");  // Mic On
        shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=8 25"); // Mic Vol ~70%
        shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=9 0");  // AGC Off
        shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=5 1");  // Spk On
        shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=6 30"); // Spk Vol ~80%
        shell_exec("sudo /usr/sbin/alsactl store $CARD_ID");
        $audio_msg = '<div class="alert alert-warning">♻️ Przywrócono domyślne ustawienia Audio.</div>';
    }

    foreach ($MIXER_IDS as $k => $id) $audio[$k] = ($id > 0) ? get_alsa_value($CARD_ID, $id) : 0;

    // --- 4. PARSOWANIE CONFIGU SVXLINK ---
    function parse_svx_conf($file) {
        $ini = []; $curr = "GLOBAL"; if (!file_exists($file)) return [];
        foreach (file($file) as $line) {
            $line = trim($line); if (empty($line) || $line[0] == '#' || $line[0] == ';') continue;
            if ($line[0] == '[' && substr($line, -1) == ']') { $curr = substr($line, 1, -1); $ini[$curr] = []; }
            else { $parts = explode('=', $line, 2); if (count($parts) == 2) $ini[$curr][trim($parts[0])] = trim(trim($parts[1]), '"\''); }
        } return $ini;
    }
    $ini = parse_svx_conf('/etc/svxlink/svxlink.conf');
    $ref = $ini['ReflectorLogic'] ?? []; $simp = $ini['SimplexLogic'] ?? []; $glob = $ini['GLOBAL'] ?? []; $el = $ini['ModuleEchoLink'] ?? [];

    $vals = [
        'Callsign'      => $ref['CALLSIGN'] ?? $simp['CALLSIGN'] ?? 'N0CALL',
        'Password'      => $ref['AUTH_KEY'] ?? '', 'Host' => $ref['HOSTS'] ?? '', 'Port' => $ref['HOST_PORT'] ?? '',
        'DefaultTG'     => $ref['DEFAULT_TG'] ?? '0', 'MonitorTGs' => $ref['MONITOR_TGS'] ?? '',
        'TgTimeout'     => $ref['TG_SELECT_TIMEOUT'] ?? '60', 'TmpTimeout' => $ref['TMP_MONITOR_TIMEOUT'] ?? '3600',
        'Beep3Tone'     => $ref['TGSTBEEP_ENABLE'] ?? '0', 'AnnounceTG' => $ref['TGREANON_ENABLE'] ?? '0',
        'RefStatusInfo' => $ref['REFCON_ENABLE'] ?? '0', 'RogerBeep' => $simp['RGR_SOUND_ALWAYS'] ?? '0',
        'Modules'       => $simp['MODULES'] ?? 'Help,Parrot,EchoLink'
    ];
    $vals_el = [
        'Callsign' => $el['CALLSIGN'] ?? $vals['Callsign'], 'Password' => $el['PASSWORD'] ?? '', 'Sysop' => $el['SYSOPNAME'] ?? '',
        'Desc' => $el['DESCRIPTION'] ?? '', 'Proxy' => $el['PROXY_SERVER'] ?? ''
    ];

    $jsonFile = '/var/www/html/radio_config.json';
    $radio = ["rx" => "432.8500", "tx" => "432.8500", "ctcss" => "0000", "sq" => "2", "desc" => "Brak opisu"];
    if (file_exists($jsonFile)) { $loaded = json_decode(file_get_contents($jsonFile), true); if ($loaded) $radio = array_merge($radio, $loaded); }

    // --- 5. AKCJE SYSTEMOWE I CONFIG ---
    if (isset($_POST['save_svx_full'])) {
        $newData = $_POST; unset($newData['save_svx_full'], $newData['active_tab']); 
        file_put_contents('/tmp/svx_new_settings.json', json_encode($newData));
        shell_exec('sudo /usr/bin/python3 /usr/local/bin/update_svx_full.py 2>&1'); 
        shell_exec('sudo /usr/bin/systemctl restart svxlink > /dev/null 2>&1 &');
        echo "<div class='alert alert-success'>Zapisano! Restart...</div><meta http-equiv='refresh' content='3'>";
    }
    
    // [NOWE] Obsługa Auto-Proxy
    if (isset($_POST['auto_proxy'])) {
        if (file_exists('/usr/local/bin/auto_proxy.py')) {
             shell_exec('sudo /usr/bin/python3 /usr/local/bin/auto_proxy.py > /dev/null 2>&1 &');
             echo "<div class='alert alert-warning'>♻️ Uruchomiono Auto-Proxy. SvxLink zrestartuje się za chwilę...</div>";
        } else {
             echo "<div class='alert alert-error'>❌ Błąd: Brak skryptu /usr/local/bin/auto_proxy.py</div>";
        }
    }

    // --- GPIO / SETUP RADIO ---
    if (isset($_POST['gpio_setup'])) {
        shell_exec("sudo /usr/bin/python3 /usr/local/bin/setup_radio.py > /dev/null 2>&1");
        echo "<div class='alert alert-success'>Konfiguracja Karty/GPIO zaktualizowana!</div><meta http-equiv='refresh' content='2'>";
    }

    if (isset($_POST['restart_srv'])) { shell_exec('sudo /usr/bin/systemctl restart svxlink > /dev/null 2>&1 &'); echo "<div class='alert alert-success'>Restart Usługi...</div>"; }
    if (isset($_POST['reboot_device'])) { shell_exec('sudo /usr/sbin/reboot > /dev/null 2>&1 &'); echo "<div class='alert alert-warning'>🔄 Reboot...</div>"; }
    if (isset($_POST['shutdown_device'])) { shell_exec('sudo /usr/sbin/shutdown -h now > /dev/null 2>&1 &'); echo "<div class='alert alert-error'>🛑 Shutdown...</div>"; }
    
    // --- GIT UPDATE ---
    if (isset($_POST['git_update'])) {
        $out = shell_exec("sudo /usr/local/bin/update_dashboard.sh 2>&1");
        echo "<div class='alert alert-warning' style='text-align:left;'><strong>Wynik Aktualizacji:</strong><br><pre style='font-size:10px;'>$out</pre></div><meta http-equiv='refresh' content='5'>";
    }

    // --- 6. WIFI (NMCLI) ---
    if (isset($_POST['wifi_scan'])) {
        $output = shell_exec("sudo /usr/bin/nmcli -f SSID,SIGNAL dev wifi list 2>&1");
        $lines = explode("\n", $output);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, "SSID") !== false || strpos($line, "--") !== false) continue;
            $parts = preg_split('/\s+/', $line);
            $ssid_part = $parts[0];
            $signal_part = end($parts);
            if (!empty($ssid_part) && $ssid_part != "--") {
                 $wifi_scan_results[] = ['ssid' => $ssid_part, 'signal' => $signal_part];
            }
        }
    }
    
    if (isset($_POST['wifi_connect'])) {
        $ssid = $_POST['ssid']; 
        $pass = $_POST['pass'];
        shell_exec("sudo /usr/bin/nmcli dev wifi connect " . escapeshellarg($ssid) . " password " . escapeshellarg($pass) . " > /dev/null 2>&1 &");
        echo "<div class='alert alert-success'>Wysłano polecenie połączenia z siecią: $ssid</div>";
    }

    if (isset($_POST['wifi_delete'])) {
        $ssid = $_POST['ssid'];
        shell_exec("sudo /usr/bin/nmcli connection delete " . escapeshellarg($ssid) . " > /dev/null 2>&1 &");
        echo "<div class='alert alert-warning'>Usuwanie sieci: $ssid</div><meta http-equiv='refresh' content='2'>";
    }

    // Pobieranie listy zapamiętanych sieci przez NMCLI (FILTR UKRYWANIA)
    $saved_wifi_list = [];
    $nm_saved = shell_exec("sudo /usr/bin/nmcli -t -f NAME connection show 2>/dev/null");
    
    // LISTA SIECI DO UKRYCIA
    $ignored_list = [
        "Wired connection 1",
        "lo",
        "Rescue_AP",
        "SQLink_WiFi_AP",
        "preconfigured"
    ];

    if ($nm_saved) {
        $lines = explode("\n", trim($nm_saved));
        foreach($lines as $l) {
            $l = trim($l);
            if(!empty($l) && !in_array($l, $ignored_list)) {
                $saved_wifi_list[] = $l;
            }
        }
    }
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotspot <?php echo $vals['Callsign']; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container">
    <header>
        <div style="position: relative; display: flex; justify-content: center; align-items: center; min-height: 100px;">
            <img src="sqlink4.png" alt="SQLink" style="position: absolute; left: 15%; top: 50%; transform: translateY(-50%); height: 90px; width: auto;">
            <h1 style="margin: 0; z-index: 2;">Hotspot <?php echo $vals['Callsign']; ?></h1>
            <img src="ant3.PNG" alt="Radio" style="position: absolute; right: 15%; top: 50%; transform: translateY(-50%); height: 90px; width: auto;">
        </div>
        <div class="status-bar"><span id="main-status-dot" class="status-dot red"></span><span id="main-status-text" class="status-text inactive">SYSTEM START...</span></div>
    </header>

    <div class="telemetry-row">
        <div class="t-box"><div class="t-label">CPU Temp</div><div class="t-val" id="t-temp">...</div><div class="progress-bg"><div class="progress-fill" id="t-temp-bar" style="width: 0%;"></div></div></div>
        <div class="t-box"><div class="t-label">RAM Used</div><div class="t-val" id="t-ram">...</div><div class="progress-bg"><div class="progress-fill" id="t-ram-bar" style="width: 0%;"></div></div></div>
        <div class="t-box"><div class="t-label">Disk Used</div><div class="t-val" id="t-disk">...</div><div class="progress-bg"><div class="progress-fill" id="t-disk-bar" style="width: 0%;"></div></div></div>
        <div class="t-box"><div class="t-label">Network</div><div class="t-val" id="t-net-type">...</div><div style="font-size:9px; color:#aaa;" id="t-ip">...</div></div>
        <div class="t-box"><div class="t-label">Hardware</div><div class="t-val" id="t-hw" style="font-size:10px; margin-top:5px;">...</div></div>
    </div>

    <div class="tabs">
        <button id="btn-Dashboard" class="tab-btn active" onclick="openTab(event, 'Dashboard')">Dashboard</button>
        <button id="btn-Nodes" class="tab-btn" onclick="openTab(event, 'Nodes')">Nodes</button>
        <button id="btn-DTMF" class="tab-btn" onclick="openTab(event, 'DTMF')">DTMF</button>
        <button id="btn-Radio" class="tab-btn" onclick="openTab(event, 'Radio')">Radio</button>
        <button id="btn-Audio" class="tab-btn" onclick="openTab(event, 'Audio')">Audio</button>
        <button id="btn-SvxConfig" class="tab-btn" onclick="openTab(event, 'SvxConfig')">Config</button>
        <button id="btn-WiFi" class="tab-btn" onclick="openTab(event, 'WiFi')">WiFi</button>
        <button id="btn-Power" class="tab-btn" onclick="openTab(event, 'Power')">Zasilanie</button>
        <button id="btn-Logs" class="tab-btn" onclick="openTab(event, 'Logs')">Logi</button>
        <button id="btn-Help" class="tab-btn" onclick="openTab(event, 'Help')">Pomoc</button>
    </div>

    <div id="Dashboard" class="tab-content active"><?php include 'tab_dashboard.php'; ?></div>
    <div id="Radio" class="tab-content"><?php include 'tab_radio.php'; ?></div>
    <div id="DTMF" class="tab-content"><?php include 'tab_dtmf.php'; ?></div>
    <div id="Audio" class="tab-content"><?php include 'tab_audio.php'; ?></div>
    <div id="SvxConfig" class="tab-content"><?php include 'tab_config.php'; ?></div>
    <div id="WiFi" class="tab-content"><?php include 'tab_wifi.php'; ?></div>
    <div id="Power" class="tab-content"><?php include 'tab_power.php'; ?></div>
    <div id="Nodes" class="tab-content"><?php include 'tab_nodes.php'; ?></div>
    <div id="Help" class="tab-content"><?php include 'help.php'; ?></div>
    <div id="Logs" class="tab-content"><div id="log-content" class="log-box">...</div></div>
</div>

<div class="main-footer">
    SvxLink v1.9.99.36@master Copyright (C) 2003-2025 Tobias Blomberg / <span class="callsign-blue">SM0SVX</span><br>
    <span class="callsign-blue">SQLink Systemm</span> • <span style="color: #ffffff;">SierraEcho & Team Edition</span><br>
    Website design by <span class="callsign-blue">SQ7UTP</span>
</div>

<script>const GLOBAL_CALLSIGN = "<?php echo $vals['Callsign']; ?>";</script>
<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>