<?php
// Remote MySQL connection via freesqldatabase.com
$host = 'sql5.freesqldatabase.com';
$db   = 'sql5787380';
$user = 'sql5787380';
$pass = 'RPn8MBxyad';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=$charset";

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

// ✅ Updated to return TRUE instead of END — this allows flow to continue
function registerUser($pdo, $phone, $idNumber) {
    // Check if user already exists
    $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
    $stmt->execute([$phone]);
    if ($stmt->rowCount() > 0) {
        return true; // already registered — proceed
    }

    // Get next user_number
    $stmt = $pdo->query("SELECT MAX(user_number) AS max_num FROM users");
    $max = $stmt->fetch()['max_num'];
    $newUserNumber = $max ? $max + 1 : 100000;

    // Register user
    $stmt = $pdo->prepare("INSERT INTO users (phone, national_id, user_number) VALUES (?, ?, ?)");
    $stmt->execute([$phone, $idNumber, $newUserNumber]);

    return true; // success — allow USSD to continue
}
?>
