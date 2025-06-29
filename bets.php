<?php
require_once 'db.php';
require_once 'mpesa.php'; // This must include sendSMS()

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get data from POST request
    $userId = $_POST['user_id'] ?? null;
    $gameId = $_POST['game_id'] ?? null;
    $amount = $_POST['amount'] ?? null;

    // Validate input
    if (!$userId || !$gameId || !$amount || $amount <= 0) {
        http_response_code(400);
        exit("Invalid bet details.");
    }

    // Get user phone number
    $stmt = $pdo->prepare("SELECT phone FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        exit("User not found.");
    }

    // Optional: Check if user has enough balance
    // Example only — implement based on your balance logic
    // $balance = getUserBalance($userId);
    // if ($balance < $amount) exit("Insufficient balance.");

    // Insert the bet into the database
    $stmt = $pdo->prepare("INSERT INTO bets (user_id, game_id, amount, status) VALUES (?, ?, ?, 'pending')");
    $stmt->execute([$userId, $gameId, $amount]);

    // Send confirmation SMS
    $message = "Popstar Bets: Your bet of KES $amount on Game $gameId has been placed successfully.";
    sendSMS($user['phone'], $message);

    echo "Bet placed successfully.";
}
?>
