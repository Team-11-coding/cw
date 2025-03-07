<?php

@include 'config.php';
@include 'liveDataSim.php';
$viewMode = isset($_GET['view']) ? $_GET['view'] : 'historic';

$sensors = [];   

 
$rowsPerPage = 50;   
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$startIndexLine4 = ($page - 1) * $rowsPerPage;
$startIndexLine5 = ($page - 1) * $rowsPerPage;

// Filter variables
$dayFilter = isset($_GET['day']) ? $_GET['day'] : '';
$hourFilter = isset($_GET['hour']) ? $_GET['hour'] : '';
$minuteFilter = isset($_GET['minute']) ? $_GET['minute'] : '';

//  WHERE clause for filtering
$whereClause = "";
if ($dayFilter) {
    $whereClause .= " AND timestamp LIKE '%$dayFilter%'";
}
if ($hourFilter) {
    $whereClause .= " AND timestamp LIKE '%$hourFilter%'";
}
if ($minuteFilter) {
    $whereClause .= " AND timestamp LIKE '%$minuteFilter%'";
}

 $sqlLine4 = "SELECT * FROM line4_data WHERE 1=1 $whereClause LIMIT $startIndexLine4, $rowsPerPage";
$resultLine4 = $conn->query($sqlLine4);

// Fetch data from the database for line5_data with filtering
$sqlLine5 = "SELECT * FROM line5_data WHERE 1=1 $whereClause LIMIT $startIndexLine5, $rowsPerPage";
$resultLine5 = $conn->query($sqlLine5);

// Check if data is available
$dataLine4 = [];
$dataLine5 = [];

if ($resultLine4->num_rows > 0) {
    while($row = $resultLine4->fetch_assoc()) {
        $dataLine4[] = $row;
    }
}

if ($resultLine5->num_rows > 0) {
    while($row = $resultLine5->fetch_assoc()) {
        $dataLine5[] = $row;
    }
}

 
$sqlTotalLine4 = "SELECT COUNT(*) as total FROM line4_data WHERE 1=1 $whereClause";
$totalResultLine4 = $conn->query($sqlTotalLine4);
$totalRowsLine4 = $totalResultLine4->fetch_assoc()['total'];

 
$sqlTotalLine5 = "SELECT COUNT(*) as total FROM line5_data WHERE 1=1 $whereClause";
$totalResultLine5 = $conn->query($sqlTotalLine5);
$totalRowsLine5 = $totalResultLine5->fetch_assoc()['total'];

 
$totalPagesLine4 = ceil($totalRowsLine4 / $rowsPerPage);
$totalPagesLine5 = ceil($totalRowsLine5 / $rowsPerPage);
$timeFilter = isset($_GET['timeFilter']) ? $_GET['timeFilter'] : '';


?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title>Sensor Dashboard</title>
    <style>
    
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f7fa;
            margin: 0;
            padding: 20px;
            color: #333;
        }

        h1 {
            text-align: center;
            color: #4A90E2;
        }

       
        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

      
        form {
            display: flex;
            justify-content: flex-start;
            margin-bottom: 20px;
        }

        label {
            margin-right: 10px;
            font-weight: bold;
        }

        select, input, button {
            padding: 8px;
            margin-right: 10px;
            font-size: 14px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }

        button {
            background-color: #4A90E2;
            color: white;
            cursor: pointer;
        }

        button:hover {
            background-color: #357ABD;
        }

         .table-container {
            max-height: 450px;   
            overflow-y: auto;
            border-radius: 8px;
            margin-bottom: 30px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            font-size: 14px;
            color: #333;
        }

        th, td {
            padding: 12px;
            text-align: center;
            border: 1px solid #ddd;
        }

        th {
            background-color: #4A90E2;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        tr:hover {
            background-color: #f1f1f1;
        }

        td {
            font-size: 12px;   
        }

       
        .pagination {
            text-align: center;
            margin-top: 20px;
        }

        .pagination a {
            padding: 8px 16px;
            margin: 0 5px;
            text-decoration: none;
            background-color: #4A90E2;
            color: white;
            border-radius: 5px;
        }

        .pagination a.active {
            background-color: #357ABD;
        }

        .pagination a.disabled {
            background-color: #ccc;
            pointer-events: none;
        }

        /* Responsive Design for smaller screens */
        @media screen and (max-width: 768px) {
            table, th, td {
                font-size: 12px;
            }

            form {
                flex-direction: column;
                align-items: flex-start;
            }

            label, select, input, button {
                margin-bottom: 10px;
            }

            .container {
                padding: 15px;
            }
        }
    </style>
