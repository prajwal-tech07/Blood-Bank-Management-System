<?php
require_once '../php/config.php';

// Check if user is logged in
if (!is_logged_in()) {
    header("Location: ../login.html");
    exit;
}

// Handle search query
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " AND (d.first_name LIKE '%$search%' OR d.last_name LIKE '%$search%' OR bd.donation_id LIKE '%$search%')";
}

// Handle status filter
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$statusCondition = '';
if (!empty($status)) {
    $statusCondition = " AND bd.status = '$status'";
}

// Get donations with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$query = "SELECT bd.donation_id, d.donor_id, d.first_name, d.last_name, bg.group_name, 
          bd.donation_date, bd.units_donated, bd.status, bd.hemoglobin_level, bd.blood_pressure 
          FROM blood_donations bd 
          JOIN donors d ON bd.donor_id = d.donor_id 
          JOIN blood_groups bg ON bd.blood_group_id = bg.group_id 
          WHERE 1=1 $searchCondition $statusCondition 
          ORDER BY bd.donation_date DESC 
          LIMIT $offset, $recordsPerPage";
$result = $conn->query($query);

// Get total number of donations for pagination
$countQuery = "SELECT COUNT(*) as total FROM blood_donations bd 
               JOIN donors d ON bd.donor_id = d.donor_id 
               WHERE 1=1 $searchCondition $statusCondition";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Donations - Blood Bank Management System</title>
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
        
        .filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }
        
        .search-container {
            flex: 1;
        }
        
        .search-container form {
            display: flex;
        }
        
        .search-container input[type="text"] {
            flex: 1;
            padding: 10px;
        }
        
        .search-container button {
            padding: 10px 20px;
            background-color: #e74c3c;
            color: white;
            border: none;
            cursor: pointer;
        }
        
        .status-filter {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 5px 10px;
            margin: 0 5px;
            border: 1px solid #ddd;
            background-color: #f4f4f4;
            text-decoration: none;
            color: #333;
        }
        
        .pagination .active {
            background-color: #e74c3c;
            color: white;
            border-color: #e74c3c;
        }
        
        .user-info {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #ddd;
        }
        
        .status-approved {
            color: #2ecc71;
            font-weight: bold;
        }
        
        .status-rejected {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .status-pending {
            color: #f39c12;
            font-weight: bold;
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
                <h2>Manage Blood Donations</h2>
                
                <div class="filters">
                    <div class="search-container">
                        <form action="" method="GET">
                            <?php if (!empty($status)): ?>
                            <input type="hidden" name="status" value="<?php echo $status; ?>">
                            <?php endif; ?>
                            <input type="text" name="search" placeholder="Search by donor name or donation ID..." value="<?php echo $search; ?>">
                            <button type="submit">Search</button>
                        </form>
                    </div>
                    
                    <div class="status-filter">
                        <label for="status">Status:</label>
                        <select id="status" onchange="window.location.href='?status='+this.value+'<?php echo !empty($search) ? '&search='.$search : ''; ?>'">
                            <option value="">All</option>
                            <option value="Approved" <?php echo $status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Rejected" <?php echo $status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                            <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                        </select>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Donor</th>
                            <th>Blood Group</th>
                            <th>Donation Date</th>
                            <th>Units</th>
                            <th>Hemoglobin</th>
                            <th>Blood Pressure</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($donation = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $donation['donation_id']; ?></td>
                                    <td>
                                        <a href="view_donor.php?id=<?php echo $donation['donor_id']; ?>">
                                            <?php echo $donation['first_name'] . ' ' . $donation['last_name']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $donation['group_name']; ?></td>
                                    <td><?php echo $donation['donation_date']; ?></td>
                                    <td><?php echo $donation['units_donated']; ?></td>
                                    <td><?php echo $donation['hemoglobin_level'] ? $donation['hemoglobin_level'] . ' g/dL' : 'N/A'; ?></td>
                                    <td><?php echo $donation['blood_pressure'] ? $donation['blood_pressure'] : 'N/A'; ?></td>
                                    <td class="status-<?php echo strtolower($donation['status']); ?>"><?php echo $donation['status']; ?></td>
                                    <td>
                                        <a href="view_donation.php?id=<?php echo $donation['donation_id']; ?>">View</a> | 
                                        <a href="edit_donation.php?id=<?php echo $donation['donation_id']; ?>">Edit</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">No donations found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($totalPages > 1): ?>
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo !empty($status) ? '&status='.$status : ''; ?>">&laquo; First</a>
                            <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo !empty($status) ? '&status='.$status : ''; ?>">&lsaquo; Prev</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($page+2, $totalPages); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo !empty($status) ? '&status='.$status : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo !empty($status) ? '&status='.$status : ''; ?>">Next &rsaquo;</a>
                            <a href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search='.$search : ''; ?><?php echo !empty($status) ? '&status='.$status : ''; ?>">Last &raquo;</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div>
                    <p>Total Donations: <?php echo $totalRecords; ?></p>
                    <a href="record_donation.php" class="btn btn-primary">Record New Donation</a>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Blood Bank Management System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
