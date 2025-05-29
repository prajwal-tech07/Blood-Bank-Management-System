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
    $searchCondition = " WHERE r.first_name LIKE '%$search%' OR r.last_name LIKE '%$search%' OR r.email LIKE '%$search%' OR r.contact_number LIKE '%$search%'";
}

// Get recipients with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$query = "SELECT r.recipient_id, r.first_name, r.last_name, r.gender, r.date_of_birth, 
          bg.group_name, r.contact_number, r.email, r.city 
          FROM recipients r 
          JOIN blood_groups bg ON r.blood_group_id = bg.group_id 
          $searchCondition 
          ORDER BY r.recipient_id DESC 
          LIMIT $offset, $recordsPerPage";
$result = $conn->query($query);

// Get total number of recipients for pagination
$countQuery = "SELECT COUNT(*) as total FROM recipients r $searchCondition";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Recipients - Blood Bank Management System</title>
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
        
        .search-container {
            margin-bottom: 20px;
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
                <h2>Manage Recipients</h2>
                
                <div class="search-container">
                    <form action="" method="GET">
                        <input type="text" name="search" placeholder="Search by name, email or contact..." value="<?php echo $search; ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Age</th>
                            <th>Blood Group</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>City</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($recipient = $result->fetch_assoc()): ?>
                                <?php 
                                    // Calculate age
                                    $dob = new DateTime($recipient['date_of_birth']);
                                    $today = new DateTime();
                                    $age = $today->diff($dob)->y;
                                ?>
                                <tr>
                                    <td><?php echo $recipient['recipient_id']; ?></td>
                                    <td><?php echo $recipient['first_name'] . ' ' . $recipient['last_name']; ?></td>
                                    <td><?php echo $recipient['gender']; ?></td>
                                    <td><?php echo $age; ?></td>
                                    <td><?php echo $recipient['group_name']; ?></td>
                                    <td><?php echo $recipient['contact_number']; ?></td>
                                    <td><?php echo $recipient['email'] ? $recipient['email'] : 'N/A'; ?></td>
                                    <td><?php echo $recipient['city']; ?></td>
                                    <td>
                                        <a href="view_recipient.php?id=<?php echo $recipient['recipient_id']; ?>">View</a> | 
                                        <a href="edit_recipient.php?id=<?php echo $recipient['recipient_id']; ?>">Edit</a> | 
                                        <a href="new_blood_request.php?recipient_id=<?php echo $recipient['recipient_id']; ?>">New Request</a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">No recipients found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <div class="pagination">
                    <?php if ($totalPages > 1): ?>
                        <?php if ($page > 1): ?>
                            <a href="?page=1<?php echo !empty($search) ? '&search='.$search : ''; ?>">&laquo; First</a>
                            <a href="?page=<?php echo $page-1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">&lsaquo; Prev</a>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page-2); $i <= min($page+2, $totalPages); $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>"><?php echo $i; ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page+1; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">Next &rsaquo;</a>
                            <a href="?page=<?php echo $totalPages; ?><?php echo !empty($search) ? '&search='.$search : ''; ?>">Last &raquo;</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                
                <div>
                    <p>Total Recipients: <?php echo $totalRecords; ?></p>
                    <a href="add_recipient.php" class="btn btn-primary">Add New Recipient</a>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Blood Bank Management System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
