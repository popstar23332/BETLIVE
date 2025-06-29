<?php
// Connection details
$host = 'sql5.freesqldatabase.com';
$db   = 'sql5787380';
$user = 'sql5787380';
$pass = 'RPn8MBxyad';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;port=3306;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (PDOException $e) {
    exit("END DB Error: " . $e->getMessage());
}

// ✅ Registration logic with logging
function registerUser($pdo, $phone, $idNumber) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        if ($stmt->rowCount() > 0) {
            file_put_contents("debug_register.txt", "Already exists: $phone\n", FILE_APPEND);
            return true;
        }

        $stmt = $pdo->query("SELECT MAX(user_number) AS max_num FROM users");
        $max = $stmt->fetch()['max_num'];
        $newUserNumber = $max ? $max + 1 : 100000;

        $stmt = $pdo->prepare("INSERT INTO users (phone, national_id, user_number) VALUES (?, ?, ?)");
        $stmt->execute([$phone, $idNumber, $newUserNumber]);

        file_put_contents("debug_register.txt", "Registered: $phone - $idNumber\n", FILE_APPEND);
        return true;
    } catch (Exception $e) {
        file_put_contents("debug_register.txt", "Error: " . $e->getMessage() . "\n", FILE_APPEND);
        return false;
    }
}
?>
