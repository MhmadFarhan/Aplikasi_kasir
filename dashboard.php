<?php
session_start();

if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit();
}

include 'config/db_config.php';

// === Ambil Pendapatan Hari Ini ===
$query = $pdo->query("SELECT SUM(total_bayar) as total FROM transaksi WHERE DATE(tanggal) = CURDATE()");
$pendapatan_hari_ini = $query->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// === Ambil Pendapatan Bulan Ini ===
$query = $pdo->query("SELECT SUM(total_bayar) as total FROM transaksi WHERE MONTH(tanggal) = MONTH(CURDATE()) AND YEAR(tanggal) = YEAR(CURDATE())");
$pendapatan_bulan_ini = $query->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// === Ambil Pendapatan Tahun Ini ===
$query = $pdo->query("SELECT SUM(total_bayar) as total FROM transaksi WHERE YEAR(tanggal) = YEAR(CURDATE())");
$pendapatan_tahun_ini = $query->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard UI</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="header">
    <span class="menu-toggle">☰</span>
</div>

<div class="sidebar">
    <a href="dashboard.php" class="sidebar-nav-item active">Dashboard <span>&#9660;</span></a>
    <a href="kasir.php" class="sidebar-nav-item">Kasir Menu <span>&#9660;</span></a>
    <a href="detail_transaksi.php" class="sidebar-nav-item">Detail Transaksi <span>&#9660;</span></a>
    <div class="logout-btn" onclick="location.href='logout.php'">Logout</div>
</div>

<div class="main-content">
    <h1>Dashboard</h1>
    
    <div class="stats-container">
        <div class="stat-box">
            <p>Pendapatan Hari Ini</p>
            <strong>Rp. <?php echo number_format($pendapatan_hari_ini, 0, ',', '.'); ?></strong>
        </div>
        <div class="stat-box">
            <p>Pendapatan Bulan Ini</p>
            <strong>Rp. <?php echo number_format($pendapatan_bulan_ini, 0, ',', '.'); ?></strong>
        </div>
        <div class="stat-box">
            <p>Pendapatan Tahun Ini</p>
            <strong>Rp. <?php echo number_format($pendapatan_tahun_ini, 0, ',', '.'); ?></strong>
        </div>
    </div>
</div>
</body>
</html>
