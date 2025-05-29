<?php
require_once 'config.php';

// Query to get blood stock data with group names
$query = "SELECT bs.stock_id, bg.group_name, bs.units_available, bs.last_updated 
          FROM blood_stock bs 
          JOIN blood_groups bg ON bs.blood_group_id = bg.group_id 
          ORDER BY bg.group_name";

$result = $conn->query($query);

if ($result) {
    $bloodStock = array();
    
    while ($row = $result->fetch_assoc()) {
        $bloodStock[] = $row;
    }
    
    // Return data as JSON
    header('Content-Type: application/json');
    echo json_encode($bloodStock);
} else {
    // Return error message
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(array('error' => 'Failed to fetch blood stock data.'));
}

$conn->close();
?>
