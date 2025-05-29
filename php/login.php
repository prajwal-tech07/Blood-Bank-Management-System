<?php
require_once 'config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get input data
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password']; // Don't sanitize password before verification
    
    // Query to get user data
    $query = "SELECT user_id, username, password, role, full_name FROM users WHERE username = ? AND is_active = 1";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 1) {
        $user = $result->fetch_assoc();
        
        // Verify password (assuming password is hashed in the database)
        if (password_verify($password, $user['password'])) {
            // Password is correct, start a new session
            session_start();
            
            // Store user data in session variables
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['full_name'] = $user['full_name'];
            
            // Update last login time
            $updateQuery = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
            $stmt = $conn->prepare($updateQuery);
            $stmt->bind_param("i", $user['user_id']);
            $stmt->execute();
            
            // Redirect to admin dashboard
            header("Location: ../admin/dashboard.php");
            exit;
        } else {
            // Password is incorrect
            echo "<script>
                alert('Invalid username or password.');
                window.location.href = '../login.html';
            </script>";
        }
    } else {
        // User not found
        echo "<script>
            alert('Invalid username or password.');
            window.location.href = '../login.html';
        </script>";
    }
    
    $stmt->close();
} else {
    // Redirect if accessed directly without form submission
    header("Location: ../login.html");
    exit;
}

$conn->close();
?>
