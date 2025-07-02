<?php
// update_bot_lists_cron.php
// Bu dosya bir cron job tarafından çalıştırılmak üzere tasarlanmıştır.

// PHP hata raporlamayı sadece loglara yap, ekrana basma (cron job'larda önemlidir)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL);
// Hata log dosyasının yolunu belirt (web klasörü dışında olması güvenlik için daha iyi)
ini_set('error_log', '/path/to/your/custom_php_errors.log'); // Burayı kendi sunucunuza göre ayarlayın

// config.php dosyasını dahil et (cron scriptinin konumuna göre yolunu ayarla)
// Eğer update_bot_lists_cron.php ana dizinde ise:
require_once __DIR__ . '/config.php'; 
// Eğer update_bot_lists_cron.php başka bir klasörde ise (örn: cron/update_bot_lists_cron.php):
// require_once __DIR__ . '/../config.php'; // Bir üst dizin için

// Admin paneli isteği değilmiş gibi davran, böylece cloaker mantığı çalışmaz
// Ancak log_cloaker_action fonksiyonu tanımlı olmalı.
// Cloaker ayarlarını doğrudan okumalıyız.

// config.php'deki update_cloaker_bot_lists fonksiyonunu çağır
// $pdo objesinin update_cloaker_bot_lists fonksiyonuna doğru şekilde geçtiğinden emin olun
// config.php'nin en son sürümünde $pdo objesi global olarak tanımlı veya
// admin_header.php'den çekiliyordu. Cron job doğrudan çalıştığı için
// config.php içindeki $pdo'nun init edildiği bloğu kontrol etmeniz gerekebilir.

// Geçici olarak CLOAKER_ROOT_PATH'i tanımlamamız gerekiyorsa:
// define('CLOAKER_ROOT_PATH', '/home/mszffsbpwz/bonuslistesi.xyz/'); // Sunucunuzdaki kök yolu

// config.php'nin tamamının çalışmasını bekliyoruz.
// Fonksiyon define edildiği için direkt çağırabiliriz.
if (function_exists('update_cloaker_bot_lists')) {
    // config.php'nin en altında $pdo'nun tanımlandığı kısmı cron için uygun hale getirelim.
    // Oradaki global $pdo; if(!isset($pdo) ... bloğu çalışacaktır.
    global $pdo; // $pdo objesi cron çalıştırıldığında config.php'den gelecektir.
    if (isset($pdo)) {
        $result = update_cloaker_bot_lists($pdo);
        // Cron loguna yazdır
        error_log("Bot Listesi Güncelleme Cron Sonucu: " . $result['message']);
    } else {
        error_log("Bot Listesi Güncelleme Cron Hatası: PDO objesi cron scriptinde tanımlı değil.");
    }
} else {
    error_log("Bot Listesi Güncelleme Cron Hatası: update_cloaker_bot_lists fonksiyonu config.php'de bulunamadı.");
}
?>