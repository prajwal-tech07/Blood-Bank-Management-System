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
    $email = sanitize_input($_POST['email']);
    $address = sanitize_input($_POST['address']);
    $city = sanitize_input($_POST['city']);
    $state = sanitize_input($_POST['state']);
    $lastDonation = !empty($_POST['lastDonation']) ? sanitize_input($_POST['lastDonation']) : NULL;
    $medicalHistory = !empty($_POST['medicalHistory']) ? sanitize_input($_POST['medicalHistory']) : NULL;
    
    // Validate age (must be at least 18)
    $dobDate = new DateTime($dob);
    $today = new DateTime();
    $age = $today->diff($dobDate)->y;
    
    if ($age < 18) {
        echo "<script>
            alert('You must be at least 18 years old to register as a donor.');
            window.location.href = '../donor_registration.html';
        </script>";
        exit;
    }
    
    // Check if email already exists
    $checkEmailQuery = "SELECT donor_id FROM donors WHERE email = ?";
    $stmt = $conn->prepare($checkEmailQuery);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "<script>
            alert('This email is already registered. Please use a different email or login.');
            window.location.href = '../donor_registration.html';
        </script>";
        exit;
    }
    
    // Insert donor data into database
    $insertQuery = "INSERT INTO donors (first_name, last_name, gender, date_of_birth, blood_group_id, contact_number, email, address, city, state, last_donation_date, medical_history) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("ssssisssssss", $firstName, $lastName, $gender, $dob, $bloodGroup, $contact, $email, $address, $city, $state, $lastDonation, $medicalHistory);
    
    if ($stmt->execute()) {
        $donorId = $conn->insert_id;
        echo "<script>
            alert('Registration successful! Your Donor ID is: " . $donorId . ". Please keep this ID for future reference.');
            window.location.href = '../index.html';
        </script>";
    } else {
        echo "<script>
            alert('Error: " . $stmt->error . "');
            window.location.href = '../donor_registration.html';
        </script>";
    }
    
    $stmt->close();
} else {
    // Redirect if accessed directly without form submission
    header("Location: ../donor_registration.html");
    exit;
}

$conn->close();
?>
