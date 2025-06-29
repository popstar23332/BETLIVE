<?php
require_once 'db.php';
require_once 'vendor/autoload.php';

use Dompdf\Dompdf;
use Dompdf\Options;

date_default_timezone_set('Africa/Nairobi');

// Get previous day's date
$yesterday = date('Y-m-d', strtotime('-1 day'));

// Fetch betting data
$sql = "
    SELECT 
        COUNT(*) AS total_bets,
        COALESCE(SUM(CASE WHEN result = 'loss' THEN stake ELSE 0 END), 0) -
        COALESCE(SUM(CASE WHEN result = 'win' THEN win_amount ELSE 0 END), 0) AS profit
    FROM bets
    WHERE DATE(created_at) = :yesterday
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':yesterday' => $yesterday]);
$data = $stmt->fetch();

// Prepare HTML for PDF
$html = "
    <h2>Pata Pata Bet - Daily Report for $yesterday</h2>
    <p><strong>Total Bets:</strong> {$data['total_bets']}</p>
    <p><strong>Profit:</strong> KES " . number_format($data['profit'], 2) . "</p>
";

// Generate PDF
$options = new Options();
$options->set('defaultFont', 'Arial');
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$pdfOutput = $dompdf->output();

// Save to file
$pdfFile = __DIR__ . "/reports/daily_report_{$yesterday}.pdf";
file_put_contents($pdfFile, $pdfOutput);

echo "Daily PDF report generated: daily_report_{$yesterday}.pdf";
