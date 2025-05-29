<?php
require_once '../php/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: ../login.html");
    exit;
}

// Get dashboard statistics
$stats = array();

// Total donors
$query = "SELECT COUNT(*) as total_donors FROM donors";
$result = $conn->query($query);
$stats['total_donors'] = $result->fetch_assoc()['total_donors'];

// Total recipients
$query = "SELECT COUNT(*) as total_recipients FROM recipients";
$result = $conn->query($query);
$stats['total_recipients'] = $result->fetch_assoc()['total_recipients'];

// Total blood donations
$query = "SELECT COUNT(*) as total_donations FROM blood_donations";
$result = $conn->query($query);
$stats['total_donations'] = $result->fetch_assoc()['total_donations'];

// Total blood requests
$query = "SELECT COUNT(*) as total_requests FROM blood_requests";
$result = $conn->query($query);
$stats['total_requests'] = $result->fetch_assoc()['total_requests'];

// Pending blood requests
$query = "SELECT COUNT(*) as pending_requests FROM blood_requests WHERE status = 'Pending'";
$result = $conn->query($query);
$stats['pending_requests'] = $result->fetch_assoc()['pending_requests'];

// Get blood stock
$query = "SELECT bg.group_name, bs.units_available 
          FROM blood_stock bs 
          JOIN blood_groups bg ON bs.blood_group_id = bg.group_id 
          ORDER BY bg.group_name";
$result = $conn->query($query);
$bloodStock = array();
while ($row = $result->fetch_assoc()) {
    $bloodStock[] = $row;
}

// Get recent blood requests
$query = "SELECT br.request_id, r.first_name, r.last_name, bg.group_name, br.units_required, 
          br.request_date, br.required_date, br.status 
          FROM blood_requests br 
          JOIN recipients r ON br.recipient_id = r.recipient_id 
          JOIN blood_groups bg ON br.blood_group_id = bg.group_id 
          ORDER BY br.request_date DESC 
          LIMIT 5";
$result = $conn->query($query);
$recentRequests = array();
while ($row = $result->fetch_assoc()) {
    $recentRequests[] = $row;
}

// Get recent blood donations
$query = "SELECT bd.donation_id, d.first_name, d.last_name, bg.group_name, bd.units_donated, 
          bd.donation_date, bd.status 
          FROM blood_donations bd 
          JOIN donors d ON bd.donor_id = d.donor_id 
          JOIN blood_groups bg ON bd.blood_group_id = bg.group_id 
          ORDER BY bd.donation_date DESC 
          LIMIT 5";
$result = $conn->query($query);
$recentDonations = array();
while ($row = $result->fetch_assoc()) {
    $recentDonations[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blood Bank Management System</title>
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
        
        .stats-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            flex: 1;
            min-width: 150px;
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        
        .stat-card h3 {
            margin-bottom: 5px;
            color: #e74c3c;
        }
        
        .stat-card p {
            font-size: 1.5em;
            font-weight: bold;
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
                <h2>Dashboard</h2>
                
                <section class="stats-container">
                    <div class="stat-card">
                        <h3>Total Donors</h3>
                        <p><?php echo $stats['total_donors']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Recipients</h3>
                        <p><?php echo $stats['total_recipients']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Donations</h3>
                        <p><?php echo $stats['total_donations']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Total Requests</h3>
                        <p><?php echo $stats['total_requests']; ?></p>
                    </div>
                    
                    <div class="stat-card">
                        <h3>Pending Requests</h3>
                        <p><?php echo $stats['pending_requests']; ?></p>
                    </div>
                </section>
                
                <section>
                    <h3>Blood Stock</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Blood Group</th>
                                <th>Units Available</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bloodStock as $stock): ?>
                            <tr>
                                <td><?php echo $stock['group_name']; ?></td>
                                <td><?php echo $stock['units_available']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </section>
                
                <section>
                    <h3>Recent Blood Requests</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Recipient</th>
                                <th>Blood Group</th>
                                <th>Units</th>
                                <th>Request Date</th>
                                <th>Required Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentRequests as $request): ?>
                            <tr>
                                <td><?php echo $request['request_id']; ?></td>
                                <td><?php echo $request['first_name'] . ' ' . $request['last_name']; ?></td>
                                <td><?php echo $request['group_name']; ?></td>
                                <td><?php echo $request['units_required']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($request['request_date'])); ?></td>
                                <td><?php echo $request['required_date']; ?></td>
                                <td><?php echo $request['status']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><a href="manage_requests.php">View All Requests</a></p>
                </section>
                
                <section>
                    <h3>Recent Blood Donations</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Donor</th>
                                <th>Blood Group</th>
                                <th>Units</th>
                                <th>Donation Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentDonations as $donation): ?>
                            <tr>
                                <td><?php echo $donation['donation_id']; ?></td>
                                <td><?php echo $donation['first_name'] . ' ' . $donation['last_name']; ?></td>
                                <td><?php echo $donation['group_name']; ?></td>
                                <td><?php echo $donation['units_donated']; ?></td>
                                <td><?php echo $donation['donation_date']; ?></td>
                                <td><?php echo $donation['status']; ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <p><a href="manage_donations.php">View All Donations</a></p>
                </section>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Blood Bank Management System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
