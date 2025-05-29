<?php
require_once 'config.php';

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and get input data
    $recipientId = sanitize_input($_POST['recipientId']);
    $bloodGroup = sanitize_input($_POST['bloodGroup']);
    $unitsRequired = sanitize_input($_POST['unitsRequired']);
    $requiredDate = sanitize_input($_POST['requiredDate']);
    $hospitalName = sanitize_input($_POST['hospitalName']);
    $doctorName = sanitize_input($_POST['doctorName']);
    $purpose = sanitize_input($_POST['purpose']);
    
    // Validate recipient ID
    if (empty($recipientId)) {
        echo "<script>
            alert('Please register as a recipient first and provide your Recipient ID.');
            window.location.href = '../recipient_registration.html';
        </script>";
        exit;
    }
    
    // Check if recipient exists
    $checkRecipientQuery = "SELECT recipient_id FROM recipients WHERE recipient_id = ?";
    $stmt = $conn->prepare($checkRecipientQuery);
    $stmt->bind_param("i", $recipientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows == 0) {
        echo "<script>
            alert('Recipient ID not found. Please register as a recipient first.');
            window.location.href = '../recipient_registration.html';
        </script>";
        exit;
    }
    
    // Check blood stock availability
    $checkStockQuery = "SELECT units_available FROM blood_stock WHERE blood_group_id = ?";
    $stmt = $conn->prepare($checkStockQuery);
    $stmt->bind_param("i", $bloodGroup);
    $stmt->execute();
    $result = $stmt->get_result();
    $stock = $result->fetch_assoc();
    
    if ($result->num_rows == 0 || $stock['units_available'] < $unitsRequired) {
        echo "<script>
            alert('Sorry, we don\\'t have enough units of the requested blood group in stock. Please contact us directly for assistance.');
            window.location.href = '../blood_request.html';
        </script>";
        exit;
    }
    
    // Insert blood request into database
    $insertQuery = "INSERT INTO blood_requests (recipient_id, blood_group_id, units_required, required_date, hospital_name, doctor_name, purpose) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
    
    $stmt = $conn->prepare($insertQuery);
    $stmt->bind_param("iiissss", $recipientId, $bloodGroup, $unitsRequired, $requiredDate, $hospitalName, $doctorName, $purpose);
    
    if ($stmt->execute()) {
        $requestId = $conn->insert_id;
        echo "<script>
            alert('Blood request submitted successfully! Your Request ID is: " . $requestId . ". We will contact you soon.');
            window.location.href = '../index.html';
        </script>";
    } else {
        echo "<script>
            alert('Error: " . $stmt->error . "');
            window.location.href = '../blood_request.html';
        </script>";
    }
    
    $stmt->close();
} else {
    // Redirect if accessed directly without form submission
    header("Location: ../blood_request.html");
    exit;
}

$conn->close();
?>
