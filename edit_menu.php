<?php
session_start();
include 'config/db_config.php';

// Pastikan login
if (!isset($_SESSION['user_logged_in'])) {
    header('Location: index.php');
    exit();
}

// ==== Ambil data menu ====
try {
    $stmt = $pdo->query("SELECT * FROM menu");
    $menus = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $menus = [];
}

// ==== Tambah Menu ====
if (isset($_POST['tambah_menu'])) {
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $foto = null;

    if (!empty($_FILES['foto']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir);

        $fotoName = time() . "_" . basename($_FILES['foto']['name']);
        $targetFile = $uploadDir . $fotoName;

        if (move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile)) {
            $foto = $fotoName;
        }
    }

    $stmt = $pdo->prepare("INSERT INTO menu (nama, harga, foto) VALUES (?, ?, ?)");
    $stmt->execute([$nama, $harga, $foto]);
    header("Location: edit_menu.php");
    exit();
}

// ==== Edit Menu ====
if (isset($_POST['edit_menu'])) {
    $id = $_POST['id'];
    $nama = $_POST['nama'];
    $harga = $_POST['harga'];
    $foto = null;

    if (!empty($_FILES['foto']['name'])) {
        $uploadDir = "uploads/";
        if (!is_dir($uploadDir)) mkdir($uploadDir);

        $fotoName = time() . "_" . basename($_FILES['foto']['name']);
        $targetFile = $uploadDir . $fotoName;
        move_uploaded_file($_FILES['foto']['tmp_name'], $targetFile);
        $foto = $fotoName;

        $stmt = $pdo->prepare("UPDATE menu SET nama=?, harga=?, foto=? WHERE id=?");
        $stmt->execute([$nama, $harga, $foto, $id]);
    } else {
        $stmt = $pdo->prepare("UPDATE menu SET nama=?, harga=? WHERE id=?");
        $stmt->execute([$nama, $harga, $id]);
    }

    header("Location: edit_menu.php");
    exit();
}

// ==== Hapus Menu ====
if (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    $stmt = $pdo->prepare("DELETE FROM menu WHERE id=?");
    $stmt->execute([$id]);
    header("Location: edit_menu.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Menu</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>

<div class="header">
    <span class="menu-toggle">☰</span>
</div>

<div class="sidebar">
    <a href="dashboard.php" class="sidebar-nav-item">Dashboard</a>
    <a href="kasir.php" class="sidebar-nav-item">Kasir Menu</a>
    <a href="edit_menu.php" class="sidebar-nav-item active">Edit Menu</a>
    <div class="logout-btn" onclick="location.href='logout.php'">Logout</div>
</div>

<div class="main-content">
    <div class="menu-container">
        <div class="menu-header">
            <h1>Edit Menu</h1>
            <button class="edit-menu-btn" id="openModalBtn">+ Tambah Menu</button>
        </div>

        <div class="menu-grid">
            <?php foreach ($menus as $menu): ?>
                <div class="menu-item">
                    <?php if (!empty($menu['foto'])): ?>
                        <img src="uploads/<?php echo htmlspecialchars($menu['foto']); ?>" alt="Foto Menu">
                    <?php else: ?>
                        <div class="menu-item-image"></div>
                    <?php endif; ?>
                    <p><b><?php echo htmlspecialchars($menu['nama']); ?></b></p>
                    <p>Rp. <?php echo number_format($menu['harga'], 0, ',', '.'); ?></p>

                    <form action="edit_menu.php" method="POST" enctype="multipart/form-data" class="edit-form">
                        <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                        <input type="text" name="nama" value="<?php echo htmlspecialchars($menu['nama']); ?>" required>
                        <input type="number" name="harga" value="<?php echo $menu['harga']; ?>" required>
                        <input type="file" name="foto">
                        <button type="submit" name="edit_menu" class="btn-edit">Simpan</button>
                        <a href="edit_menu.php?hapus=<?php echo $menu['id']; ?>" class="btn-hapus" onclick="return confirm('Hapus menu ini?')">Hapus</a>
                    </form>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Modal Tambah Menu -->
<div id="tambahModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" id="closeModalBtn">&times;</span>
        <h3>Tambah Menu</h3>
        <form action="edit_menu.php" method="POST" enctype="multipart/form-data">
            <input type="text" name="nama" placeholder="Nama Menu" required>
            <input type="number" name="harga" placeholder="Harga" required>
            <input type="file" name="foto" required>
            <button type="submit" name="tambah_menu" class="btn-simpan">Simpan</button>
        </form>
    </div>
</div>

<script>
// Modal Tambah Menu
document.getElementById('openModalBtn').onclick = function() {
    document.getElementById('tambahModal').style.display = 'flex';
};

document.getElementById('closeModalBtn').onclick = function() {
    document.getElementById('tambahModal').style.display = 'none';
};

window.onclick = function(e) {
    if (e.target == document.getElementById('tambahModal')) {
        document.getElementById('tambahModal').style.display = 'none';
    }
};
</script>

</body>
</html>
