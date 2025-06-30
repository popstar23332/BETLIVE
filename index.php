<?php
session_start();
header('Content-type: text/plain');

$text = $_POST['text'] ?? '';
$phone = $_POST['phoneNumber'] ?? '0727430534';
$steps = explode("*", $text);

require_once 'db.php';
require_once 'mpesa.php';
require_once 'football_api.php'; // must include getLeagues() and getGamesByLeague()
require_once 'sms.php';

function displayLeagues() {
    $leagues = getLeagues(); // returns array of league names
    $_SESSION['leagues'] = $leagues;

    $msg = "CON Choose League:\n";
    foreach ($leagues as $i => $name) {
        $msg .= ($i + 1) . ". " . $name . "\n";
    }
    return $msg;
}

function displayGames($leagueIndex) {
    $leagues = $_SESSION['leagues'] ?? [];
    if (!isset($leagues[$leagueIndex])) return "END Invalid league selection.";

    $league = $leagues[$leagueIndex];
    $games = getGamesByLeague($league); // returns array of ['id', 'home', 'away']
    if (empty($games)) return "END No games in $league.";

    $_SESSION['selected_league'] = $league;
    $_SESSION['games'] = $games;

    $msg = "CON Choose Game:\n";
    foreach ($games as $i => $g) {
        $msg .= ($i + 1) . ". {$g['home']} vs {$g['away']}\n";
    }
    return $msg;
}

if ($text == "") {
    echo "CON Welcome to Popstars Bet\n1. Log In";
} elseif ($steps[0] == "1" && count($steps) == 1) {
    echo "CON Enter your National ID:";
} elseif ($steps[0] == "1" && count($steps) == 2) {
    $id = $steps[1];
    $success = registerUser($pdo, $phone, $id);
    echo $success ? displayLeagues() : "END Registration failed. Try again.";
} elseif ($steps[0] == "1" && count($steps) == 3) {
    $leagueIndex = intval($steps[2]) - 1;
    echo displayGames($leagueIndex);
} elseif ($steps[0] == "1" && count($steps) == 4) {
    $gameIndex = intval($steps[3]) - 1;
    $games = $_SESSION['games'] ?? [];
    if (!isset($games[$gameIndex])) {
        echo "END Invalid game.";
    } else {
        $_SESSION['selected_game'] = $games[$gameIndex];
        echo "CON Predict outcome:\n1. Home Win\n2. Draw\n3. Away Win";
    }
} elseif ($steps[0] == "1" && count($steps) == 5) {
    $choice = intval($steps[4]);
    if (!in_array($choice, [1, 2, 3])) {
        echo "END Invalid prediction.";
    } else {
        $_SESSION['selected_choice'] = $choice;
        echo "CON Enter stake amount (max KES 5000):";
    }
} elseif ($steps[0] == "1" && count($steps) == 6) {
    $stake = intval($steps[5]);
    if ($stake <= 0 || $stake > 5000) {
        echo "END Invalid stake amount.";
    } else {
        $game = $_SESSION['selected_game'] ?? null;
        $choice = $_SESSION['selected_choice'] ?? null;

        if (!$game || !$choice) {
            echo "END Session expired. Start again.";
        } else {
            placeBet($pdo, $phone, $game['id'], $choice, $stake);
            deduct($pdo, $phone, $stake);
            logTransaction($pdo, $phone, 'bet', $stake);
            echo "END Bet placed on {$game['home']} vs {$game['away']}.\nStake: KES $stake";
        }
    }
} else {
    echo "END Invalid input.";
}
