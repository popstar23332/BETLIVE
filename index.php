<?php
session_start();

file_put_contents("ussd_debug.txt", date("Y-m-d H:i:s") . " | " . print_r($_POST, true) . "\n", FILE_APPEND);

$text = $_POST['text'] ?? '';
$phone = $_POST['phoneNumber'] ?? '0727430534';
$steps = explode("*", $text);

require_once 'db.php';
require_once 'sms.php';
require_once 'mpesa.php';
require_once 'football_api.php';

$response = "";

switch (true) {
    case ($text == ""):
        $response = "CON Popstars Bet\n1. Log In";
        break;

    case ($steps[0] == "1" && count($steps) == 1):
        $response = "CON Enter your National ID number:";
        break;

    case ($steps[0] == "1" && count($steps) == 2):
        $idNumber = $steps[1];
        if (registerUser($pdo, $phone, $idNumber)) {
            $response = "CON Main Menu:\n1. Deposit Funds\n2. Today's Games & Bet\n3. Withdraw Winnings";
        } else {
            $response = "END Registration failed. Please try again.";
        }
        break;

    case ($steps[0] == "1" && count($steps) >= 3):
        $action = $steps[2] ?? '';

        if ($action === "1") {
            stkPush($phone, 100, 'deposit');
            logTransaction($pdo, $phone, 'deposit', 100);
            $response = "END Deposit initiated. Approve the prompt on your phone.";
        
        } elseif ($action === "2") {
            $games = getTodaysGames();
            if (empty($games)) {
                $response = "END No games available today.";
                break;
            }

            $_SESSION['games'] = $games;

            if (count($steps) == 3) {
                $response = "CON Choose a game:\n";
                foreach ($games as $i => $g) {
                    $response .= ($i + 1) . ". {$g['home']} vs {$g['away']}\n";
                }

            } elseif (count($steps) == 4) {
                $index = intval($steps[3]) - 1;
                if (!isset($games[$index])) {
                    $response = "END Invalid game selection.";
                    break;
                }

                $_SESSION['selected_game'] = $games[$index];
                $response = "CON Bet on: {$games[$index]['home']} vs {$games[$index]['away']}\n1. Home Win\n2. Draw\n3. Away Win";

            } elseif (count($steps) == 5) {
                $choice = intval($steps[4]);
                $_SESSION['selected_choice'] = $choice;
                $response = "CON Enter your stake amount (max KES 5000):";

            } elseif (count($steps) == 6) {
                $stake = intval($steps[5]);
                $game = $_SESSION['selected_game'];
                $choice = $_SESSION['selected_choice'];

                if ($stake > 5000) {
                    $response = "END Maximum stake is KES 5,000.";
                } elseif (!hasBalance($pdo, $phone, $stake)) {
                    $response = "END Insufficient balance.";
                } else {
                    placeBet($pdo, $phone, $game['id'], $choice, $stake);
                    deduct($pdo, $phone, $stake);
                    logTransaction($pdo, $phone, 'bet', $stake);
                    $response = "END Bet placed with stake of KES $stake. Thank you!";
                }
            }

        } elseif ($action === "3") {
            $winnings = getWinnings($pdo, $phone);
            if ($winnings > 0) {
                stkPush($phone, $winnings, 'withdraw');
                resetWinnings($pdo, $phone);
                logTransaction($pdo, $phone, 'withdraw', $winnings);
                $response = "END Withdrawal initiated. You'll receive an M-Pesa prompt.";
            } else {
                $response = "END No winnings to withdraw.";
            }

        } else {
            $response = "END Invalid option.";
        }
        break;

    default:
        $response = "END Invalid input.";
        break;
}

header('Content-type: text/plain');
echo $response;
