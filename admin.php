<?php
session_start();
if (!isset($_SESSION["admin_logged_in"])) {
    header("Location: login.php");
    exit;
}

require_once 'db.php';
require_once 'mpesa.php';

// === Withdrawal Logic ===
$auto_limit = 2000;

// Auto-approve small withdrawals
$auto_pending = $pdo->prepare("SELECT * FROM withdrawals WHERE status = 'pending' AND amount <= ?");
$auto_pending->execute([$auto_limit]);
$auto_withdrawals = $auto_pending->fetchAll();

foreach ($auto_withdrawals as $w) {
    $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?")->execute([$w['id']]);
    stkPush($w['phone'], $w['amount'], 'payout');
    $log = $pdo->prepare("INSERT INTO payouts (phone, amount, method) VALUES (?, ?, ?)");
    $log->execute([$w['phone'], $w['amount'], 'auto']);
}

// Handle manual approval for larger withdrawals
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['withdrawal_id'])) {
    $id = $_POST['withdrawal_id'];
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmt->execute([$id]);
    $withdrawal = $stmt->fetch();

    if ($withdrawal && $withdrawal['status'] === 'pending') {
        $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?")->execute([$id]);
        stkPush($withdrawal['phone'], $withdrawal['amount'], 'payout');
        $log = $pdo->prepare("INSERT INTO payouts (phone, amount, method) VALUES (?, ?, ?)");
        $log->execute([$withdrawal['phone'], $withdrawal['amount'], 'manual']);
        $withdrawal_approved = true;
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        h2 {
            text-align: center;
            margin-top: 40px;
        }

        .nav {
            text-align: center;
            margin-bottom: 30px;
        }

        .nav a {
            margin: 0 15px;
            font-size: 16px;
            text-decoration: none;
            color: #0066cc;
        }

        .nav a:hover {
            text-decoration: underline;
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

        .notice {
            text-align: center;
            color: green;
        }

        .logout {
            text-align: center;
            margin-top: 20px;
        }

        .logout a {
            color: red;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="nav">
    <a href="admin_user_summary.php">📊 User Summary Report</a>
    <a href="admin_bets.php">🔎 View All Bets</a>
    <a href="daily_report.php">📄 Daily PDF Report</a>
</div>

<h2>Pending Withdrawals Above KES <?= $auto_limit ?></h2>

<?php if (!empty($withdrawal_approved)): ?>
    <p class="notice">✅ Withdrawal approved and sent via M-Pesa.</p>
<?php endif; ?>

<form method="POST">
    <?php
    $pending = $pdo->prepare("SELECT * FROM withdrawals WHERE status = 'pending' AND amount > ?");
    $pending->execute([$auto_limit]);
    $pending_withdrawals = $pending->fetchAll();

    foreach ($pending_withdrawals as $w): ?>
        <p style="text-align: center;">
            <?= htmlspecialchars($w['phone']) ?> - KES <?= number_format($w['amount']) ?>
            <button name="withdrawal_id" value="<?= $w['id'] ?>">Approve</button>
        </p>
    <?php endforeach; ?>
</form>

<div class="logout">
    <p><a href="logout.php">🚪 Logout</a></p>
</div>

</body>
</html>
