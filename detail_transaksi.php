<?php
session_start();
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit();
}
require_once __DIR__ . '/config/db_config.php';

// ----------------------
// Ambil data dari database (kelompokkan per transaksi)
// ----------------------
if (isset($_GET['tanggal']) && $_GET['tanggal'] != "") {
    $tanggal = $_GET['tanggal'];
    $stmt = $pdo->prepare("
        SELECT 
            t.id AS id_transaksi,
            DATE(t.tanggal) AS tanggal,
            GROUP_CONCAT(m.nama SEPARATOR ', ') AS menu,
            SUM(d.quantity) AS total_jumlah,
            SUM(d.subtotal) AS total_harga
        FROM detail_transaksi d
        JOIN transaksi t ON d.transaksi_id = t.id
        JOIN menu m ON d.menu_id = m.id
        WHERE DATE(t.tanggal) = ?
        GROUP BY t.id, DATE(t.tanggal)
        ORDER BY t.id DESC
    ");
    $stmt->execute([$tanggal]);
} else {
    // Tampilkan tanpa filter (data terakhir)
    $stmt = $pdo->query("
        SELECT 
            t.id AS id_transaksi,
            DATE(t.tanggal) AS tanggal,
            GROUP_CONCAT(m.nama SEPARATOR ', ') AS menu,
            SUM(d.quantity) AS total_jumlah,
            SUM(d.subtotal) AS total_harga
        FROM detail_transaksi d
        JOIN transaksi t ON d.transaksi_id = t.id
        JOIN menu m ON d.menu_id = m.id
        GROUP BY t.id, DATE(t.tanggal)
        ORDER BY t.id DESC
    ");
}

$data = $stmt->fetchAll(PDO::FETCH_ASSOC);



// ----------------------
// Hapus transaksi (otomatis hapus detail karena ON DELETE CASCADE)
// ----------------------
if (isset($_GET['delete'])) {
    $id_transaksi = (int)$_GET['delete'];

    try {
        $delete = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
        $delete->execute([$id_transaksi]);
        header('Location: detail_transaksi.php?msg=deleted');
        exit;
    } catch (Exception $e) {
        echo "<script>alert('Gagal menghapus data: " . $e->getMessage() . "');</script>";
    }
}


?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Transaksi</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

</head>
<body>
    <div class="header">
        <div class="menu-icon">☰</div>
    </div>

    <div class="container">
        <div class="sidebar">
            <a href="dashboard.php" class="sidebar-item">Dashboard ▾</a>
            <a href="kasir.php" class="sidebar-item">Kasir Menu ▾</a>
            <a href="detail_transaksi.php" class="sidebar-item active">Detail Transaksi ▾</a>
            <div class="logout-btn" onclick="location.href='logout.php'">Logout</div>
        </div>

        <div class="main-content">
            <div class="card">
            <div class="card-header">
                <span>Detail Transaksi</span>

                <form action="" method="GET" class="filter-form">
                    <input type="date" name="tanggal" value="<?= isset($_GET['tanggal']) ? $_GET['tanggal'] : '' ?>">
                    <button type="submit" class="btn-filter">Filter</button>
                </form>
            </div>
                <table class="table-detail">
                    <tr>
                        <th>No</th>
                        <th>ID Transaksi</th>
                        <th>Tanggal</th>
                        <th>Menu</th>
                        <th>Total Item</th>
                        <th>Total Harga</th>
                        <th>Aksi</th>
                    </tr>
                    <?php if ($data): ?>
                        <?php $no = 1; foreach ($data as $row): ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><?= htmlspecialchars($row['id_transaksi']) ?></td>
                            <td><?= htmlspecialchars($row['tanggal']) ?></td>
                            <td><?= htmlspecialchars($row['menu']) ?></td>
                            <td><?= htmlspecialchars($row['total_jumlah']) ?></td>
                            <td>Rp <?= number_format($row['total_harga'], 0, ',', '.') ?></td>
                            <td>
                                <a href="detail_transaksi.php?delete=<?= $row['id_transaksi'] ?>" 
                                class="btn-delete" 
                                onclick="return confirm('Yakin ingin menghapus transaksi ini?')">
                                <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="7">Tidak ada data transaksi</td></tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>
    </div>
</body>
</html>