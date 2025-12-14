<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit();
}

require_once __DIR__ . '/config/db_config.php';

// ===========================
// FILTER
// ===========================
$filter = $_GET['filter'] ?? 'daily';

switch ($filter) {
    case 'monthly':
        $where   = "WHERE DATE_FORMAT(tanggal, '%Y-%m') = DATE_FORMAT(CURDATE(), '%Y-%m')";
        $groupBy = "DATE_FORMAT(tanggal, '%Y-%m')";
        $label   = "Pendapatan Bulanan";
        break;

    case 'yearly':
        $where   = "WHERE YEAR(tanggal) = YEAR(CURDATE())";
        $groupBy = "YEAR(tanggal)";
        $label   = "Pendapatan Tahunan";
        break;

    default: // daily
        $where   = "WHERE DATE(tanggal) = CURDATE()";
        $groupBy = "DATE(tanggal)";
        $label   = "Pendapatan Harian";
        break;
}

// ===========================
// DATA GRAFIK
// ===========================
$query = "
    SELECT 
        $groupBy AS periode,
        SUM(total_bayar) AS total
    FROM transaksi
    $where
    GROUP BY $groupBy
    ORDER BY periode ASC
";

$stmt = $pdo->query($query);
$chartData = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ===========================
// TOTAL PENDAPATAN
// ===========================
$totalPendapatan = $pdo->query("
    SELECT SUM(total_bayar) AS total
    FROM transaksi
    $where
")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// ===========================
// REKAP TOTAL TRANSAKSI
// ===========================
$rekap = $pdo->query("
    SELECT 
        COUNT(*) AS total_transaksi,
        SUM(total_bayar) AS total_nilai
    FROM transaksi
    $where
")->fetch(PDO::FETCH_ASSOC);
        
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Monitoring Keuangan</title>
    <link rel="stylesheet" href="assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>

<div class="header"><div class="menu-icon">☰</div></div>

<div class="container">
    <div class="sidebar">
        <a href="dashboard.php" class="sidebar-item">Dashboard ▾</a>
        <a href="kasir.php" class="sidebar-item">Kasir Menu ▾</a>
        <a href="detail_transaksi.php" class="sidebar-item">Detail Transaksi ▾</a>
        <a href="monitoring.php" class="sidebar-item active">Monitoring Keuangan ▾</a>
        <div class="logout-btn" onclick="location.href='logout.php'">Logout</div>
    </div>

    <div class="main-content">

        <h2>Monitoring Keuangan</h2>

        <!-- FILTER -->
        <form method="GET">
            <select name="filter" onchange="this.form.submit()" class="filter-select">
                <option value="daily"   <?= $filter=='daily'?'selected':'' ?>>Harian</option>
                <option value="monthly" <?= $filter=='monthly'?'selected':'' ?>>Bulanan</option>
                <option value="yearly"  <?= $filter=='yearly'?'selected':'' ?>>Tahunan</option>
            </select>
        </form>

        <!-- Statistik Utama -->
        <div class="stats-container">
            <div class="stat-box">
                <p>Total Pendapatan</p>
                <strong>Rp <?= number_format($totalPendapatan,0,',','.') ?></strong>
            </div>
        </div>

        <!-- Grafik -->
        <div class="card">
            <div class="card-header"><strong><?= $label ?></strong></div>
            <div id="chartContainer">
                <canvas id="chartPendapatan"></canvas>
            </div>
        </div>

        <!-- Rekap Transaksi -->
        <div class="card">
            <div class="card-header"><strong>Rekap Transaksi</strong></div>
            <table class="detail-table">
                <tr>
                    <th>Total Transaksi</th>
                    <th>Total Pendapatan</th>
                </tr>
                <tr>
                    <td><?= $rekap['total_transaksi'] ?></td>
                    <td>Rp <?= number_format($rekap['total_nilai'],0,',','.') ?></td>
                </tr>
            </table>
        </div>

    </div>
</div>

<script>
    new Chart(document.getElementById('chartPendapatan'), {
        type: 'line',
        data: {
            labels: <?= json_encode(array_column($chartData, 'periode')) ?>,
            datasets: [{
                label: '<?= $label ?>',
                data: <?= json_encode(array_column($chartData, 'total')) ?>,
                borderWidth: 3,
                tension: 0.3
            }]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
</script>

</body>
</html>
