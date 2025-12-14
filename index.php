<?php
// index.php - Login dengan koneksi database

session_start();
require_once 'config/db_config.php'; // Tambahkan koneksi database

// Jika sudah login, arahkan ke dashboard
if (isset($_SESSION['user_logged_in'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $input_username = $_POST['username'];
    $input_password = $_POST['password'];

    try {
        // Query ambil user berdasarkan username
        $stmt = $pdo->prepare("SELECT * FROM user WHERE username = :username");
        $stmt->bindParam(':username', $input_username);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['password'] === md5($input_password)) {
            // Login berhasil
            $_SESSION['user_logged_in'] = true;
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            header('Location: dashboard.php');
            exit();
        } else {
            // Login gagal
            $error_message = 'Username atau password salah.';
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Login UI</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<div class="login-container">
    <div class="login-left"></div>
    <div class="login-right">
        <h1 style="position: absolute; top: 20px; left: 30%; color: #1e1e3f; font-size: 30px; font-weight: bold; margin-top: 30px;">Pondok Baso Parayangan</h1>
        <form action="index.php" method="POST" class="login-form">
            <?php if ($error_message): ?>
                <p style="color: red; text-align: center; margin-bottom: 10px; padding: 5px; border: 1px solid red; background-color: #ffebeb; border-radius: 4px;"><?php echo $error_message; ?></p>
            <?php endif; ?>
            <div class="input-group">
                <label for="username">USERNAME</label>
                <input type="text" id="username" name="username" placeholder="Masukan Username Anda" required>
            </div>
            <div class="input-group">
                <label for="password">PASSWORD</label>
                <input type="password" id="password" name="password" placeholder="Masukan Password Anda" required>
            </div>
            
            <!-- <a href="#" class="forgot-link" style="float: left;">Belum Punya Akun?</a> -->
            <button type="submit" name="login" class="login-button">Login</button>
        </form>
    </div>
</div>
</body>
</html>
