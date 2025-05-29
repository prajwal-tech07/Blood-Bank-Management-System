<?php
require_once '../php/config.php';

// Check if user is logged in and is admin
if (!is_logged_in() || !is_admin()) {
    header("Location: ../login.html");
    exit;
}

// Handle search query
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$searchCondition = '';
if (!empty($search)) {
    $searchCondition = " WHERE username LIKE '%$search%' OR full_name LIKE '%$search%' OR email LIKE '%$search%'";
}

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = 10;
$offset = ($page - 1) * $recordsPerPage;

$query = "SELECT user_id, username, role, full_name, email, is_active, last_login, created_at 
          FROM users 
          $searchCondition 
          ORDER BY user_id 
          LIMIT $offset, $recordsPerPage";
$result = $conn->query($query);

// Get total number of users for pagination
$countQuery = "SELECT COUNT(*) as total FROM users $searchCondition";
$countResult = $conn->query($countQuery);
$totalRecords = $countResult->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $recordsPerPage);

// Handle user status toggle
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['toggle_status'])) {
    $userId = (int)$_POST['user_id'];
    $newStatus = $_POST['is_active'] == '1' ? 0 : 1;
    
    // Don't allow deactivating your own account
    if ($userId == $_SESSION['user_id']) {
        echo "<script>
            alert('You cannot deactivate your own account.');
            window.location.href = 'manage_users.php';
        </script>";
        exit;
    }
    
    $updateQuery = "UPDATE users SET is_active = ? WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param("ii", $newStatus, $userId);
    
    if ($stmt->execute()) {
        header("Location: manage_users.php?updated=1");
        exit;
    } else {
        echo "<script>
            alert('Error updating user status: " . $stmt->error . "');
            window.location.href = 'manage_users.php';
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
    <title>Manage Users - Blood Bank Management System</title>
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
        
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        
        .status-active {
            color: #2ecc71;
            font-weight: bold;
        }
        
        .status-inactive {
            color: #e74c3c;
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
                    <li><a href="manage_users.php">Manage Users</a></li>
                </ul>
            </aside>
            
            <div class="dashboard-content">
                <h2>Manage Users</h2>
                
                <?php if (isset($_GET['updated']) && $_GET['updated'] == 1): ?>
                <div class="success-message">
                    User status updated successfully!
                </div>
                <?php endif; ?>
                
                <div class="search-container">
                    <form action="" method="GET">
                        <input type="text" name="search" placeholder="Search by username, name or email..." value="<?php echo $search; ?>">
                        <button type="submit">Search</button>
                    </form>
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Last Login</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($user = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo $user['user_id']; ?></td>
                                    <td><?php echo $user['username']; ?></td>
                                    <td><?php echo $user['full_name']; ?></td>
                                    <td><?php echo $user['email']; ?></td>
                                    <td><?php echo $user['role']; ?></td>
                                    <td class="<?php echo $user['is_active'] ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </td>
                                    <td><?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <a href="edit_user.php?id=<?php echo $user['user_id']; ?>">Edit</a> | 
                                        
                                        <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                            <input type="hidden" name="is_active" value="<?php echo $user['is_active']; ?>">
                                            <input type="hidden" name="toggle_status" value="1">
                                            <button type="submit" style="background: none; border: none; color: blue; text-decoration: underline; cursor: pointer;">
                                                <?php echo $user['is_active'] ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <?php else: ?>
                                        <span>Current User</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9">No users found.</td>
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
                    <p>Total Users: <?php echo $totalRecords; ?></p>
                    <a href="add_user.php" class="btn btn-primary">Add New User</a>
                </div>
            </div>
        </main>
        
        <footer>
            <p>&copy; 2025 Blood Bank Management System. All rights reserved.</p>
        </footer>
    </div>
</body>
</html>
