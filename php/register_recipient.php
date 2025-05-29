<?php
require_once 'config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get input data
    $firstName = sanitize_input($_POST['firstName']);
    $lastName = sanitize_input($_POST['lastName']);
    $gender = sanitize_input($_POST['gender']);
    $dob = sanitize_input($_POST['dob']);
    $bloodGroup = sanitize_input($_POST['bloodGroup']);
    $contact = sanitize_input($_POST['contact']);
    $email = !empty($_POST['email']) ? sanitize_input($_POST['email']) : NULL;
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $medicalHistory = !empty($_POST['medicalHistory']) ? sanitize_input($_POST['medicalHistory']) : NULL;
    
    // Check if email already exists (if provided)
    if ($email) {
        $checkEmailQuery = "SELECT recipient_id FROM recipients WHERE email = ?";
        $stmt = $conn->prepare($checkEmailQuery);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            echo "<script>
                alert('This email is already registered. Please use a different email.');
                window.location.href = '../recipient_registration.html';
            </script>";
            exit;
        }
    }
    
    // Insert recipient data into database
    $insertQuery = "INSERT INTO recipients (first_name, last_name, gender, date_of_birth, blood_group_id, contact_number, email, address, city, state, medical_history) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssssissssss", $firstName, $lastName, $gender, $dob, $bloodGroup, $contact, $email, $address, $city, $state, $medicalHistory);
    
    if ($stmt->execute()) {
        $recipientId = $conn->insert_id;
        echo "<script>
            alert('Registration successful! Your Recipient ID is: " . $recipientId . ". Please keep this ID for future reference.');
            window.location.href = '../index.html';
        </script>";
    } else {
        echo "<script>
            alert('Error: " . $stmt->error . "');
            window.location.href = '../recipient_registration.html';
        </script>";
    }
    
    $stmt->close();
} else {
    // Redirect if accessed directly without form submission
    header("Location: ../recipient_registration.html");
    exit;
}

$conn->close();
?>
