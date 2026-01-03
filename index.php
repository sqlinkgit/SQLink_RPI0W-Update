<?php
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
        $ini_chk = parse_svx_conf('/etc/svxlink/svxlink.conf');
        $mods = $ini_chk['SimplexLogic']['MODULES'] ?? '';
        $stats['el_enabled'] = (strpos($mods, 'ModuleEchoLink') !== false || strpos($mods, 'EchoLink') !== false);
        $stats['el_error'] = file_exists('/var/www/html/el_error.flag');
        $stats['el_online'] = file_exists('/var/www/html/el_online.flag');
        echo json_encode($stats);
        exit;
    }

    if (isset($_POST['ajax_dtmf'])) {
        $code = $_POST['ajax_dtmf'];
        if (preg_match('/^[0-9A-D*#]+$/', $code)) {
            shell_exec("sudo /usr/local/bin/send_dtmf.sh " . escapeshellarg($code));
            echo "OK: $code";
        } else { echo "ERROR"; }
        exit;
    }

    $cards = shell_exec("cat /proc/asound/cards");
    if (preg_match('/(\d+)\s\[(Device|Set|USB)/', $cards, $matches)) {
        $CARD_ID = (int)$matches[1];
    } else {
        $CARD_ID = 0;
    }

    $MIXER_IDS = ['Mic_Cap_Sw' => 7, 'Mic_Cap_Vol' => 8, 'Auto_Gain_Ctrl' => 9, 'Spk_Play_Sw' => 5, 'Spk_Play_Vol' => 6];
    $audio = []; $audio_msg = '';
    
    function get_alsa_value($card, $numid) {
        $cmd = "sudo /usr/bin/amixer -c $card cget numid=$numid 2>&1";
        $output = shell_exec($cmd);
        if (preg_match('/: values=(\d+)/', $output, $matches)) return (int)$matches[1];
        if (preg_match('/: values=(on|off)/', $output, $matches)) return $matches[1] === 'on' ? 1 : 0;
        return 0;
    }

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
    
    if (isset($_POST['fix_audio_btn'])) {
       $audio_msg = ""; 
    }

    foreach ($MIXER_IDS as $k => $id) $audio[$k] = ($id > 0) ? get_alsa_value($CARD_ID, $id) : 0;

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
    $ref = $ini['ReflectorLogic'] ?? []; $simp = $ini['SimplexLogic'] ?? []; $glob = $ini['GLOBAL'] ?? []; $el = $ini['EchoLink'] ?? [];
    
    $rx1 = $ini['Rx1'] ?? [];
    $tx1 = $ini['Tx1'] ?? [];

    $currentSimplexCall = $simp['CALLSIGN'] ?? '';
    $voiceIDStatus = ($currentSimplexCall == '') ? '0' : '1';

    $vals = [
        'Callsign' => $ref['CALLSIGN'] ?? 'N0CALL', 'Host' => $ref['HOSTS'] ?? '', 'Port' => $ref['HOST_PORT'] ?? '', 'Password' => $ref['AUTH_KEY'] ?? '',
        'DefaultTG' => $ref['DEFAULT_TG'] ?? '0', 'MonitorTGs' => $ref['MONITOR_TGS'] ?? '', 'TgTimeout' => $ref['TG_SELECT_TIMEOUT'] ?? '60',
        'TmpTimeout' => $ref['TMP_MONITOR_TIMEOUT'] ?? '3600', 'Modules' => $simp['MODULES'] ?? 'Help,Parrot,EchoLink',
        'Beep3Tone' => $ref['TGSTBEEP_ENABLE'] ?? '0', 'AnnounceTG' => $ref['TGREANON_ENABLE'] ?? '0', 'RefStatusInfo' => $ref['REFCON_ENABLE'] ?? '0',
        'RogerBeep' => $simp['RGR_SOUND_ALWAYS'] ?? '0',
        'VoiceID'   => $voiceIDStatus,
    ];
    $vals_el = [
        'Callsign' => $el['CALLSIGN'] ?? $vals['Callsign'], 'Password' => $el['PASSWORD'] ?? '', 'Sysop' => $el['SYSOPNAME'] ?? '',
        'Location' => $el['LOCATION'] ?? '', 'Desc' => $el['DESCRIPTION'] ?? '', 'Proxy' => $el['PROXY_SERVER'] ?? '',
        'ModTimeout' => $el['TIMEOUT'] ?? '60', 'IdleTimeout' => $el['LINK_IDLE_TIMEOUT'] ?? '300',
    ];

    $jsonFile = '/var/www/html/radio_config.json';
    $radio = [
        "rx" => "432.8000", "tx" => "432.8000", "ctcss" => "0000", "desc" => "Brak opisu",
        "gpio_ptt" => $tx1['PTT_GPIOD_LINE'] ?? '12',
        "gpio_sql" => $rx1['SQL_GPIOD_LINE'] ?? '16'
    ];
    
    if (file_exists($jsonFile)) { 
        $loaded = json_decode(file_get_contents($jsonFile), true); 
        if ($loaded) $radio = array_merge($radio, $loaded); 
    }

    if (isset($_POST['save_svx_full'])) {
        $newData = $_POST;
        unset($newData['save_svx_full'], $newData['active_tab']);
        file_put_contents('/tmp/svx_new_settings.json', json_encode($newData));
        shell_exec('sudo /usr/bin/python3 /usr/local/bin/update_svx_full.py 2>&1');
        shell_exec('sudo /usr/bin/systemctl restart svxlink > /dev/null 2>&1 &');
        echo "<div class='alert alert-success'>Zapisano! Restart...</div><meta http-equiv='refresh' content='3'>";
    }

    if (isset($_POST['save_radio'])) {
        $newRadio = [
            "rx" => $_POST['rx_freq'], 
            "tx" => $_POST['tx_freq'], 
            "ctcss" => "0000",
            "desc" => $_POST['radio_desc'],
            "gpio_ptt" => $_POST['gpio_ptt'],
            "gpio_sql" => $_POST['gpio_sql']
        ];
        file_put_contents($jsonFile, json_encode($newRadio));
        $radio = $newRadio;
        
        $hwUpdate = ["GpioPtt"=>$_POST['gpio_ptt'], "GpioSql"=>$_POST['gpio_sql']];
        file_put_contents('/tmp/svx_new_settings.json', json_encode($hwUpdate));
        shell_exec('sudo /usr/bin/python3 /usr/local/bin/update_svx_full.py 2>&1');
        
        shell_exec('sudo /usr/bin/systemctl restart svxlink > /dev/null 2>&1 &');
        echo "<div class='alert alert-success'>Konfiguracja GPIO i Wizytówka Zapisane! Restart...</div><meta http-equiv='refresh' content='3'>";
    }

    if (isset($_POST['restart_srv'])) { shell_exec('sudo /usr/bin/systemctl restart svxlink > /dev/null 2>&1 &'); echo "<div class='alert alert-success'>Restart Usługi...</div>"; }
    if (isset($_POST['reboot_device'])) { shell_exec('sudo /usr/sbin/reboot > /dev/null 2>&1 &'); echo "<div class='alert alert-warning'>🔄 Reboot...</div>"; }
    if (isset($_POST['shutdown_device'])) { shell_exec('sudo /usr/sbin/shutdown -h now > /dev/null 2>&1 &'); echo "<div class='alert alert-error'>🛑 Shutdown...</div>"; }
    if (isset($_POST['auto_proxy'])) { 
        if (file_exists('/usr/local/bin/auto_proxy.py')) {
             shell_exec('sudo /usr/bin/python3 /usr/local/bin/auto_proxy.py > /dev/null 2>&1 &');
             echo "<div class='alert alert-warning'>♻️ Uruchomiono Auto-Proxy.</div>";
        }
    }
    
    if (isset($_POST['git_update'])) {
        set_time_limit(300); ignore_user_abort(true);
        $out = shell_exec("sudo /usr/local/bin/update_dashboard.sh 2>&1");
        
        if (strpos($out, 'STATUS: SUCCESS') !== false) {
            shell_exec('(sleep 5; sudo /usr/sbin/reboot) > /dev/null 2>&1 &');
            echo "
            <div class='alert alert-success' style='text-align:left; margin: 20px;'>
                <strong>✅ AKTUALIZACJA ZAKOŃCZONA SUKCESEM!</strong><br>
                System zostanie zrestartowany za <span id='cnt'>5</span> sekund...
                <pre style='font-size:10px; margin-top:5px; background:#111; color:#ccc; padding:5px; border-radius:3px; max-height:300px; overflow:auto;'>$out</pre>
            </div>
            <script>
                if(document.getElementById('loading-overlay')) {
                    document.getElementById('loading-overlay').style.display = 'none';
                }
                var sec = 5;
                setInterval(function(){
                    sec--;
                    var el = document.getElementById('cnt');
                    if(el) el.innerText = sec;
                    if(sec <= 0) {
                         document.body.innerHTML = '<h2 style=\"color:white; text-align:center; margin-top:50px; font-family:sans-serif;\">Trwa ponowne uruchamianie...<br><span style=\"font-size:16px; font-weight:normal;\">Poczekaj chwilę i odśwież stronę.</span></h2>';
                         setTimeout(function(){ window.location.href = '/'; }, 15000);
                    }
                }, 1000);
            </script>
            ";
        } elseif (strpos($out, 'STATUS: UP_TO_DATE') !== false) {
             echo "
             <div class='alert alert-warning' style='text-align:left; margin: 20px;'>
                <strong>⚠️ SYSTEM JEST JUŻ AKTUALNY</strong><br>
                Brak nowych zmian do pobrania. Powrót za chwilę...
             </div>
             <script>
                if(document.getElementById('loading-overlay')) {
                    document.getElementById('loading-overlay').style.display = 'none';
                }
             </script>
             <meta http-equiv='refresh' content='4;url=/'>";
        } else {
            echo "
            <div class='alert alert-error' style='text-align:left; margin: 20px;'>
                <strong>❌ BŁĄD AKTUALIZACJI!</strong><br>
                <pre style='font-size:10px; margin-top:5px; background:#300; padding:5px; border-radius:3px; max-height:300px; overflow:auto;'>$out</pre>
                <br><a href='/' class='btn btn-blue' style='display:inline-block; width:auto; padding:5px 10px;'>Wróć</a>
            </div>
            <script>
                if(document.getElementById('loading-overlay')) {
                    document.getElementById('loading-overlay').style.display = 'none';
                }
            </script>";
        }
    }
    
    $wifi_output = "";
    if (isset($_POST['wifi_scan'])) { shell_exec('sudo nmcli dev wifi rescan'); $raw = shell_exec('sudo nmcli -t -f SSID,SIGNAL,SECURITY dev wifi list 2>&1'); $lines = explode("\n", $raw); foreach($lines as $line) { if(empty($line)) continue; $parts = explode(':', $line); $sec = array_pop($parts); $sig = array_pop($parts); $ssid = implode(':', $parts); if(!empty($ssid)) $wifi_scan_results[$ssid] = ['ssid'=>$ssid, 'signal'=>$sig, 'sec'=>$sec]; } usort($wifi_scan_results, function($a, $b) { return $b['signal'] - $a['signal']; }); }
    if (isset($_POST['wifi_connect'])) { $ssid = escapeshellarg($_POST['ssid']); $pass = escapeshellarg($_POST['pass']); $wifi_output = shell_exec("sudo nmcli dev wifi connect $ssid password $pass 2>&1"); }
    if (isset($_POST['wifi_delete'])) { $ssid = escapeshellarg($_POST['ssid']); $wifi_output = shell_exec("sudo nmcli connection delete $ssid 2>&1"); echo "<div class='alert alert-warning'>Usunięto sieć.</div><meta http-equiv='refresh' content='2'>"; }
    
    $cache_file = '/tmp/sqlink_alert_cache.txt';
    $cache_time = 3600; 
    $alert_msg = "";
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_time)) { 
        $alert_msg = file_get_contents($cache_file); 
    } else { 
        $opts = [
            "http" => [
                "method" => "GET",
                "header" => "User-Agent: SQLink-Hotspot\r\n",
                "timeout" => 5
            ]
        ];
        $ctx = stream_context_create($opts);
        $remote_msg = @file_get_contents('https://raw.githubusercontent.com/SQLinkgit/SQLink_RPI0W-Update/main/alert.txt', false, $ctx); 
        
        if ($remote_msg !== false) { 
            $alert_msg = $remote_msg; 
            file_put_contents($cache_file, $alert_msg); 
        } elseif (file_exists($cache_file)) { 
            $alert_msg = file_get_contents($cache_file); 
        } 
    }
