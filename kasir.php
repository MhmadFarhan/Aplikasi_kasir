<?php
// kasir.php - Versi Koneksi Database
session_start();

// Pastikan user sudah login
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit();
}

// Include koneksi database
include 'config/db_config.php';

// --- INISIALISASI ---
if (!isset($_SESSION['total_revenue'])) {
    $_SESSION['total_revenue'] = 0;
}

// ===========================================
// 1. Ambil Data Menu dari Database
// ===========================================
// Jika belum punya tabel, buat seperti ini:
// CREATE TABLE menu (
//   id INT AUTO_INCREMENT PRIMARY KEY,
//   nama VARCHAR(100),
//   harga INT
// );

try {
    $stmt = $pdo->query("SELECT * FROM menu");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Jika belum ada tabel, fallback ke menu default
    // $menus = [
    //     ['id' => 1, 'nama' => 'Mie Ayam Bakso', 'harga' => 22000],
    //     ['id' => 2, 'nama' => 'Mie Ayam Biasa', 'harga' => 15000],
    //     ['id' => 3, 'nama' => 'Bakso Komplit', 'harga' => 25000],
    //     ['id' => 4, 'nama' => 'Bakso Biasa', 'harga' => 18000],
    //     ['id' => 5, 'nama' => 'Es Teh Manis', 'harga' => 5000],
    //     ['id' => 6, 'nama' => 'Air Mineral', 'harga' => 3000],
    // ];
}

// Inisialisasi Keranjang
if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$message = "";
// Tambah item
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $menu_id = (int)$_GET['add'];
    foreach ($menus as $menu) {
        if ($menu['id'] == $menu_id) {
            if (isset($_SESSION['keranjang'][$menu_id])) {
                $_SESSION['keranjang'][$menu_id]['quantity']++;
            } else {
                $_SESSION['keranjang'][$menu_id] = [
                    'nama' => $menu['nama'],
                    'harga' => $menu['harga'],
                    'quantity' => 1
                ];
            }
            break;
        }
    }
    header('Location: kasir.php');
    exit();
}

// Kurangi item
if (isset($_GET['reduce']) && is_numeric($_GET['reduce'])) {
    $menu_id = (int)$_GET['reduce'];
    if (isset($_SESSION['keranjang'][$menu_id])) {
        $_SESSION['keranjang'][$menu_id]['quantity']--;
        if ($_SESSION['keranjang'][$menu_id]['quantity'] <= 0) {
            unset($_SESSION['keranjang'][$menu_id]);
        }
    }
    header('Location: kasir.php');
    exit();
}

