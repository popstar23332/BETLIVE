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
