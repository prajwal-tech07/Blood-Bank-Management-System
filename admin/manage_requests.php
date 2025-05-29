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
    $searchCondition = " AND (r.first_name LIKE '%$search%' OR r.last_name LIKE '%$search%' OR br.hospital_name LIKE '%$search%')";
}

// Handle status filter
$status = isset($_GET['status']) ? sanitize_input($_GET['status']) : '';
$statusCondition = '';
if (!empty($status)) {
    $statusCondition = " AND br.status = '$status'";
}

// Get requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$query = "SELECT br.request_id, r.recipient_id, r.first_name, r.last_name, bg.group_name, 
          br.units_required, br.request_date, br.required_date, br.status, br.hospital_name 
          FROM blood_requests br 
          JOIN recipients r ON br.recipient_id = r.recipient_id 
          JOIN blood_groups bg ON br.blood_group_id = bg.group_id 
          WHERE 1=1 $searchCondition $statusCondition 
          ORDER BY br.request_date DESC 
          LIMIT $offset, $recordsPerPage";
$result = $conn->query($query);

// Get total number of requests for pagination
$countQuery = "SELECT COUNT(*) as total FROM blood_requests br 
               JOIN recipients r ON br.recipient_id = r.recipient_id 
               WHERE 1=1 $searchCondition $statusCondition";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Process request status update
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['update_status'])) {
    $requestId = (int)$_POST['request_id'];
    $newStatus = sanitize_input($_POST['new_status']);
    
    // Get request details
    $requestQuery = "SELECT br.blood_group_id, br.units_required, bs.units_available 
                    FROM blood_requests br 
                    JOIN blood_stock bs ON br.blood_group_id = bs.blood_group_id 
                    WHERE br.request_id = ?";
    $stmt = $conn->prepare($requestQuery);
    $stmt->bind_param("i", $requestId);
    $stmt->execute();
    $requestResult = $stmt->get_result();
    $request = $requestResult->fetch_assoc();
    
    // Check if we have enough blood units for fulfillment
    if ($newStatus == 'Fulfilled' && $request['units_required'] > $request['units_available']) {
        echo "<script>
            alert('Not enough blood units available to fulfill this request.');
            window.location.href = 'manage_requests.php';
        </script>";
        exit;
    }
    
    // Update request status
    $updateQuery = "UPDATE blood_requests SET status = ? WHERE request_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("si", $newStatus, $requestId);
    
    if ($stmt->execute()) {
        // If fulfilled, update blood stock
        if ($newStatus == 'Fulfilled') {
            $updateStockQuery = "UPDATE blood_stock 
                                SET units_available = units_available - ? 
                                WHERE blood_group_id = ?";
            $stmt = $conn->prepare($updateStockQuery);
            $stmt->bind_param("ii", $request['units_required'], $request['blood_group_id']);
            $stmt->execute();
            
            // Record transfusion
            $transfusionQuery = "INSERT INTO blood_transfusions (request_id, transfusion_date, units_transfused) 
                                VALUES (?, CURDATE(), ?)";
            $stmt = $conn->prepare($transfusionQuery);
            $stmt->bind_param("ii", $requestId, $request['units_required']);
            $stmt->execute();
        }
        
        // Redirect to refresh the page
        header("Location: manage_requests.php?updated=1");
        exit;
    } else {
        echo "<script>
            alert('Error updating request status: " . $stmt->error . "');
            window.location.href = 'manage_requests.php';
        </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Blood Requests - Blood Bank Management System</title>
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
        
        .status-fulfilled {
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
        
        .status-approved {
            color: #3498db;
            font-weight: bold;
        }
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
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
                <h2>Manage Blood Requests</h2>
                
                <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
                <div class="success-message">
                    Blood request status updated successfully!
                </div>
                <?php endif; ?>
                
                <div class="filters">
                    <div class="search-container">
                        <form action="" method="GET">
                            <?php if (!empty($status)): ?>
                            <input type="hidden" name="status" value="<?php echo $status; ?>">
                            <?php endif; ?>
                            <input type="text" name="search" placeholder="Search by recipient name or hospital..." value="<?php echo $search; ?>">
                            <button type="submit">Search</button>
                        </form>
                    </div>
                    
                    <div class="status-filter">
                        <label for="status">Status:</label>
                        <select id="status" onchange="window.location.href='?status='+this.value+'<?php echo !empty($search) ? '&search='.$search : ''; ?>'">
                            <option value="">All</option>
                            <option value="Pending" <?php echo $status == 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Approved" <?php echo $status == 'Approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="Fulfilled" <?php echo $status == 'Fulfilled' ? 'selected' : ''; ?>>Fulfilled</option>
                            <option value="Rejected" <?php echo $status == 'Rejected' ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Recipient</th>
                            <th>Blood Group</th>
                            <th>Units</th>
                            <th>Request Date</th>
                            <th>Required Date</th>
                            <th>Hospital</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($request = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $request['request_id']; ?></td>
                                    <td>
                                        <a href="view_recipient.php?id=<?php echo $request['recipient_id']; ?>">
                                            <?php echo $request['first_name'] . ' ' . $request['last_name']; ?>
                                        </a>
                                    </td>
                                    <td><?php echo $request['group_name']; ?></td>
                                    <td><?php echo $request['units_required']; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($request['request_date'])); ?></td>
                                    <td><?php echo $request['required_date']; ?></td>
                                    <td><?php echo $request['hospital_name']; ?></td>
                                    <td class="status-<?php echo strtolower($request['status']); ?>"><?php echo $request['status']; ?></td>
                                    <td>
                                        <a href="view_request.php?id=<?php echo $request['request_id']; ?>">View</a> | 
                                        
                                        <?php if ($request['status'] == 'Pending'): ?>
                                        <a href="#" onclick="updateStatus(<?php echo $request['request_id']; ?>, 'Approved')">Approve</a> | 
                                        <a href="#" onclick="updateStatus(<?php echo $request['request_id']; ?>, 'Rejected')">Reject</a>
                                        <?php elseif ($request['status'] == 'Approved'): ?>
                                        <a href="#" onclick="updateStatus(<?php echo $request['request_id']; ?>, 'Fulfilled')">Fulfill</a> | 
                                        <a href="#" onclick="updateStatus(<?php echo $request['request_id']; ?>, 'Rejected')">Reject</a>
                                        <?php else: ?>
                                        <span>No actions</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">No blood requests found.</td>
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
                    <p>Total Requests: <?php echo $totalRecords; ?></p>
                    <a href="new_blood_request.php" class="btn btn-primary">Create New Request</a>
                </div>
                
                <!-- Hidden form for status updates -->
                <form id="statusForm" method="POST" style="display: none;">
                    <input type="hidden" name="request_id" id="request_id">
                    <input type="hidden" name="new_status" id="new_status">
                    <input type="hidden" name="update_status" value="1">
                </form>
                
                <script>
                    function updateStatus(requestId, status) {
                        if (confirm('Are you sure you want to change the status to ' + status + '?')) {
                            document.getElementById('request_id').value = requestId;
                            document.getElementById('new_status').value = status;
                            document.getElementById('statusForm').submit();
                        }
                    }
                </script>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Blood Bank Management System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
