<?php
require_once '../php/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: ../login.html");
    exit;
}

// Handle stock update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_stock'])) {
    foreach ($_POST['units'] as $stockId => $units) {
        $stockId = (int)$stockId;
        $units = (int)$units;
        
        if ($units < 0) {
            $units = 0;
        }
        
        $updateQuery = "UPDATE blood_stock SET units_available = ? WHERE stock_id = ?";
        $stmt = $conn->prepare($updateQuery);
        $stmt->bind_param("ii", $units, $stockId);
        $stmt->execute();
    }
    
    // Redirect to refresh the page
    header("Location: manage_stock.php?updated=1");
    exit;
}

// Get blood stock data
$query = "SELECT bs.stock_id, bg.group_id, bg.group_name, bs.units_available, bs.last_updated 
          FROM blood_stock bs 
          JOIN blood_groups bg ON bs.blood_group_id = bg.group_id 
          ORDER BY bg.group_name";
$result = $conn->query($query);
$bloodStock = array();
while ($row = $result->fetch_assoc()) {
    $bloodStock[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blood Stock - Blood Bank Management System</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .dashboard-container {
            display: flex;
            flex-wrap: wrap;
        }
        
        .dashboard-sidebar {
            flex: 1;
            min-width: 200px;
            background-color: #f4f4f4;
            padding: 20px;
            border-radius: 5px;
            margin-right: 20px;
        }
        
        .dashboard-content {
            flex: 3;
            min-width: 300px;
        }
        
        .dashboard-menu {
            list-style: none;
            padding: 0;
        }
        
        .dashboard-menu li {
            margin-bottom: 10px;
        }
        
        .dashboard-menu a {
            display: block;
            padding: 10px;
            background-color: #e74c3c;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            transition: background-color 0.3s;
        }
        
        .dashboard-menu a:hover {
            background-color: #c0392b;
        }
        
        .stock-form input[type="number"] {
            width: 80px;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .critical {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .low {
            color: #f39c12;
            font-weight: bold;
        }
        
        .normal {
            color: #2ecc71;
        }
        
        .user-info {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Blood Bank Management System</h1>
            <div class="user-info">
                <p>Welcome, <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</p>
                <p><a href="logout.php">Logout</a></p>
            </div>
        </header>
        
        <main class="dashboard-container">
            <aside class="dashboard-sidebar">
                <h2>Admin Menu</h2>
                <ul class="dashboard-menu">
                    <li><a href="dashboard.php">Dashboard</a></li>
                    <li><a href="manage_donors.php">Manage Donors</a></li>
                    <li><a href="manage_recipients.php">Manage Recipients</a></li>
                    <li><a href="manage_donations.php">Manage Donations</a></li>
                    <li><a href="manage_requests.php">Manage Requests</a></li>
                    <li><a href="manage_stock.php">Manage Blood Stock</a></li>
                    <?php if (is_admin()): ?>
                    <li><a href="manage_users.php">Manage Users</a></li>
                    <?php endif; ?>
                </ul>
            </aside>
            
            <div class="dashboard-content">
                <h2>Manage Blood Stock</h2>
                
                <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
                <div class="success-message">
                    Blood stock updated successfully!
                </div>
                <?php endif; ?>
                
                <form class="stock-form" method="POST" action="">
                    <table>
                        <thead>
                            <tr>
                                <th>Blood Group</th>
                                <th>Units Available</th>
                                <th>Status</th>
                                <th>Last Updated</th>
                                <th>New Units</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloodStock as $stock): ?>
                                <?php 
                                    $statusClass = 'normal';
                                    $statusText = 'Normal';
                                    
                                    if ($stock['units_available'] < 5) {
                                        $statusClass = 'critical';
                                        $statusText = 'Critical';
                                    } else if ($stock['units_available'] < 10) {
                                        $statusClass = 'low';
                                        $statusText = 'Low';
                                    }
                                ?>
                                <tr>
                                    <td><?php echo $stock['group_name']; ?></td>
                                    <td><?php echo $stock['units_available']; ?></td>
                                    <td class="<?php echo $statusClass; ?>"><?php echo $statusText; ?></td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($stock['last_updated'])); ?></td>
                                    <td>
                                        <input type="number" name="units[<?php echo $stock['stock_id']; ?>]" value="<?php echo $stock['units_available']; ?>" min="0">
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    
                    <div style="margin-top: 20px;">
                        <button type="submit" name="update_stock" class="btn btn-primary">Update Stock</button>
                    </div>
                </form>
                
                <div style="margin-top: 30px;">
                    <h3>Blood Stock Status</h3>
                    <div class="status-indicators">
                        <div class="status-item">
                            <span class="status-dot critical"></span>
                            <span>Critical (Less than 5 units)</span>
                        </div>
                        <div class="status-item">
                            <span class="status-dot low"></span>
                            <span>Low (5-10 units)</span>
                        </div>
                        <div class="status-item">
                            <span class="status-dot normal"></span>
                            <span>Normal (More than 10 units)</span>
                        </div>
                    </div>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Blood Bank Management System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
