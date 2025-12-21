<?php
    // --- 1. API TELEMETRII (Raspberry Pi) ---
    if (isset($_GET['ajax_stats'])) {
        header('Content-Type: application/json');
        $stats = [];

        // Hardware & Temp
        $model = @file_get_contents('/sys/firmware/devicetree/base/model');
        $stats['hw'] = $model ? str_replace("\0", "", trim($model)) : "Raspberry Pi";
        $temp_raw = @file_get_contents('/sys/class/thermal/thermal_zone0/temp');
        $stats['temp'] = $temp_raw ? round($temp_raw / 1000, 1) : 0;

        // RAM & Disk
        $free = shell_exec('free -m');
        $free_arr = explode("\n", (string)trim($free));
        $mem = preg_split("/\s+/", $free_arr[1]);
        $stats['ram_percent'] = round(($mem[2] / $mem[1]) * 100, 1);
        $dt = disk_total_space('/');
        $df = disk_free_space('/');
        $stats['disk_percent'] = round((($dt - $df) / $dt) * 100, 1);

        // NETWORK INFO
        $ip = trim(shell_exec("hostname -I | awk '{print $1}'"));
        $stats['ip'] = empty($ip) ? "Brak IP" : $ip;
        $ssid = trim(shell_exec("iwgetid -r"));
        if (!empty($ssid)) {
            $stats['net_type'] = "WiFi";
            $stats['ssid'] = $ssid;
        } elseif (!empty($ip) && $ip != "127.0.0.1") {
            $stats['net_type'] = "LAN";
            $stats['ssid'] = "";
        } else {
            $stats['net_type'] = "Offline";
            $stats['ssid'] = "";
        }

        echo json_encode($stats);
        exit;
    }

    // --- 2. OBSŁUGA DTMF ---
    if (isset($_POST['ajax_dtmf'])) {
        $code = $_POST['ajax_dtmf'];
        if (preg_match('/^[0-9A-D*#]+$/', $code)) {
            shell_exec("sudo /usr/local/bin/send_dtmf.sh " . escapeshellarg($code));
            echo "OK: $code";
        } else { echo "ERROR"; }
        exit;
    }

    // --- KONFIGURACJA AUDIO (CM108 RPi Simple) ---
    $CARD_ID = 0; 

    // Prosty mixer dla RPi (CM108)
    $MIXER_IDS = [
        'Mic_Cap_Sw' => 7,      
        'Mic_Cap_Vol' => 8,     
        'Auto_Gain_Ctrl' => 9,  
        'Spk_Play_Sw' => 5,     
        'Spk_Play_Vol' => 6     
    ];

    $audio = [];
    $audio_msg = '';

    function get_alsa_value($card, $numid) {
        $cmd = "sudo /usr/bin/amixer -c $card cget numid=$numid 2>&1";
        $output = shell_exec($cmd);
        if (preg_match('/: values=(\d+)/', $output, $matches)) return (int)$matches[1];
        if (preg_match('/: values=(on|off)/', $output, $matches)) return $matches[1] === 'on' ? 1 : 0;
        return 0;
    }

    if (isset($_POST['save_audio'])) {
        $audio_data = $_POST;
        foreach (['mic_cap_vol' => 'Mic_Cap_Vol', 'spk_play_vol' => 'Spk_Play_Vol'] as $post_name => $mix_name) {
            $numid = $MIXER_IDS[$mix_name];
            $val = (int)$audio_data[$post_name];
            if ($numid > 0) shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=$numid $val");
        }
        foreach (['Mic_Cap_Sw', 'Auto_Gain_Ctrl', 'Spk_Play_Sw'] as $mix_name) {
            $numid = $MIXER_IDS[$mix_name];
            $state = isset($audio_data[$mix_name]) && $audio_data[$mix_name] == '1' ? 'on' : 'off';
            if ($numid > 0) shell_exec("sudo /usr/bin/amixer -c $CARD_ID cset numid=$numid $state");
        }
        shell_exec("sudo /usr/sbin/alsactl store $CARD_ID");
        $audio_msg = '<div class="alert alert-success">✅ Audio ZAPISANE.</div>';
    }

    if (isset($_POST['reset_audio_defaults'])) {
        $output = shell_exec("sudo /usr/local/bin/reset_audio.sh 2>&1");
        $audio_msg = "<div class='alert alert-warning'><strong>⚠️ Reset:</strong> $output</div>";
    }

    foreach ($MIXER_IDS as $key => $numid) {
        $audio[$key] = ($numid > 0) ? get_alsa_value($CARD_ID, $numid) : 0;
    }

    // --- CONFIG SVXLINK ---
    function parse_svx_conf($file) {
        $ini = []; $curr = "GLOBAL";
        if (!file_exists($file)) return [];
        foreach (file($file) as $line) {
            $line = trim($line);
            if (empty($line) || $line[0] == '#' || $line[0] == ';') continue;
            if ($line[0] == '[' && substr($line, -1) == ']') { $curr = substr($line, 1, -1); $ini[$curr] = []; }
            else { $parts = explode('=', $line, 2); if (count($parts) == 2) $ini[$curr][trim($parts[0])] = trim(trim($parts[1]), '"\''); }
        }
        return $ini;
    }
    $ini = parse_svx_conf('/etc/svxlink/svxlink.conf');
    $ref = $ini['ReflectorLogic'] ?? []; $simp = $ini['SimplexLogic'] ?? []; $glob = $ini['GLOBAL'] ?? []; $el = $ini['ModuleEchoLink'] ?? [];

    $vals = [
        'Callsign' => $ref['CALLSIGN'] ?? 'N0CALL', 'Host' => $ref['HOSTS'] ?? '', 'Port' => $ref['HOST_PORT'] ?? '', 'Password' => $ref['AUTH_KEY'] ?? '',
        'DefaultTG' => $ref['DEFAULT_TG'] ?? '0', 'Modules' => $simp['MODULES'] ?? 'Help,Parrot,EchoLink'
    ];

    $jsonFile = '/var/www/html/radio_config.json';
    $radio = ["rx" => "432.8500", "tx" => "432.8500", "ctcss" => "0000", "sq" => "2"];
    if (file_exists($jsonFile)) { $loaded = json_decode(file_get_contents($jsonFile), true); if ($loaded) $radio = array_merge($radio, $loaded); }

    // --- AKCJE SYSTEMOWE ---
    if (isset($_POST['save_svx_full'])) {
        $newData = $_POST; unset($newData['save_svx_full'], $newData['active_tab']); file_put_contents('/tmp/svx_new_settings.json', json_encode($newData));
        shell_exec('sudo /usr/bin/python3 /usr/local/bin/update_svx_full.py 2>&1'); shell_exec('sudo /usr/bin/systemctl restart svxlink > /dev/null 2>&1 &');
        echo "<div class='alert alert-success'>Zapisano! Restart...</div><meta http-equiv='refresh' content='3'>";
    }
    if (isset($_POST['save_radio'])) {
        $newRadio = ["rx"=>$_POST['rx'], "tx"=>$_POST['tx'], "ctcss"=>$_POST['ctcss'], "sq"=>$_POST['sq']]; file_put_contents($jsonFile, json_encode($newRadio)); $radio = $newRadio;
        shell_exec('sudo /usr/bin/systemctl stop svxlink'); sleep(1);
        $cmd = "sudo /usr/bin/python3 /usr/local/bin/setup_radio.py ".escapeshellarg($radio['rx'])." ".escapeshellarg($radio['tx'])." ".escapeshellarg($radio['ctcss'])." ".escapeshellarg($radio['sq'])." 2>&1";
        $out = shell_exec($cmd); shell_exec('sudo /usr/bin/systemctl start svxlink'); echo "<div class='alert alert-success'>Radio: $out</div>";
    }

    if (isset($_POST['restart_srv'])) { shell_exec('sudo /usr/bin/systemctl restart svxlink > /dev/null 2>&1 &'); echo "<div class='alert alert-success'>Restart Usługi...</div>"; }
    if (isset($_POST['reboot_device'])) { shell_exec('sudo /usr/sbin/reboot > /dev/null 2>&1 &'); echo "<div class='alert alert-warning'>🔄 Reboot...</div>"; }
    if (isset($_POST['shutdown_device'])) { shell_exec('sudo /usr/sbin/shutdown -h now > /dev/null 2>&1 &'); echo "<div class='alert alert-error'>🛑 Shutdown...</div>"; }
    if (isset($_POST['auto_proxy'])) { $out = shell_exec("sudo /usr/local/bin/proxy_hunter.py 2>&1"); echo "<div class='alert alert-warning'><strong>♻️ Auto-Proxy:</strong><br><small>$out</small></div><meta http-equiv='refresh' content='3'>"; }
    
    // --- KLUCZOWY MOMENT AKTUALIZACJI ---
    if (isset($_POST['git_update'])) {
        $out = shell_exec("sudo /usr/local/bin/update_dashboard.sh 2>&1");
        echo "<div class='alert alert-warning' style='text-align:left;'><strong>Wynik Aktualizacji:</strong><br><pre style='font-size:10px;'>$out</pre></div>";
        echo "<meta http-equiv='refresh' content='5'>";
    }
    
    // WiFi Scan & Connect
    $wifi_output = "";
    if (isset($_POST['wifi_scan'])) { shell_exec('sudo nmcli dev wifi rescan'); $raw = shell_exec('sudo nmcli -t -f SSID,SIGNAL,SECURITY dev wifi list 2>&1'); $lines = explode("\n", $raw); foreach($lines as $line) { if(empty($line)) continue; $parts = explode(':', $line); $sec = array_pop($parts); $sig = array_pop($parts); $ssid = implode(':', $parts); if(!empty($ssid)) $wifi_scan_results[$ssid] = ['ssid'=>$ssid, 'signal'=>$sig, 'sec'=>$sec]; } usort($wifi_scan_results, function($a, $b) { return $b['signal'] - $a['signal']; }); }
    
    $saved_wifi_list = [];
    $saved_raw = shell_exec("sudo nmcli -t -f NAME,TYPE connection show | grep '802-11-wireless' | grep -v 'Rescue_AP'");
    if($saved_raw) {
        $s_lines = explode("\n", trim($saved_raw));
        foreach($s_lines as $s_line) { $s_parts = explode(":", $s_line); if(count($s_parts) >= 1) { $saved_wifi_list[] = $s_parts[0]; } }
    }

    if (isset($_POST['wifi_connect'])) { $ssid = escapeshellarg($_POST['ssid']); $pass = escapeshellarg($_POST['pass']); $wifi_output = shell_exec("sudo nmcli dev wifi connect $ssid password $pass 2>&1"); }
    if (isset($_POST['wifi_delete'])) { $ssid = escapeshellarg($_POST['ssid']); $wifi_output = shell_exec("sudo nmcli connection delete $ssid 2>&1"); echo "<div class='alert alert-warning'>Usunięto sieć: ".htmlspecialchars($_POST['ssid'])."</div><meta http-equiv='refresh' content='2'>"; }
