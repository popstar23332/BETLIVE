<?php
session_start();
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: ../login.php');
    exit;
}

require_once '../db.php';
require_once '../mpesa.php'; // Make sure this includes stkPush()

// Auto-approve small withdrawals
$auto_limit = 2000;

$auto_pending = $pdo->prepare("SELECT * FROM withdrawals WHERE status = 'pending' AND amount <= ?");
$auto_pending->execute([$auto_limit]);
$auto_withdrawals = $auto_pending->fetchAll();

foreach ($auto_withdrawals as $w) {
    // Mark as approved
    $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?")->execute([$w['id']]);
    
    // Send M-Pesa
    stkPush($w['phone'], $w['amount'], 'payout');

    // Log payout to DB
    $log = $pdo->prepare("INSERT INTO payouts (phone, amount, method) VALUES (?, ?, ?)");
    $log->execute([$w['phone'], $w['amount'], 'auto']);
}

// Admin approval for larger withdrawals
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];

    // Get withdrawal details
    $stmt = $pdo->prepare("SELECT * FROM withdrawals WHERE id = ?");
    $stmt->execute([$id]);
    $withdrawal = $stmt->fetch();

    if ($withdrawal && $withdrawal['status'] === 'pending') {
        $pdo->prepare("UPDATE withdrawals SET status = 'approved' WHERE id = ?")->execute([$id]);

        // Send M-Pesa
        stkPush($withdrawal['phone'], $withdrawal['amount'], 'payout');

        // Log payout to DB
        $log = $pdo->prepare("INSERT INTO payouts (phone, amount, method) VALUES (?, ?, ?)");
        $log->execute([$withdrawal['phone'], $withdrawal['amount'], 'manual']);
    }
}

// Show only pending withdrawals above the auto limit
$pending = $pdo->prepare("SELECT * FROM withdrawals WHERE status = 'pending' AND amount > ?");
$pending->execute([$auto_limit]);
$pending_withdrawals = $pending->fetchAll();
?>

<h2>Pending Withdrawals Above KES <?= $auto_limit ?></h2>
<form method="POST">
    <?php foreach ($pending_withdrawals as $w): ?>
        <p>
            <?= $w['phone'] ?> - KES <?= $w['amount'] ?>
            <button name="id" value="<?= $w['id'] ?>">Approve</button>
        </p>
    <?php endforeach; ?>
</form>
