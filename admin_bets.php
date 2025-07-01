<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';

$stmt = $pdo->query("SELECT * FROM bets ORDER BY created_at DESC");
$bets = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin - All Bets</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; text-align: center; }
        th { background-color: #f4f4f4; }
        h2 { text-align: center; }
    </style>
</head>
<body>

<h2>All Bets</h2>

<table>
    <tr>
        <th>ID</th>
        <th>User Phone</th>
        <th>Game ID</th>
        <th>Choice</th>
        <th>Stake</th>
        <th>Result</th>
        <th>Win Amount</th>
        <th>Created At</th>
    </tr>
    <?php foreach ($bets as $b): ?>
    <tr>
        <td><?= $b['id'] ?></td>
        <td><?= htmlspecialchars($b['user_phone']) ?></td>
        <td><?= htmlspecialchars($b['game_id']) ?></td>
        <td><?= $b['choice'] ?></td>
        <td><?= number_format($b['stake']) ?></td>
        <td><?= $b['result'] ?? '-' ?></td>
        <td><?= number_format($b['win_amount']) ?></td>
        <td><?= $b['created_at'] ?></td>
    </tr>
    <?php endforeach; ?>
</table>

</body>
</html>
