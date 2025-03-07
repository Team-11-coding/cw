<?php
@include 'config.php';

// Function to create tables if they don't exist
function createTableIfNotExists($conn, $table, $numSensors, $sensorPrefix) {
    $columns = ["id INT AUTO_INCREMENT PRIMARY KEY", "timestamp DATETIME NOT NULL"];
    
    for ($i = 1; $i <= $numSensors; $i++) {
        $sensorCol = "{$sensorPrefix}" . str_pad($i, 2, "0", STR_PAD_LEFT);
        $columns[] = "$sensorCol FLOAT";
    }

    $sql = "CREATE TABLE IF NOT EXISTS $table (" . implode(", ", $columns) . ")";
    
    if ($conn->query($sql) === FALSE) {
        echo "Error creating table $table: " . $conn->error;
    }
}

// Create tables for Line 4 and Line 5
createTableIfNotExists($conn, "line4_data", 8, 'r');
createTableIfNotExists($conn, "line5_data", 17, 'r');

// Batch size  
$batchSize = 1000;
$batchData = [];

function processCSV($filename, $table, &$batchData, $conn, $batchSize, $numSensors, $sensorPrefix) {
    $file = fopen($filename, "r");

    while (($data = fgetcsv($file, 1000, ",")) !== FALSE) {
        $timestamp = mysqli_real_escape_string($conn, $data[0]);

        $sensorValues = [];
        for ($i = 1; $i <= $numSensors; $i++) {
            $sensorValues[] = isset($data[$i]) ? mysqli_real_escape_string($conn, $data[$i]) : "NULL";
        }

        // Add the row to the batch
        $batchData[] = "('$timestamp', " . implode(", ", $sensorValues) . ")";

        // If the batch is full, insert it into the database
        if (count($batchData) >= $batchSize) {
            insertBatch($conn, $table, $batchData, $numSensors, $sensorPrefix);
        }
    }

    // Insert remaining rows if any
    if (!empty($batchData)) {
        insertBatch($conn, $table, $batchData, $numSensors, $sensorPrefix);
    }

    fclose($file);
}

// Function to insert batch data
function insertBatch($conn, $table, &$batchData, $numSensors, $sensorPrefix) {
    $sensorColumns = [];
    for ($i = 1; $i <= $numSensors; $i++) {
        $sensorColumns[] = "{$sensorPrefix}" . str_pad($i, 2, "0", STR_PAD_LEFT);
    }

    $sqlBatch = "INSERT INTO $table (timestamp, " . implode(", ", $sensorColumns) . ") 
                 VALUES " . implode(", ", $batchData);

    if ($conn->query($sqlBatch) === FALSE) {
        echo "Error: " . $sqlBatch . "<br>" . $conn->error;
    }

     $batchData = [];
}

 
processCSV("data/line4.csv", "line4_data", $batchData, $conn, $batchSize, 8, 'r');
processCSV("data/line5.csv", "line5_data", $batchData, $conn, $batchSize, 17, 'r');

$conn->close();
?>
