<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Log request for debugging
file_put_contents("ussd_debug.txt", date("Y-m-d H:i:s") . " " . print_r($_POST, true), FILE_APPEND);

header('Content-type: text/plain');

$text = $_POST['text'] ?? '';
$phone = $_POST['phoneNumber'] ?? '0727430534';
$steps = explode("*", $text);

require_once 'db.php';
require_once 'mpesa.php';
require_once 'football_api.php';
require_once 'sms.php';

// Ensure cache directory exists
if (!is_dir("cache")) mkdir("cache");

// === Cache Helpers ===
function saveData($phone, $key, $data) {
    file_put_contents("cache/{$phone}_{$key}.json", json_encode($data));
}
function loadData($phone, $key) {
    $file = "cache/{$phone}_{$key}.json";
    return file_exists($file) ? json_decode(file_get_contents($file), true) : [];
}

// === Menu Builders ===
function displayLeagues($phone) {
    $leagues = getLeagues();
    saveData($phone, "leagues", $leagues);

    $msg = "CON Choose League:\n";
    foreach ($leagues as $i => $name) {
        $msg .= ($i + 1) . ". " . $name . "\n";
    }
    return $msg;
}

function displayGames($phone, $leagueIndex) {
    $leagues = loadData($phone, "leagues");
    if (!isset($leagues[$leagueIndex])) return "END Invalid league selection.";

    $league = $leagues[$leagueIndex];
    $games = getGamesByLeague($league);
    if (empty($games)) return "END No games in $league.";

    saveData($phone, "games", $games);
    saveData($phone, "league", $league);

    $msg = "CON Choose Game:\n";
    foreach ($games as $i => $g) {
        $msg .= ($i + 1) . ". {$g['home']} vs {$g['away']}\n";
    }
    return $msg;
}

// === Flow Logic ===
if ($text == "") {
    echo "CON Welcome to Popstars Bet\nPlease enter your National ID:";

} elseif (count($steps) == 1) {
    $id = $steps[0];
    $success = registerUser($pdo, $phone, $id);
    echo $success ? displayLeagues($phone) : "END Registration failed. Try again.";

} elseif (count($steps) == 2) {
    $leagueIndex = intval($steps[1]) - 1;
    echo displayGames($phone, $leagueIndex);

} elseif (count($steps) == 3) {
    $gameIndex = intval($steps[2]) - 1;
    $games = loadData($phone, "games");
    if (!isset($games[$gameIndex])) {
        echo "END Invalid game.";
    } else {
        saveData($phone, "selected_game", $games[$gameIndex]);
        echo "CON Predict outcome:\n1. Home Win\n2. Draw\n3. Away Win";
    }

} elseif (count($steps) == 4) {
    $choice = intval($steps[3]);
    if (!in_array($choice, [1, 2, 3])) {
        echo "END Invalid prediction.";
    } else {
        saveData($phone, "selected_choice", $choice);
        echo "CON Enter stake amount (max KES 5000):";
    }

} elseif (count($steps) == 5) {
    $stake = intval($steps[4]);
    if ($stake <= 0 || $stake > 5000) {
        echo "END Invalid stake amount.";
    } else {
        $game = loadData($phone, "selected_game");
        $choice = loadData($phone, "selected_choice");

        // Logging start
        file_put_contents("debug_bet.txt", "Phone: $phone\nGame ID: {$game['id']}\nChoice: $choice\nStake: $stake\n", FILE_APPEND);

        if (!$game || !$choice) {
            echo "END Session expired. Start again.";
        } else {
            try {
                placeBet($pdo, $phone, $game['id'], $choice, $stake);
                file_put_contents("debug_bet.txt", "placeBet() executed\n", FILE_APPEND);

                deduct($pdo, $phone, $stake);
                file_put_contents("debug_bet.txt", "deduct() executed\n", FILE_APPEND);

                logTransaction($pdo, $phone, 'bet', $stake);
                file_put_contents("debug_bet.txt", "logTransaction() executed\n", FILE_APPEND);

                echo "END Bet placed on {$game['home']} vs {$game['away']}.\nStake: KES $stake";
            } catch (Exception $e) {
                file_put_contents("debug_bet.txt", "Error: " . $e->getMessage() . "\n", FILE_APPEND);
                echo "END An error occurred. Please try again later.";
            }
        }
    }

} else {
    echo "END Invalid input.";
}
