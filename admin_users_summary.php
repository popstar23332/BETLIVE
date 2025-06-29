<?php

session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

// Get filters from URL
$gameId     = $_GET['game_id'] ?? '';
$startDate  = $_GET['start_date'] ?? '';
$endDate    = $_GET['end_date'] ?? '';
$download   = isset($_GET['download']) && $_GET['download'] === 'excel';

$conditions = [];
$params = [];

// Build conditions dynamically
if (!empty($gameId)) {
    $conditions[] = "b.game_id = :game_id";
    $params[':game_id'] = $gameId;
}
if (!empty($startDate)) {
    $conditions[] = "DATE(b.created_at) >= :start_date";
    $params[':start_date'] = $startDate;
}
if (!empty($endDate)) {
    $conditions[] = "DATE(b.created_at) <= :end_date";
    $params[':end_date'] = $endDate;
}

$whereSQL = $conditions ? "WHERE " . implode(" AND ", $conditions) : "";

// Query: Get user summary (with optional filters)
$sql = "
    SELECT 
        u.user_number, 
        u.phone,
        COUNT(b.id) AS total_bets,
        COALESCE(SUM(CASE WHEN b.result = 'win' THEN b.win_amount ELSE 0 END), 0) AS total_won,
        COALESCE(SUM(CASE WHEN b.result = 'loss' THEN b.stake ELSE 0 END), 0) AS total_lost,
        (COALESCE(SUM(CASE WHEN b.result = 'loss' THEN b.stake ELSE 0 END), 0) -
         COALESCE(SUM(CASE WHEN b.result = 'win' THEN b.win_amount ELSE 0 END), 0)) AS profit
    FROM users u
    LEFT JOIN bets b ON u.phone = b.user_phone
    $whereSQL
    GROUP BY u.user_number, u.phone
    ORDER BY u.user_number ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($download) {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=betting_summary.xls");
    echo "User Number\tPhone\tTotal Bets\tTotal Won (KES)\tTotal Lost (KES)\tProfit (KES)\n";
    foreach ($users as $u) {
        echo "{$u['user_number']}\t{$u['phone']}\t{$u['total_bets']}\t{$u['total_won']}\t{$u['total_lost']}\t{$u['profit']}\n";
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - User Betting Summary</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        h2 {
            text-align: center;
        }
        form {
            text-align: center;
            margin-bottom: 20px;
        }
        input, button {
            padding: 6px;
            margin: 0 8px;
            min-width: 150px;
        }
        table {
            border-collapse: collapse;
            width: 95%;
            margin: auto;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 10px 12px;
            text-align: center;
        }
        th {
            background-color: #f4f4f4;
        }
        .no-data {
            text-align: center;
            color: red;
            font-weight: bold;
            margin-top: 20px;
        }
    </style>
</head>
<body>

<h2>Pata Pata Bet - User Betting Summary</h2>

<form method="GET">
    <input type="text" name="game_id" placeholder="Game ID (e.g., game1)" value="<?= htmlspecialchars($gameId) ?>">
    <input type="date" name="start_date" value="<?= htmlspecialchars($startDate) ?>">
    <input type="date" name="end_date" value="<?= htmlspecialchars($endDate) ?>">
    <button type="submit">Apply Filters</button>
    <a href="admin_users.php"><button type="button">Reset</button></a>
    <button type="submit" name="download" value="excel">Download Excel</button>
</form>

<?php if (count($users) > 0): ?>
<table>
    <tr>
        <th>User Number</th>
        <th>Phone</th>
        <th>Total Bets</th>
        <th>Total Won (KES)</th>
        <th>Total Lost (KES)</th>
        <th>Profit (KES)</th>
    </tr>
    <?php foreach ($users as $u): ?>
    <tr>
        <td><?= htmlspecialchars($u['user_number']) ?></td>
        <td><?= htmlspecialchars($u['phone']) ?></td>
        <td><?= $u['total_bets'] ?></td>
        <td><?= number_format($u['total_won'], 2) ?></td>
        <td><?= number_format($u['total_lost'], 2) ?></td>
        <td><?= number_format($u['profit'], 2) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
<?php else: ?>
    <p class="no-data">No records found for the selected filter(s).</p>
<?php endif; ?>

</body>
</html>
