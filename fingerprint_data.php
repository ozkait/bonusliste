<?php
// api/fingerprint_data.php

require_once __DIR__ . '/../config.php'; // config.php bir üst dizinde
// Bu script sadece POST isteklerini kabul etmeli
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('HTTP/1.1 405 Method Not Allowed');
    header('Allow: POST');
    echo json_encode(['status' => 'error', 'message' => 'Method Not Allowed']);
    exit();
}

// Gelen JSON verisini al
$input = file_get_contents('php://input');
$fingerprint_data = json_decode($input, true);

// Verinin varlığını ve geçerliliğini kontrol et
if (json_last_error() !== JSON_ERROR_NONE || !is_array($fingerprint_data)) {
    header('HTTP/1.1 400 Bad Request');
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit();
}

$remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
$user_agent = $fingerprint_data['userAgent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? 'N/A');

// Burada fingerprint_data'yı işleyeceksiniz.
// Örneğin:
// 1. Veritabanına kaydet (ileride analiz için)
// 2. Bot olup olmadığına dair karar ver (şimdilik basit bir mantıkla başlayabiliriz)
// 3. Cloaker'ın karar mekanizmasına (config.php) bir şekilde ilet.

// Şimdilik, verileri log dosyasına kaydedelim ve basit bir karar döndürelim.
// Daha sonra buraya daha gelişmiş bot tespit mantığı eklenecek.

$decision = 'human'; // Varsayılan olarak insan varsayalım
$reason_details = [];

// Örnek basit bot tespit mantığı (İyileştirilmesi gerekecek!)
// Bu kısım, topladığınız fingerprint özelliklerine göre çok daha karmaşık hale getirilebilir.
if (strpos(strtolower($user_agent), 'headlesschrome') !== false) {
    $decision = 'bot';
    $reason_details[] = 'Headless Chrome UA';
}
if (($fingerprint_data['canvasHash'] ?? '') === 'N/A') { // Canvas render edemeyen botlar
    $decision = 'bot';
    $reason_details[] = 'Canvas N/A';
}
if (($fingerprint_data['plugins'] ?? '') === '') { // Hiç eklentisi olmayan tarayıcılar (bazı botlar)
    // Bu kuralı dikkatli kullanın, bazı gerçek kullanıcıların da eklentisi olmayabilir.
    // $decision = 'bot'; 
    // $reason_details[] = 'No Plugins';
}
if (($fingerprint_data['webglVendor'] ?? '') === 'N/A' && ($fingerprint_data['webglRenderer'] ?? '') === 'N/A') {
    $decision = 'bot';
    $reason_details[] = 'WebGL N/A';
}
// Veya belirli bir bot fingerprint hash'i ile eşleşme kontrolü (ileride)
// if (in_array($fingerprint_data['canvasHash'], ['known_bot_canvas_hash_1', 'known_bot_canvas_hash_2'])) {
//     $decision = 'bot';
//     $reason_details[] = 'Known Canvas Hash';
// }


// Loglama (mevcut cloaker log sisteminden bağımsız olarak, sadece fingerprint verisi için)
$fingerprint_log_file = CLOAKER_ROOT_PATH . 'fingerprint_activity.log';
$log_entry = date('Y-m-d H:i:s') . " - IP: {$remote_ip} - UA: {$user_agent} - Decision: {$decision} - Reason: " . implode(', ', $reason_details) . " - Data: " . json_encode($fingerprint_data) . "\n";
file_put_contents($fingerprint_log_file, $log_entry, FILE_APPEND);

// Yanıt döndür
// JS tarafındaki çerez kaydetme için bu 'decision' kullanılır
echo json_encode([
    'status' => 'success',
    'message' => 'Fingerprint data received and processed.',
    'decision' => $decision, // bot veya human
    'reasons' => $reason_details,
    'ip' => $remote_ip
]);
exit();
?>