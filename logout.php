<?php
require_once 'config.php'; // Oturumu başlatmak için config dosyasını dahil et

// Tüm oturum değişkenlerini sıfırla
$_SESSION = array();

// Oturum çerezini sil (varsa)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Oturumu tamamen yok et
session_destroy();

// Anasayfaya veya giriş sayfasına yönlendir
redirect('index.php');
?>