?>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
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
        <div class="status-bar">
            <span id="main-status-dot" class="status-dot red"></span>
            <span id="main-status-text" class="status-text inactive">SYSTEM START...</span>
        </div>
    </header>

    <div class="telemetry-row">
        <div class="t-box">
            <div class="t-label">CPU Temp</div>
            <div class="t-val" id="t-temp">...</div>
            <div class="progress-bg"><div class="progress-fill" id="t-temp-bar" style="width: 0%;"></div></div>
        </div>
        <div class="t-box">
            <div class="t-label">RAM Used</div>
            <div class="t-val" id="t-ram">...</div>
            <div class="progress-bg"><div class="progress-fill" id="t-ram-bar" style="width: 0%;"></div></div>
        </div>
        <div class="t-box">
            <div class="t-label">Disk Used</div>
            <div class="t-val" id="t-disk">...</div>
            <div class="progress-bg"><div class="progress-fill" id="t-disk-bar" style="width: 0%;"></div></div>
        </div>
        <div class="t-box">
            <div class="t-label">Network</div>
            <div class="t-val" id="t-net-type">...</div>
            <div style="font-size:9px; color:#aaa;" id="t-ip">...</div>
        </div>
        <div class="t-box">
            <div class="t-label">Hardware</div>
            <div class="t-val" id="t-hw" style="font-size:10px; margin-top:5px;">...</div>
        </div>
    </div>

    <div class="info-panel">
        <div class="info-box"><div class="info-label">Logiki</div><div class="info-value" style="font-size:11px;"><?php echo str_replace(',', ', ', $glob['LOGICS'] ?? '-'); ?></div></div>
        <div class="info-box"><div class="info-label">Moduły</div><div class="info-value" style="font-size:11px;"><?php echo $vals['Modules']; ?></div></div>
        <div class="info-box"><div class="info-label">TG Default</div><div class="info-value hl"><?php echo $vals['DefaultTG']; ?></div></div>
        <div class="info-box"><div class="info-label">TG Active</div><div class="info-value hl" id="tg-active">---</div></div>
        <div class="info-box"><div class="info-label">Reflector</div><div class="info-value" id="ref-status">---</div></div>
        <div class="info-box"><div class="info-label">Uptime</div><div class="info-value" style="font-size:11px;"><?php echo shell_exec("uptime -p"); ?></div></div>
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
    <span class="callsign-blue">SQLink System</span> • <span style="color: #ffffff;">SierraEcho & Team Edition</span><br>
    Website design by <span class="callsign-blue">SQ7UTP</span>
</div>

<script>
    const GLOBAL_CALLSIGN = "<?php echo $vals['Callsign']; ?>";
</script>

<script src="script.js?v=<?php echo time(); ?>"></script>

</body>
</html>