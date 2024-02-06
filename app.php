<?php

$servername = "localhost";
$username = "root";
$password = "nitin";
$dbname = "";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to validate token from the database
function is_valid_token($token, $conn) {
    $sql = "SELECT * FROM users WHERE token = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->num_rows > 0;
}

// Function to fetch user details based on Aadhaar number
function fetch_user_details($aadhaar, $conn) {
    $sql = "SELECT name, fatherName, institutename FROM users WHERE aadhaar = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $aadhaar);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Function to store form data in the database only if the token is valid
function store_form_data($data, $conn) {
    // Check if the token exists in the database
    $sql_check_token = "SELECT * FROM users WHERE token = ?";
    $stmt = $conn->prepare($sql_check_token);
    $stmt->bind_param("s", $data['token']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Token exists, update the user's data
        $sql_update_data = "UPDATE users SET name=?, fatherName=?, email=?, aadhaar=?, phone=?, address=?, pincode=?, institutename=?, gender=?, dob=?, course=?, classYear=?, photo=?, signature=? WHERE token=?";
        $stmt = $conn->prepare($sql_update_data);
        $stmt->bind_param("ssssssssssssss", $data['name'], $data['fatherName'], $data['email'], $data['aadhaar'], $data['phone'], $data['address'], $data['pincode'], $data['institutename'], $data['gender'], $data['dob'], $data['course'], $data['classYear'], $data['photo'], $data['signature'], $data['token']);
        $stmt->execute();
    } else {
        // Token does not exist, insert a new record
        $sql_insert_data = "INSERT INTO users (name, fatherName, aadhaar, phone, address, pincode, institutename, gender, dob, course, classYear, photo, signature, token) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql_insert_data);
        $stmt->bind_param("sssssssssssss", $data['name'], $data['fatherName'], $data['aadhaar'], $data['phone'], $data['address'], $data['pincode'], $data['institutename'], $data['gender'], $data['dob'], $data['course'], $data['classYear'], $data['photo'], $data['signature'], $data['token']);
        $stmt->execute();
    }
}

// Route to fetch user details based on Aadhaar number
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['aadhaar'])) {
    $aadhaar = $_GET['aadhaar'];
    $user_details = fetch_user_details($aadhaar, $conn);

    if ($user_details) {
        echo json_encode($user_details);
    } else {
        echo json_encode(['error' => 'User not found']);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle form submission
    $data = [
        'name' => $_POST['name'],
        'fatherName' => $_POST['fatherName'],
        'email' => $_POST['email'],
        'aadhaar' => $_POST['aadhaar'],
        'phone' => $_POST['phone'],
        'address' => $_POST['address'],
        'pincode' => $_POST['pincode'],
        'institutename' => $_POST['institutename'],
        'gender' => $_POST['gender'],
        'dob' => $_POST['dob'],
        'course' => $_POST['course'],
        'classYear' => $_POST['classYear'],
        'photo' => $_POST['photo'],
        'signature' => $_POST['signature'],
        'token' => $_POST['token'],
    ];

    // Fetch user details based on Aadhaar number
    $user_details = fetch_user_details($data['aadhaar'], $conn);

    // If user details are found, update the form data
    if ($user_details) {
        $data['name'] = $user_details['name'];
        $data['fatherName'] = $user_details['fatherName'];
        $data['institutename'] = $user_details['institutename'];
    }

    // Validate the token and store form data in the database
    if (is_valid_token($data['token'], $conn)) {
        store_form_data($data, $conn);
        echo "Form submitted successfully!";
    } else {
        echo "Invalid token. Form submission canceled.";
    }
}

// Close the database connection
$conn->close();

?>