?>
<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hotspot <?php echo $vals['Callsign']; ?></title>
    <link rel="stylesheet" href="style.css?v=<?php echo time(); ?>">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .btn-loading {
            position: relative;
            color: transparent !important;
            pointer-events: none;
        }
        .btn-loading::after {
            content: "";
            position: absolute;
            left: 50%; top: 50%;
            width: 16px; height: 16px;
            margin-left: -8px; margin-top: -8px;
            border: 2px solid #fff;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.8s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>
<div class="container">
    <?php if (!empty(trim($alert_msg))): ?>
    <div style="background:#2196F3; color:#fff; text-align:center; padding:12px; font-weight:bold; border-bottom:2px solid #1976D2; font-size:14px;">📢 INFO: <?php echo htmlspecialchars($alert_msg); ?></div>
    <?php endif; ?>
    
    <header>
        <div style="position: relative; display: flex; justify-content: center; align-items: center; min-height: 100px;">
            <img src="sqlink4.png" alt="SQLink" style="position: absolute; left: 15%; top: 50%; transform: translateY(-50%); height: 90px; width: auto; display:none;" class="desktop-only">
            <h1 style="margin: 0; z-index: 2;">Hotspot <?php echo $vals['Callsign']; ?></h1>
            <img src="ant3.PNG" alt="Radio" style="position: absolute; right: 15%; top: 50%; transform: translateY(-50%); height: 90px; width: auto; display:none;" class="desktop-only">
        </div>
        <div class="status-bar" style="flex-direction: column; gap: 5px; margin-top:5px;">
            <div style="display:flex; align-items:center; gap:10px;">
                <span id="main-status-dot" class="status-dot red"></span>
                <span id="main-status-text" class="status-text inactive">SYSTEM START...</span>
            </div>
            <div style="display:flex; align-items:center; gap:10px;">
                <span id="el-status-dot" class="status-dot" style="background-color: #444;"></span>
                <span id="el-status-text" class="status-text" style="color: #666; font-size: 0.85em; font-weight:normal;">EchoLink Init...</span>
            </div>
        </div>
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
    <div id="DTMF" class="tab-content"><?php include 'tab_dtmf.php'; ?></div>
    <div id="Audio" class="tab-content"><?php include 'tab_audio.php'; ?></div>
    <div id="Radio" class="tab-content"><?php include 'tab_radio.php'; ?></div>
    <div id="SvxConfig" class="tab-content"><?php include 'tab_config.php'; ?></div>
    <div id="WiFi" class="tab-content"><?php include 'tab_wifi.php'; ?></div>
    <div id="Power" class="tab-content"><?php include 'tab_power.php'; ?></div>
    <div id="Nodes" class="tab-content"><?php include 'tab_nodes.php'; ?></div>
    <div id="Help" class="tab-content"><?php include 'help.php'; ?></div>
    <div id="Logs" class="tab-content"><div id="log-content" class="log-box">...</div></div>
</div>

<div class="main-footer">
    SvxLink v1.9.99.36@master Copyright (C) 2003-<?php echo date("Y"); ?> Tobias Blomberg / <span class="callsign-blue">SM0SVX</span><br>
    <span class="callsign-blue">SQLink System</span> • SierraEcho & Team Edition<br>
    Copyright &copy; 2025<?php if(date("Y") > 2025) echo "-".date("Y"); ?> by <span class="callsign-blue">SQ7UTP</span>
    <div style="margin-top: 5px; font-size: 9px; opacity: 0.6;"><a href="https://github.com/SQLink-Official/SQLink-Official" target="_blank" style="color: inherit; text-decoration: none;">Source Code (AGPL v3)</a></div>
</div>

<script> 
const GLOBAL_CALLSIGN = "<?php echo $vals['Callsign']; ?>"; 
if(window.innerWidth > 768) {
    document.querySelectorAll('.desktop-only').forEach(el => el.style.display = 'block');
}
</script>
<script src="script.js?v=<?php echo time(); ?>"></script>
</body>
</html>