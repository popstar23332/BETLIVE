<?php
header('Content-type: text/plain');
require_once("config.php");       // DB connection ($pdo)
require_once("mpesa.php");        // M-Pesa functions

// Input
$sessionId   = $_POST["sessionId"] ?? '';
$serviceCode = $_POST["serviceCode"] ?? '';
$phoneNumber = $_POST["phoneNumber"] ?? '';
$text        = $_POST["text"] ?? '';

$menu = explode("*", $text);
$level = count($menu);

// Simulated game list (you can replace with a real API)
$games = [
    "1" => "Arsenal vs Chelsea",
    "2" => "Man City vs Liverpool",
    "3" => "Barcelona vs Real Madrid"
];

if ($text == "") {
    echo "CON Welcome to PopStar Bets\nEnter your ID:";
} elseif ($level == 1) {
    echo "CON Choose a game:\n";
    foreach ($games as $key => $name) {
        echo "$key. $name\n";
    }
} elseif ($level == 2) {
    echo "CON Choose outcome:\n1. Home Win\n2. Draw\n3. Away Win";
} elseif ($level == 3) {
    echo "CON Enter amount in KES:";
} elseif ($level == 4) {
    $userId = $menu[0];
    $gameId = $menu[1];
    $outcome = $menu[2];
    $amount = (int)$menu[3];

    if (!isset($games[$gameId])) {
        echo "END Invalid game selected.";
        exit;
    }

    // Save bet to DB
    try {
        $stmt = $pdo->prepare("INSERT INTO bets (phone_number, user_id, option_chosen, amount, game_id) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$phoneNumber, $userId, $outcome, $amount, $gameId]);
        
        // Trigger M-Pesa STK push
        $response = stkPush($phoneNumber, $amount, "bet");
        if (isset($response['ResponseCode']) && $response['ResponseCode'] == "0") {
            echo "END Bet placed! KES $amount STK push sent to your phone.";
        } else {
            echo "END Bet saved, but payment failed to initiate.";
        }
    } catch (Exception $e) {
        echo "END Error saving bet. Try again.";
    }
} else {
    echo "END Invalid input. Please start again.";
}
?>