</head>
<body>

<div class="container">
    <h1>Sensor Dashboard</h1>

    <!-- Data Type Selection (Live or Historic) -->
    <form method="get">
        <label for="view">Select Data View:</label>
        <select name="view" id="view">
            <option value="historic" <?php echo $viewMode === 'historic' ? 'selected' : ''; ?>>Historic Data</option>
            <option value="live" <?php echo $viewMode === 'live' ? 'selected' : ''; ?>>Live Data</option>
        </select>
        <button type="submit">View</button>
    </form>

    <!-- Filters for Day, Hour, Minute -->
    <form method="get">
        <label for="day">Day (YYYY-MM-DD):</label>
        <input type="text" name="day" id="day" value="<?php echo htmlspecialchars($dayFilter); ?>" placeholder="Enter Day (e.g., 2023-12-23)">
        
        <label for="hour">Hour (HH):</label>
        <input type="text" name="hour" id="hour" value="<?php echo htmlspecialchars($hourFilter); ?>" placeholder="Enter Hour (e.g., 14)">
        
        <label for="minute">Minute (MM):</label>
        <input type="text" name="minute" id="minute" value="<?php echo htmlspecialchars($minuteFilter); ?>" placeholder="Enter Minute (e.g., 30)">
        
        <button type="submit">Filter</button>
    </form>

    <?php if ($viewMode === 'historic'): ?>
        <h2>Line 4 Sensor Data (Historic)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <th>r0<?php echo $i; ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($dataLine4)) {
                        foreach ($dataLine4 as $row) {
                            echo "<tr>";
                            echo "<td>".$row['timestamp']."</td>";
                            for ($i = 1; $i <= 8; $i++) {
                                echo "<td>".$row["r0{$i}"]."</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='9'>No data available for Line 4</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

         <div class="pagination">
            <a href="?view=historic&page=<?php echo max(1, $page - 1); ?>">Previous</a>
            <span>Page <?php echo $page; ?> of <?php echo $totalPagesLine4; ?></span>
            <a href="?view=historic&page=<?php echo min($totalPagesLine4, $page + 1); ?>">Next</a>
        </div>

        <h2>Line 5 Sensor Data (Historic)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <?php 
                        for ($i = 1; $i <= 17; $i++): 
                            $sensorLabel = $i < 10 ? "r0{$i}" : "r{$i}";
                        ?>
                            <th><?php echo $sensorLabel; ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (!empty($dataLine5)) {
                        foreach ($dataLine5 as $row) {
                            echo "<tr>";
                            echo "<td>".$row['timestamp']."</td>";
                            for ($i = 1; $i <= 17; $i++) {
                                $sensorLabel = $i < 10 ? "r0{$i}" : "r{$i}";
                                echo "<td>".$row[$sensorLabel]."</td>";
                            }
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='18'>No data available for Line 5</td></tr>";
                    }
                    ?>
                </tbody>
            </table>
        </div>

         <div class="pagination">
            <a href="?view=historic&page=<?php echo max(1, $page - 1); ?>">Previous</a>
            <span>Page <?php echo $page; ?> of <?php echo $totalPagesLine5; ?></span>
            <a href="?view=historic&page=<?php echo min($totalPagesLine5, $page + 1); ?>">Next</a>
        </div>

    <?php elseif ($viewMode === 'live'): ?>
        <h2>Line 4 Sensor Data (Live)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                            <th>r0<?php echo $i; ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody id="line4-table-body">
                    <?php
                     $liveDataLine4 = generateRandomData()['line4'];
                    echo "<tr><td>".$liveDataLine4['timestamp']."</td>";
                    for ($i = 1; $i <= 8; $i++) {
                        echo "<td>".$liveDataLine4["r0{$i}"]."</td>";
                    }
                    echo "</tr>";
                    ?>
                </tbody>
            </table>
        </div>

        <h2>Line 5 Sensor Data (Live)</h2>
        <div class="table-container">
            <table>
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <?php 
                        for ($i = 1; $i <= 17; $i++): 
                            $sensorLabel = $i < 10 ? "r0{$i}" : "r{$i}";
                        ?>
                            <th><?php echo $sensorLabel; ?></th>
                        <?php endfor; ?>
                    </tr>
                </thead>
                <tbody id="line5-table-body">
                    <?php
                     $liveDataLine5 = generateRandomData()['line5'];
                    echo "<tr><td>".$liveDataLine5['timestamp']."</td>";
                    for ($i = 1; $i <= 17; $i++) {
                        $sensorLabel = $i < 10 ? "r0{$i}" : "r{$i}";
                        echo "<td>".$liveDataLine5[$sensorLabel]."</td>";
                    }
                    echo "</tr>";
                    ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

</div>

<div id="sensor-statistics">
    <h2>Statistics (Last <?php echo $timeFilter; ?> Hours)</h2>
    <table border="1">
        <tr><th>Sensor</th><th>Min</th><th>Max</th><th>Avg</th><th>Median</th><th>Anomalous Count</th></tr>
        <?php foreach ($sensors as $sensor): ?>
            <tr>
                <td><?php echo htmlspecialchars($sensor); ?></td>
                <td><?php echo $stats['min'][$sensor] ?? 'N/A'; ?></td>
                <td><?php echo $stats['max'][$sensor] ?? 'N/A'; ?></td>
                <td><?php echo isset($stats['avg'][$sensor]) ? number_format($stats['avg'][$sensor], 2) : 'N/A'; ?></td>
                <td><?php echo $stats['median'][$sensor] ?? 'N/A'; ?></td>
                <td><?php echo $stats['anomalous'][$sensor] ?? 0; ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<script>
    const maxRows = 20;   

    setInterval(function() {
         fetch('http://127.0.0.1:5000/get_live_data')  
            .then(response => response.json())
            .then(data => {
                updateTable('line4', data.line4);
                updateTable('line5', data.line5);
            })
            .catch(error => console.error('Error fetching live data:', error));
    }, 1000);

    // Update the table with new data
    function updateTable(line, rowData) {
        const tableBody = document.getElementById(`${line}-table-body`);
        const newRow = createNewRow(line, rowData);
        tableBody.insertBefore(newRow, tableBody.firstChild);
        
         if (tableBody.rows.length > maxRows) {
            tableBody.deleteRow(tableBody.rows.length - 1);
        }
    }

     function createNewRow(line, rowData) {
        const newRow = document.createElement('tr');
        newRow.classList.add('new-row-highlight');  // Highlight new row

        // Create timestamp cell
        const timestampCell = document.createElement('td');
        timestampCell.textContent = rowData.timestamp;
        newRow.appendChild(timestampCell);

        // Create sensor data cells
        for (let i = 1; i <= (line === 'line4' ? 8 : 17); i++) {
            const cell = document.createElement('td');
            const sensorLabel = i < 10 ? `r0${i}` : `r${i}`;
            cell.textContent = rowData[sensorLabel];
            newRow.appendChild(cell);
        }

         setTimeout(() => {
            newRow.classList.remove('new-row-highlight');
        }, 1000);

        return newRow;
    }

    //  (for testing)
    function generateRandomData() {
        return {
            line4: {
                timestamp: new Date().toISOString(),
                r01: Math.random() * 10 + 20,
                r02: Math.random() * 10 + 20,
                r03: Math.random() * 10 + 20,
                r04: Math.random() * 10 + 20,
                r05: Math.random() * 10 + 20,
                r06: Math.random() * 10 + 20,
                r07: Math.random() * 10 + 20,
                r08: Math.random() * 10 + 20,
            },
            line5: {
                timestamp: new Date().toISOString(),
                r01: Math.random() * 10 + 20,
                r02: Math.random() * 10 + 20,
                r03: Math.random() * 10 + 20,
                r04: Math.random() * 10 + 20,
                r05: Math.random() * 10 + 20,
                r06: Math.random() * 10 + 20,
                r07: Math.random() * 10 + 20,
                r08: Math.random() * 10 + 20,
                r09: Math.random() * 10 + 20,
                r10: Math.random() * 10 + 20,
                r11: Math.random() * 10 + 20,
                r12: Math.random() * 10 + 20,
                r13: Math.random() * 10 + 20,
                r14: Math.random() * 10 + 20,
                r15: Math.random() * 10 + 20,
                r16: Math.random() * 10 + 20,
                r17: Math.random() * 10 + 20,
            }
        };
    }

    function createChart(canvasId, sensors) {
        const ctx = document.getElementById(canvasId).getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($sensorData, 'timestamp')); ?>,
                datasets: sensors.map(sensor => ({
                    label: sensor,
                    data: <?php echo json_encode(array_map(fn($row) => $row[sensor] ?? null, $sensorData)); ?>,
                    borderColor: 'rgba(75, 192, 192, 1)',
                    fill: false
                }))
            },
            options: { responsive: true }
        });
    }
</script>

</body>
</html>