// ===========================================
// 2. Logika CHECKOUT
// ===========================================
if (isset($_POST['checkout'])) {
    if (!empty($_SESSION['keranjang'])) {
        $total_bayar = 0;
        foreach ($_SESSION['keranjang'] as $item) {
            $total_bayar += ($item['harga'] * $item['quantity']);
        }

        try {
            // 1️⃣ Simpan ke tabel transaksi
            $stmt = $pdo->prepare("INSERT INTO transaksi (total_bayar) VALUES (:total)");
            $stmt->execute(['total' => $total_bayar]);
            
            // Ambil ID transaksi terakhir (foreign key)
            $transaksi_id = $pdo->lastInsertId();

            // 2️⃣ Simpan ke tabel detail_transaksi
            $stmtDetail = $pdo->prepare("
                INSERT INTO detail_transaksi (transaksi_id, menu_id, nama_menu, harga, quantity, subtotal)
                VALUES (:transaksi_id, :menu_id, :nama_menu, :harga, :quantity, :subtotal)
            ");

            foreach ($_SESSION['keranjang'] as $menu_id => $item) {
                $stmtDetail->execute([
                    'transaksi_id' => $transaksi_id,
                    'menu_id' => $menu_id,
                    'nama_menu' => $item['nama'],
                    'harga' => $item['harga'],
                    'quantity' => $item['quantity'],
                    'subtotal' => $item['harga'] * $item['quantity']
                ]);
            }

            // 3️⃣ Update session dan pesan
            $_SESSION['total_revenue'] += $total_bayar;
            $message = "🎉 Transaksi senilai Rp. " . number_format($total_bayar, 0, ',', '.') . " berhasil disimpan!";

            // Kosongkan keranjang setelah checkout
            $_SESSION['keranjang'] = [];

        } catch (Exception $e) {
            $message = "❌ Gagal menyimpan transaksi: " . $e->getMessage();
        }

        header('Location: kasir.php?msg=' . urlencode($message));
        exit();
    } else {
        $message = "Keranjang kosong. Tidak ada yang bisa dibayar.";
        header('Location: kasir.php?msg=' . urlencode($message));
        exit();
    }
}

// ===========================================
// 3. Logika Tambah ke Keranjang
// ===========================================
if (isset($_GET['add']) && is_numeric($_GET['add'])) {
    $menu_id = (int)$_GET['add'];

    // Cari menu berdasarkan ID
    foreach ($menus as $menu) {
        if ($menu['id'] == $menu_id) {
            if (isset($_SESSION['keranjang'][$menu_id])) {
                $_SESSION['keranjang'][$menu_id]['quantity']++;
            } else {
                $_SESSION['keranjang'][$menu_id] = [
                    'nama' => $menu['nama'],
                    'harga' => $menu['harga'],
                    'quantity' => 1
                ];
            }
            break;
        }
    }
    header('Location: kasir.php');
    exit();
}

// ===========================================
// 4. Pesan Notifikasi
// ===========================================
if (isset($_GET['msg'])) {
    $message = urldecode($_GET['msg']);
}

// ===========================================
// 5. Hitung Total Keranjang
// ===========================================
$total_bayar = 0;
foreach ($_SESSION['keranjang'] as $item) {
    $total_bayar += ($item['harga'] * $item['quantity']);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kasir UI</title>
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
    <a href="monitoring.php" class="sidebar-item active">Monitoring Keuangan</a>
    <div class="logout-btn" onclick="location.href='logout.php'">Logout</div>
</div>


<div class="main-content main-container">
    <div class="menu-area">
        <div class="menu-header">
            <h1>Kasir Menu</h1>
            <button class="edit-menu-btn" onclick="location.href='edit_menu.php'">Edit Menu</button>
        </div>

        <?php if ($message): ?>
            <p class="notif-message"><?php echo $message; ?></p>
        <?php endif; ?>

        <div class="menu-grid">
            <?php foreach ($menus as $menu): ?>
                <div class="menu-item">
                    <?php if (!empty($menu['foto'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($menu['foto']); ?>" alt="Foto Menu">
                    <?php else: ?>
                        <div class="menu-item-image"></div>
                    <?php endif; ?>
                    <p><strong><?php echo htmlspecialchars($menu['nama']); ?></strong></p>
                    <p class="menu-item-price">Rp. <?php echo number_format($menu['harga'], 0, ',', '.'); ?></p>
                    <a href="kasir.php?add=<?php echo $menu['id']; ?>" class="add-button">Tambah</a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    
    <div class="cart-area">
        <h2>Keranjang Belanja</h2>
        <?php if (empty($_SESSION['keranjang'])): ?>
            <p>Keranjang kosong. Silakan pilih menu.</p>
        <?php else: ?>
            <?php foreach ($_SESSION['keranjang'] as $id => $item): ?>
                <div class="cart-item">
                    <span><?php echo $item['quantity']; ?>x <?php echo htmlspecialchars($item['nama']); ?></span>
                    <span>Rp. <?php echo number_format($item['harga'] * $item['quantity'], 0, ',', '.'); ?></span>
                </div>
                <div class="cart-item-controls">
                        <a href="kasir.php?reduce=<?php echo $id; ?>" class="qty-btn">–</a>
                        <span><?php echo $item['quantity']; ?></span>
                        <a href="kasir.php?add=<?php echo $id; ?>" class="qty-btn">+</a>
                    </div>
            <?php endforeach; ?>

            <div class="cart-total">
                <span>TOTAL:</span>
                <span>Rp. <?php echo number_format($total_bayar, 0, ',', '.'); ?></span>
            </div>

            <form action="kasir.php" method="POST">
                <button type="submit" name="checkout" class="add-button checkout-button">Bayar (Checkout)</button>
            </form>
        <?php endif; ?>
    </div>
</div>

</body>
</html>
