<?php
// update_lists.php

// Bu script, cron job tarafından çağrılmak üzere tasarlanmıştır.
// Cloaker listelerini (UA, Referer, IP İtibar) harici kaynaklardan günceller.

// Gerekli yapılandırma dosyasını dahil et
// Bu dosya, veritabanı bağlantısını ($pdo_cloaker) ve update_cloaker_bot_lists fonksiyonunu sağlar.
require_once __DIR__ . '/config.php';

// Cron job tarafından çağrıldığını varsayarak hata raporlamayı kapat
// Canlı sistemde bu, çıktıyı temiz tutar ve hassas bilgilerin açığa çıkmasını engeller.
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);

// update_cloaker_bot_lists fonksiyonunu çağır
// Bu fonksiyon, gerekli listeleri çeker ve veritabanına kaydeder.
// Global $pdo_cloaker bağlantısını fonksiyona argüman olarak geçiriyoruz.
if (function_exists('update_cloaker_bot_lists')) {
    global $pdo_cloaker; // config.php'den gelen global PDO bağlantısı
    $result = update_cloaker_bot_lists($pdo_cloaker);
    
    // Cron job loguna yazmak için (isteğe bağlı)
    // Bu log dosyası, cron job'un başarılı olup olmadığını kontrol etmek için faydalıdır.
    $log_message = date('Y-m-d H:i:s') . " - Cron Job Update Result: " . ($result['message'] ?? 'Bilinmeyen Sonuç') . "\n";
    file_put_contents(CLOAKER_ROOT_PATH . 'cron_update.log', $log_message, FILE_APPEND);
} else {
    // Fonksiyon bulunamazsa hata mesajını logla
    $log_message = date('Y-m-d H:i:s') . " - Error: update_cloaker_bot_lists function not found. Check config.php inclusion.\n";
    file_put_contents(CLOAKER_ROOT_PATH . 'cron_update.log', $log_message, FILE_APPEND);
}

// Script tamamlandı
// Web tarayıcısı üzerinden erişimi engellemek için exit kullanılır.
exit();
?>