<?php
// Database connection details
$host = 'localhost';
$db   = 'ussd_betting';   // Change if your DB name is different
$user = 'root';           // Default user for XAMPP
$pass = '';               // Default has no password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Throw exceptions
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit("END DB Error: " . $e->getMessage());
}

// Function to register a new user with auto-assigned user_number
function registerUser($pdo, $phone, $idNumber) {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->rowCount() > 0) {
        return "END You are already registered.";
    }

    // Get max user_number and add 1, or start at 100000
    $stmt = $pdo->query("SELECT MAX(user_number) AS max_num FROM users");
    $max = $stmt->fetch()['max_num'];
    $newUserNumber = $max ? $max + 1 : 100000;

    // Insert user with auto-assigned user_number
    $stmt = $pdo->prepare("INSERT INTO users (phone, national_id, user_number) VALUES (?, ?, ?)");
    $stmt->execute([$phone, $idNumber, $newUserNumber]);

    return "END Registration successful. Welcome to Pata Pata Bet.";
}
?>
