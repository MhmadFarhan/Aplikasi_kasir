<?php
// logout.php
session_start();

// Hapus semua variabel session
$_SESSION = array();

// Hancurkan session
session_destroy();

// Arahkan kembali ke halaman login (index.php)
header("Location: index.php");
exit;
?>