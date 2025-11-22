<?php
$host = '127.0.0.1'; // or 'localhost'
$db   = 'bus_system';  // ✅ replace with your actual DB name
$user = 'root';                // ✅ replace with your MySQL username
$pass = '';                    // ✅ replace with your MySQL password (if any)
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $conn = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    die("❌ Database connection failed: " . $e->getMessage());
}
?>
