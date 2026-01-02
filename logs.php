<?php

$logFile = '/var/www/html/svx_events.log';

if (file_exists($logFile)) {

    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines !== false) {
        
        $lines = array_unique($lines);
        $lines = array_reverse($lines);
        
        $output = array_slice($lines, 0, 60);
        
        $output = array_reverse($output);
        
        echo implode("\n", $output);
    } else {
        echo "Log pusty.";
    }
} else {
    echo "Oczekiwanie na dane z loggera...";
}
?>