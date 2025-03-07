<?php

// Function to generate random data
function generateRandomData() {
    $data = [];
    $timestamp = date('Y-m-d H:i:s');  // Current timestamp
    
    // Generate random data for Line 4 (8 sensors)
    $line4Data = ['timestamp' => $timestamp];
    for ($i = 1; $i <= 8; $i++) {
        $line4Data["r0{$i}"] = rand(100, 500);  // Random value between 100 and 500
    }
    $data['line4'] = $line4Data;
    
    // Generate random data for Line 5 (17 sensors)
    $line5Data = ['timestamp' => $timestamp];
    for ($i = 1; $i <= 17; $i++) {
        $sensorLabel = $i < 10 ? "r0{$i}" : "r{$i}";
        $line5Data[$sensorLabel] = rand(100, 500);  // Random value between 100 and 500
    }
    $data['line5'] = $line5Data;
    
    return $data;
}


?>
