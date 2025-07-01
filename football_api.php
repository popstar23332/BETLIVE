<?php

function getTodaysGames() {
    $apiKey = 'd44267594a54e94e2516b59e8e9be7b1';
    $today = date("Y-m-d");

    $allowedLeagues = [39, 140, 135, 78, 300]; // Supported league IDs

    $url = "https://v3.football.api-sports.io/fixtures?date=$today";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-apisports-key: $apiKey"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $fixtures = $data['response'] ?? [];

    $games = [];
    foreach ($fixtures as $match) {
        if (in_array($match['league']['id'], $allowedLeagues)) {
            $games[] = [
                'id' => $match['fixture']['id'],
                'home' => $match['teams']['home']['name'],
                'away' => $match['teams']['away']['name'],
                'league' => $match['league']['name']
            ];
        }
    }

    return $games;
}

function getMatchResult($gameId) {
    $apiKey = 'd44267594a54e94e2516b59e8e9be7b1';
    $url = "https://v3.football.api-sports.io/fixtures?id=$gameId";

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        "x-apisports-key: $apiKey"
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    $match = $data['response'][0] ?? null;

    if (!$match || $match['fixture']['status']['short'] !== "FT") {
        return 0; // Not finished
    }

    $homeGoals = $match['goals']['home'];
    $awayGoals = $match['goals']['away'];

    if ($homeGoals > $awayGoals) return 1;
    if ($homeGoals < $awayGoals) return 3;
    return 2;
}

// ✅ Static list of supported leagues
function getLeagues() {
    return [
        "Premier League",
        "La Liga",
        "Serie A",
        "Bundesliga",
        "Kenya Premier League"
    ];
}

// ✅ Dummy games per league (used in USSD flow)
function getGamesByLeague($leagueName) {
    $dummyGames = [
        "Premier League" => [
            ["id" => "test1", "home" => "Arsenal", "away" => "Chelsea"],
            ["id" => "test2", "home" => "Liverpool", "away" => "Man City"]
        ],
        "La Liga" => [
            ["id" => "test3", "home" => "Barcelona", "away" => "Real Madrid"],
            ["id" => "test4", "home" => "Atletico", "away" => "Sevilla"]
        ],
        "Serie A" => [
            ["id" => "test5", "home" => "Juventus", "away" => "Napoli"]
        ],
        "Bundesliga" => [
            ["id" => "test6", "home" => "Bayern", "away" => "Dortmund"]
        ],
        "Kenya Premier League" => [
            ["id" => "test7", "home" => "Gor Mahia", "away" => "AFC Leopards"]
        ]
    ];

    return $dummyGames[$leagueName] ?? [];
}

// ✅ Process results and notify users via SMS
function processGameResults($pdo, $gameId) {
    $result = getMatchResult($gameId);
    if (!$result) return;

    $stmt = $pdo->prepare("SELECT * FROM bets WHERE game_id = ? AND result IS NULL");
    $stmt->execute([$gameId]);
    $bets = $stmt->fetchAll();

    foreach ($bets as $bet) {
        $phone = $bet['user_phone'];
        $choice = intval($bet['choice']);
        $stake = intval($bet['stake']);

        if ($choice === $result) {
            $winAmount = $stake * 3;
            $pdo->prepare("UPDATE users SET winnings = winnings + ? WHERE phone = ?")
                ->execute([$winAmount, $phone]);

            $pdo->prepare("UPDATE bets SET result = 'won', win_amount = ? WHERE id = ?")
                ->execute([$winAmount, $bet['id']]);

            $msg = "🎉 You WON KES $winAmount for your bet on game {$bet['game_id']}!";
        } else {
            $pdo->prepare("UPDATE bets SET result = 'lost', win_amount = 0 WHERE id = ?")
                ->execute([$bet['id']]);

            $msg = "❌ You lost your bet on game {$bet['game_id']}. Try again next time.";
        }

        // ✅ Send SMS about result
        sendSMS($phone, $msg);
    }
}
