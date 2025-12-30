<?php

$host= "localhost";
$user= "root";
$password= "";
$database= "rotufit_tracker_db";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// SMTP settings - fill these to enable sending real emails via SMTP (recommended for testing/production).
// Example (Mailtrap):
// host: smtp.mailtrap.io
// port: 2525
// username: your_mailtrap_user
// password: your_mailtrap_pass
// secure: '' or 'tls' or 'ssl'

$smtp = [
    'host' => '',          // e.g. 'smtp.mailtrap.io'
    'port' => 2525,        // e.g. 2525 or 587
    'username' => '',
    'password' => '',
    'secure' => '',        // 'tls' or 'ssl' or empty for none
    'from_email' => 'no-reply@' . ($_SERVER['HTTP_HOST'] ?? 'localhost'),
    'from_name' => 'Your App'
];
?>