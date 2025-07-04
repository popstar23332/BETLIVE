<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-type: text/plain');

$text = $_POST['text'] ?? '';
$phone = $_POST['phoneNumber'] ?? '0727430534';
$steps = explode("*", $text);

require_once 'db.php';
require_once 'mpesa.php';
require_once 'football_api.php';
require_once 'sms.php';

if (!is_dir("cache")) mkdir("cache");

// Cache helpers
function saveData($phone, $key, $data) {
    file_put_contents("cache/{$phone}_{$key}.json", json_encode($data));
}
function loadData($phone, $key) {
    $file = "cache/{$phone}_{$key}.json";
    return file_exists($file) ? json_decode(file_get_contents($file), true) : null;
}

// Show 5 most recent bets
function showRecentBets($pdo, $phone) {
    $stmt = $pdo->prepare("SELECT * FROM bets WHERE user_phone = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$phone]);
    $bets = $stmt->fetchAll();

    if (empty($bets)) return "END No bets placed yet.";

    $msg = "END Last 5 Bets:\n";
    foreach ($bets as $b) {
        $opt = match ($b['choice']) {
            1 => 'Win',
            2 => 'Draw',
            3 => 'Lose',
            default => 'N/A'
        };
        $msg .= "{$b['game_id']} | $opt | KES {$b['stake']}\n";
    }
    return $msg;
}

// Display leagues
function displayLeagues($phone) {
    $leagues = getLeagues();
    saveData($phone, "leagues", $leagues);

    $msg = "CON Choose League:\n";
    foreach ($leagues as $i => $name) {
        $msg .= ($i + 1) . ". " . $name . "\n";
    }
    return $msg;
}

// Display games for selected league
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

// === USSD Flow ===
if ($text == "") {
    echo "CON Welcome, Double Chapaa na Popstars Bets\n1. Place Bet\n2. Check My Bets";

} elseif (count($steps) == 1) {
    if ($steps[0] == "1") {
        echo displayLeagues($phone);
    } elseif ($steps[0] == "2") {
        echo showRecentBets($pdo, $phone);
    } else {
        echo "END Invalid option.";
    }

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

        if (!$game || !$choice) {
            echo "END Session expired. Start again.";
        } else {
            try {
                stkPush($phone, $stake, 'bet');
                placeBet($pdo, $phone, $game['id'], $choice, $stake);
                logTransaction($pdo, $phone, 'bet', $stake);

                $msg = "Popstar Bet: Your KES $stake bet on {$game['home']} vs {$game['away']} (option $choice) has been received.";
                sendSMS($phone, $msg);

                echo "END Bet placed on {$game['home']} vs {$game['away']}.\nStake: KES $stake";
            } catch (Exception $e) {
                file_put_contents("debug_bet.txt", "❌ Error: " . $e->getMessage() . "\n", FILE_APPEND);
                echo "END An error occurred. Try again later.";
            }
        }
    }

} else {
    echo "END Invalid input.";
}
