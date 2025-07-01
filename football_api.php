<?php

function getTodaysGames() {
    $apiKey = 'd44267594a54e94e2516b59e8e9be7b1';
    $today = date("Y-m-d");

    $allowedLeagues = [
        39,   // Premier League
        140,  // La Liga
        135,  // Serie A
        78,   // Bundesliga
        300   // Kenya Premier League (replace if needed)
    ];

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

    if (!$match || $match['fixture']['status']['short'] != "FT") {
        return 0; // Match not finished
    }

    $homeGoals = $match['goals']['home'];
    $awayGoals = $match['goals']['away'];

    if ($homeGoals > $awayGoals) return 1; // Home Win
    if ($homeGoals < $awayGoals) return 3; // Away Win
    return 2; // Draw
}

// ✅ List of supported leagues
function getLeagues() {
    return [
        "Premier League",
        "La Liga",
        "Serie A",
        "Bundesliga",
        "Kenya Premier League"
    ];
}

// ✅ Return dummy games per league for testing
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
