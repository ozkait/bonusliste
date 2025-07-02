<?php
// admin/manage_cloaker.php

require_once __DIR__ . '/includes/admin_header.php'; // Oturumu başlatmak ve DB bağlantısı için admin_header.php'yi dahil et

$message = ''; // Mesajları göstermek için boş bir başlangıç

// Fonksiyon: IP'nin CIDR bloğunda olup olmadığını kontrol eder
if (!function_exists('ip_in_cidr')) {
    function ip_in_cidr($ip, $cidr) {
        if (strpos($cidr, '/') === false) { // Tek IP adresi ise
            return $ip === $cidr;
        }
        list($subnet, $mask) = explode('/', $cidr);
        if ((long2ip(ip2long($ip) & ~((1 << (32 - $mask)) - 1))) == $subnet) {
            return true;
        }
        return false;
    }
}

// POST isteği geldiğinde cloaker ayarlarını güncelle veya logları temizle
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Logları temizleme işlemi (ilk kontrol)
    if (isset($_POST['action']) && $_POST['action'] === 'clear_cloaker_logs') {
        $log_file_to_clear = CLOAKER_ROOT_PATH . 'cloaker_activity.log';
        if (file_exists($log_file_to_clear) && is_writable($log_file_to_clear)) {
            if (file_put_contents($log_file_to_clear, '') !== false) {
                $message .= '<div class="alert alert-success">Cloaker logları başarıyla temizlendi!</div>';
            } else {
                $message .= '<div class="alert alert-danger">Cloaker logları temizlenirken bir hata oluştu. Dosya izinlerini kontrol edin.</div>';
            }
        } else {
            $message .= '<div class="alert alert-danger">Cloaker log dosyası bulunamadı veya yazma izni yok.</div>';
        }
    }
    // IP Yasaklama işlemi
    elseif (isset($_POST['action']) && $_POST['action'] === 'blacklist_ip' && isset($_POST['ip_to_blacklist'])) {
        $ip_to_blacklist = trim($_POST['ip_to_blacklist']);
        
        // Mevcut kara listeyi veritabanından çek
        $stmt_current_blacklist = $pdo->query("SELECT setting_value FROM settings WHERE setting_key = 'cloaker_blacklist_ips'");
        $current_blacklist_ips_raw = $stmt_current_blacklist->fetchColumn() ?? '';
        $blacklist_ips_array = array_map('trim', explode(',', $current_blacklist_ips_raw));
        
        // IP'nin zaten listede olup olmadığını CIDR desteği ile kontrol et
        $is_already_blacklisted_on_manage = false;
        foreach($blacklist_ips_array as $bl_ip_setting) {
            if (ip_in_cidr($ip_to_blacklist, $bl_ip_setting)) {
                $is_already_blacklisted_on_manage = true;
                break;
            }
        }

        if (!filter_var($ip_to_blacklist, FILTER_VALIDATE_IP)) {
            $message .= '<div class="alert alert-danger">Geçersiz IP adresi formatı.</div>';
        } elseif (!$is_already_blacklisted_on_manage) {
            $blacklist_ips_array[] = $ip_to_blacklist;
            // Boş değerleri filtrele ve tekrar birleştir
            $new_blacklist_ips = implode(',', array_filter($blacklist_ips_array));
            
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES ('cloaker_blacklist_ips', ?, 'Kara Liste IP Adresleri') ON DUPLICATE KEY UPDATE setting_value = ?");
            if ($stmt->execute([$new_blacklist_ips, $new_blacklist_ips])) {
                $message .= '<div class="alert alert-success">IP adresi <strong>' . htmlspecialchars($ip_to_blacklist) . '</strong> başarıyla kara listeye alındı!</div>';
            } else {
                $error_info = $stmt->errorInfo();
                $message .= '<div class="alert alert-danger">IP adresi kara listeye alınırken bir hata oluştu: ' . ($error_info[2] ?? 'Bilinmeyen SQL hatası.') . '</div>';
            }
        } else {
            $message .= '<div class="alert alert-info">IP adresi <strong>' . htmlspecialchars($ip_to_blacklist) . '</strong> zaten kara listede bulunuyor.</div>';
        }

    }
    // IP Önbelleğini Temizleme işlemi
    elseif (isset($_POST['action']) && $_POST['action'] === 'clear_ip_cache') {
        $cache_file_to_clear = CLOAKER_ROOT_PATH . 'cloaker_ip_cache.json';
        if (file_exists($cache_file_to_clear) && is_writable($cache_file_to_clear)) {
            if (file_put_contents($cache_file_to_clear, '{}') !== false) { // Boş bir JSON objesi yaz
                $message .= '<div class="alert alert-success">IP önbelleği başarıyla temizlendi!</div>';
            } else {
                $message .= '<div class="alert alert-danger">IP önbelleği temizlenirken bir hata oluştu. Dosya izinlerini kontrol edin.</div>';
            }
        } else {
            $message .= '<div class="alert alert-danger">IP önbellek dosyası bulunamadı veya yazma izni yok.</div>';
        }
    }


    // Ayar kaydetme işlemi (log temizleme veya IP yasaklama dışındaki POST istekleri için)
    $cloaker_status = isset($_POST['cloaker_status']) ? '1' : '0';
    $cloaker_real_url = trim($_POST['cloaker_real_url'] ?? '');
    $cloaker_compliant_desktop_url = trim($_POST['cloaker_compliant_desktop_url'] ?? '');
    $cloaker_compliant_mobile_url = trim($_POST['cloaker_compliant_mobile_url'] ?? '');
    $cloaker_fallback_url = trim($_POST['cloaker_fallback_url'] ?? '');
    $cloaker_whitelist_ips = trim($_POST['cloaker_whitelist_ips'] ?? '');
    
    // cloaker_blacklist_ips burada doğrudan formdan gelmediği için ( blacklist_ip action'ı hariç) dikkatli olmalıyız.
    // Eğer sadece form gönderildiğinde bu alan güncellenecekse, bu satırı kullanabiliriz.
    // Ancak IP yasaklama işlemi ayrı olduğu için, sadece form alanından gelen değeri alıyoruz.
    if (!isset($_POST['action']) || ($_POST['action'] !== 'blacklist_ip')) {
        $cloaker_blacklist_ips = trim($_POST['cloaker_blacklist_ips'] ?? '');
    } else {
        // Eğer IP yasaklama işlemi yapıldıysa, bu değer zaten yukarıda güncellenmiş ve DB'ye kaydedilmiştir.
        // Bu yüzden, burada formdan gelen değeri değil, mevcut DB değerini kullanmalıyız.
        // Aksi takdirde, diğer ayarlar güncellenirken kara liste ayarı ezilebilir.
        // Bunu düzeltmek için, ayarları tekrar çekeceğimiz kısımda ele alacağız.
    }


    $cloaker_enable_logging = isset($_POST['cloaker_enable_logging']) ? '1' : '0';
    $cloaker_logging_level = trim($_POST['cloaker_logging_level'] ?? 'basic');

    $cloaker_redirect_target_url = trim($_POST['cloaker_redirect_target_url'] ?? ''); // Bu alan formda yok, kaldırılmalı veya eklenmeli
    $cloaker_allowed_devices = isset($_POST['cloaker_allowed_devices']) ? implode(',', $_POST['cloaker_allowed_devices']) : ''; // Bu alan formda yok, kaldırılmalı veya eklenmeli
    $cloaker_allowed_countries = trim($_POST['cloaker_allowed_countries'] ?? ''); // Bu alan formda yok, kaldırılmalı veya eklenmeli
    $cloaker_allowed_languages = trim($_POST['cloaker_allowed_languages'] ?? ''); // Bu alan formda yok, kaldırılmalı veya eklenmeli
    $cloaker_ad_platforms = isset($_POST['cloaker_ad_platforms']) ? implode(',', $_POST['cloaker_ad_platforms']) : ''; // Bu alan formda yok, kaldırılmalı veya eklenmeli

    $cloaker_wp_ad_bots = isset($_POST['cloaker_wp_ad_bots']) ? implode(',', $_POST['cloaker_wp_ad_bots']) : '';

    $cloaker_ua_whitelist = trim($_POST['cloaker_ua_whitelist'] ?? '');
    $cloaker_ua_blacklist = trim($_POST['cloaker_ua_blacklist'] ?? '');
    $cloaker_referer_blacklist = trim($_POST['cloaker_referer_blacklist'] ?? '');
    
    $cloaker_enable_time_rule = isset($_POST['cloaker_enable_time_rule']) ? '1' : '0';
    $cloaker_rule_start_time = trim($_POST['cloaker_rule_start_time'] ?? '00:00');
    $cloaker_rule_end_time = trim($_POST['cloaker_rule_end_time'] ?? '23:59');
    $cloaker_time_rule_action = trim($_POST['cloaker_time_rule_action'] ?? 'wordpress');

    $cloaker_enable_honeypot = isset($_POST['cloaker_enable_honeypot']) ? '1' : '0'; // Yeni honeypot ayarı
    $cloaker_enable_honeypot_links = isset($_POST['cloaker_enable_honeypot_links']) ? '1' : '0'; // Yeni honeypot link ayarı
    $cloaker_enable_dynamic_fallback = isset($_POST['cloaker_enable_dynamic_fallback']) ? '1' : '0'; // Yeni dinamik fallback ayarı
    $cloaker_dynamic_fallback_content = trim($_POST['cloaker_dynamic_fallback_content'] ?? ''); // Yeni dinamik fallback içerik ayarı


    $cloaker_enable_cookie_persistence = isset($_POST['cloaker_enable_cookie_persistence']) ? '1' : '0';
    $cloaker_cookie_lifetime_hours = (int)($_POST['cloaker_cookie_lifetime_hours'] ?? 24);


    $cloaker_settings_to_update = [
        'cloaker_status' => 'Cloaker Açık/Kapalı Durumu',
        'cloaker_real_url' => 'Mobil İçerik URL Yolu',
        'cloaker_compliant_desktop_url' => 'Masaüstü/Bot için WP Placeholder',
        'cloaker_compliant_mobile_url' => 'Mobil Bot İçerik URL Yolu (Kullanılmaz)',
        'cloaker_fallback_url' => 'Cloaker Fallback URL',
        'cloaker_whitelist_ips' => 'Beyaz Liste IP Adresleri',
        'cloaker_blacklist_ips' => 'Kara Liste IP Adresleri', // Bu ayarı formdan gelenle güncelleme, sadece blacklist_ip action'ında güncellensin.
        'cloaker_enable_logging' => 'Cloaker Loglamayı Etkinleştir',
        'cloaker_logging_level' => 'Cloaker Loglama Seviyesi',
        // Aşağıdaki 4 ayar formda mevcut değil, ya eklenmeli ya da kaldırılmalı
        // 'cloaker_redirect_target_url' => 'Yönlendirilecek Web Sitesi Adresi',
        // 'cloaker_allowed_devices' => 'İzin Verilen Cihazlar',
        // 'cloaker_allowed_countries' => 'İzin Verilen Ülkeler Listesi',
        // 'cloaker_allowed_languages' => 'İzin Verilen Dil',
        // 'cloaker_ad_platforms' => 'Reklam Verilen Platform Seçimi',
        'cloaker_wp_ad_bots' => 'WordPress Görecek Reklam Botları',
        'cloaker_ua_whitelist' => 'User-Agent Beyaz Liste',
        'cloaker_ua_blacklist' => 'User-Agent Kara Liste',
        'cloaker_referer_blacklist' => 'Referer Kara Liste',
        'cloaker_enable_time_rule' => 'Zaman Bazlı Kuralı Etkinleştir',
        'cloaker_rule_start_time' => 'Kural Başlangıç Saati',
        'cloaker_rule_end_time' => 'Kural Bitiş Saati',
        'cloaker_time_rule_action' => 'Saat Kuralı Aktifken Gösterilecek İçerik',
        'cloaker_enable_honeypot' => 'Honeypot Korumasını Etkinleştir', // Yeni eklenen
        'cloaker_enable_honeypot_links' => 'Honeypot Gizli Linklerini Etkinleştir', // Yeni eklenen
        'cloaker_enable_dynamic_fallback' => 'Dinamik Fallback İçeriği Etkinleştir', // Yeni eklenen
        'cloaker_dynamic_fallback_content' => 'Dinamik Fallback İçeriği (HTML)', // Yeni eklenen
        'cloaker_enable_cookie_persistence' => 'Çerez Tabanlı Kalıcılığı Etkinleştir',
        'cloaker_cookie_lifetime_hours' => 'Çerez Ömrü (Saat)'
    ];

    $cloaker_update_successful = true;
    if (!isset($_POST['action']) || ($_POST['action'] !== 'clear_cloaker_logs' && $_POST['action'] !== 'blacklist_ip' && $_POST['action'] !== 'clear_ip_cache')) {
        foreach ($cloaker_settings_to_update as $key => $desc) {
            $value = $$key;
            // cloaker_blacklist_ips anahtarını, eğer blacklist_ip action'ı ise atla, çünkü o zaten yukarıda güncellendi
            if ($key === 'cloaker_blacklist_ips' && isset($_POST['action']) && $_POST['action'] === 'blacklist_ip') {
                 continue;
            }
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            if (!$stmt->execute([$key, $value, $desc, $value])) {
                $error_info = $stmt->errorInfo();
                $message .= '<div class="alert alert-danger">Cloaker ayarları güncellenirken bir hata oluştu: ' . htmlspecialchars($key) . ' - ' . ($error_info[2] ?? 'Bilinmeyen SQL hatası.') . '</div>';
                $cloaker_update_successful = false;
            }
        }

        if ($cloaker_update_successful) {
            $message .= '<div class="alert alert-success">Cloaker ayarları başarıyla güncellendi!</div>';
        }
    }

    // HER POST İSTEĞİNDEN SONRA AYARLARI TEKRAR VERİTABANINDAN ÇEK
    // Bu kısım, tüm ayarların güncel halini form alanlarına yansıtmak için KRİTİKTİR.
    try {
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $current_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Cloaker ayarları çekilirken veritabanı hatası: " . $e->getMessage());
        $current_settings = [];
        $message .= '<div class="alert alert-danger">Cloaker ayarları yüklenirken bir veritabanı hatası oluştu.</div>';
    }

} else {
    // Normal sayfa yüklemede (GET isteği) ayarları veritabanından çek
    try {
        $stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
        $current_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
    } catch (PDOException $e) {
        error_log("Cloaker ayarları çekilirken veritabanı hatası: " . $e->getMessage());
        $current_settings = [];
        $message .= '<div class="alert alert-danger">Cloaker ayarları yüklenirken bir veritabanı hatası oluştu.</div>';
    }
}

// --- Checkbox Ayarlarını Diziye Ayrıştırma (her zaman güncel settings'e göre olmalı) ---
$parsed_wp_ad_bots = !empty($current_settings['cloaker_wp_ad_bots']) ? explode(',', $current_settings['cloaker_wp_ad_bots']) : [];
// Bu ikisi formda olmadığı için burada parse edilmelerine gerek yok, ancak isterseniz ekleyebilirsiniz.
// $parsed_allowed_devices = !empty($current_settings['cloaker_allowed_devices']) ? explode(',', $current_settings['cloaker_allowed_devices']) : [];
// $parsed_ad_platforms = !empty($current_settings['cloaker_ad_platforms']) ? explode(',', $current_settings['cloaker_ad_platforms']) : [];
// --- Ayrıştırma Sonu ---


$page_title = 'Cloaker Ayarları';

// Log dosyasının içeriğini okuma ve tablo için parse etme
$log_file_path = CLOAKER_ROOT_PATH . 'cloaker_activity.log';
$log_content = 'Log dosyası bulunamadı veya boş.';
$log_entries_parsed = []; // Tablo için ayrıştırılmış loglar

// --- CLOAKER İstatistiklerini Çekme ve Hesaplama (Bu kısım buraya taşındı) ---
$total_cloaker_actions = 0;
$wp_views_cloaker = 0;
$mobile_views_cloaker = 0;
$total_bot_detections = 0;
$total_blacklisted_blocks = 0;
$top_blocked_ips = [];
$recent_cloaker_actions = []; // Sadece dashboard'da değil, burada da kullanabiliriz

if (file_exists($log_file_path) && is_readable($log_file_path)) {
    $lines = file($log_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    $lines = array_reverse($lines); // En yeni loglar üstte

    $max_lines_for_stats = 500; // İstatistikler için maksimum log satırı
    $parsed_count_for_stats = 0;

    foreach ($lines as $line) {
        if ($parsed_count_for_stats >= $max_lines_for_stats) {
            break;
        }

        if (preg_match('/^\[(.*?)\] ACTION: (.*?) \| REASON: (.*?) \| TARGET: (.*?) \| IP: (.*?) \| COUNTRY: (.*?) \| ISP: (.*?)(?: \| UA: (.*?))?(?: \| REFERER: (.*?))?(?: \| REQUEST_URI: (.*?))?(?: \| DETAILS: ({.*}))?$/', $line, $matches)) {
            $timestamp = $matches[1] ?? '';
            $action = $matches[2] ?? '';
            $reason = $matches[3] ?? '';
            $target = $matches[4] ?? '';
            $ip = $matches[5] ?? '';
            $country = $matches[6] ?? '';
            $isp = $matches[7] ?? '';
            $details_json = $matches[11] ?? '{}';

            $total_cloaker_actions++;

            if (strpos($target, 'Cloaker Sayfası (WP)') !== false || strpos($target, 'WordPress (Internal)') !== false) {
                $wp_views_cloaker++;
            } elseif (strpos($target, 'Reklam Sayfası (Mobil)') !== false || strpos($target, 'Main Script (Internal)') !== false) {
                $mobile_views_cloaker++;
            }

            if (strpos($reason, 'Bot Tespiti') !== false || strpos($reason, 'Reklam Botu İzni') !== false) {
                $total_bot_detections++;
            }
            if (strpos($reason, 'Kara Listeye Alınan') !== false || strpos($reason, 'Honeypot Koruması') !== false) {
                $total_blacklisted_blocks++;
                if ($ip !== 'N/A' && filter_var($ip, FILTER_VALIDATE_IP)) {
                    $top_blocked_ips[$ip] = ($top_blocked_ips[$ip] ?? 0) + 1;
                }
            }
        }
        $parsed_count_for_stats++;
    }
    arsort($top_blocked_ips);
    $top_blocked_ips = array_slice($top_blocked_ips, 0, 5, true);


    // Detaylı log görünümü için ayrı bir döngü veya yukarıdaki döngüyü kullanabiliriz
    // Sadece tablo için logları ayrıştır (limitli sayıda)
    $max_lines_for_display = 200;
    $parsed_count_for_display = 0;
    foreach ($lines as $line) { // Tüm logları tekrar oku veya yukarıda okunanı kullan
        if ($parsed_count_for_display >= $max_lines_for_display) {
            break;
        }
        $entry = [];
        if (preg_match('/^\[(.*?)\] ACTION: (.*?) \| REASON: (.*?) \| TARGET: (.*?) \| IP: (.*?) \| COUNTRY: (.*?) \| ISP: (.*?)(?: \| UA: (.*?))?(?: \| REFERER: (.*?))?(?: \| REQUEST_URI: (.*?))?(?: \| DETAILS: ({.*}))?$/', $line, $matches)) {
            $entry['timestamp'] = $matches[1] ?? '';
            $entry['action'] = $matches[2] ?? '';
            $entry['reason'] = $matches[3] ?? '';
            $entry['target'] = $matches[4] ?? '';
            $entry['ip'] = $matches[5] ?? '';
            $entry['country'] = $matches[6] ?? '';
            $entry['isp'] = $matches[7] ?? '';
            $entry['ua'] = $matches[8] ?? '';
            $entry['referer'] = $matches[9] ?? '';
            $entry['request_uri'] = $matches[10] ?? '';
            $entry['details'] = $matches[11] ?? '';
            
            $decoded_details = json_decode($entry['details'], true) ?? [];
            $entry['is_google_ad_bot'] = $decoded_details['is_google_ad_bot'] ?? 'no';

            // --- TERİMLERİ TÜRKÇELEŞTİRME BAŞLANGICI ---
            switch ($entry['action']) {
                case 'DISPLAY': $entry['action'] = 'Görüntüleme'; break;
                case 'REDIRECT': $entry['action'] = 'Yönlendirme'; break;
                default: break;
            }

            switch ($entry['reason']) {
                case 'Desktop User/Bot/Whitelisted IP': $entry['reason'] = 'Bilgisayar/Bot/İzinVerilen IP'; break;
                case 'Blacklisted IP': $entry['reason'] = 'Kara Listeye Alınan IP'; break;
                case 'Referer Kara Listede': $entry['reason'] = 'Kara Listeye Alınan Referer'; break;
                case 'User-Agent Kara Listede': $entry['reason'] = 'Kara Listeye Alınan User-Agent'; break;
                case 'Mobile User (Normal Akış)': $entry['reason'] = 'Mobil Kullanıcı (Normal Akış)'; break;
                case 'Blacklisted IP, No Fallback URL': $entry['reason'] = 'Kara Listeye Alınan IP, Yedek URL Yok'; break;
                case 'User-Agent Beyaz Listede': $entry['reason'] = 'User-Agent Beyaz Listede'; break;
                case 'Reklam Botu İzni: google_ads': $entry['reason'] = 'Reklam Botu İzni: Google ADS'; break;
                case 'Reklam Botu İzni: meta': $entry['reason'] = 'Reklam Botu İzni: Meta'; break;
                case 'Reklam Botu İzni: tiktok': $entry['reason'] = 'Reklam Botu İzni: TikTok'; break;
                case 'Reklam Botu İzni: twitter': $entry['reason'] = 'Reklam Botu İzni: Twitter'; break;
                case 'Reklam Botu İzni: other': $entry['reason'] = 'Reklam Botu İzni: Diğer'; break;
                case 'Bot Tespiti': $entry['reason'] = 'Bot Tespiti'; break;
                case 'Reklam Platformu Tıklaması (google_ads)': $entry['reason'] = 'Reklam Tıklaması (Google ADS)'; break;
                case 'Reklam Platformu Tıklaması (meta)': $entry['reason'] = 'Reklam Tıklaması (Meta)'; break;
                case 'Reklam Platformu Tıklaması (tiktok)': $entry['reason'] = 'Reklam Tıklaması (TikTok)'; break;
                case 'Reklam Platformu Tıklaması (twitter)': $entry['reason'] = 'Reklam Tıklaması (Twitter)'; break;
                case 'Reklam Platformu Tıklaması (other_ad)': $entry['reason'] = 'Reklam Tıklaması (Diğer)'; break;
                case 'Zaman Kuralı Aktif': $entry['reason'] = 'Zaman Kuralı Aktif'; break;
                case 'Bilgisayar Cihazı': $entry['reason'] = 'Bilgisayar Cihazı'; break;
                case 'Ülke Filtresi Reddedildi: ' . ($decoded_details['country'] ?? ''): $entry['reason'] = 'Ülke Filtresi Reddedildi: ' . ($decoded_details['country'] ?? ''); break;
                case 'Dil Filtresi Reddedildi: ' . ($decoded_details['lang'] ?? ''): $entry['reason'] = 'Dil Filtresi Reddedildi: ' . ($decoded_details['lang'] ?? ''); break;
                case 'Cihaz Filtresi Reddedildi: ' . ($decoded_details['device'] ?? ''): $entry['reason'] = 'Cihaz Filtresi Reddedildi: ' . ($decoded_details['device'] ?? ''); break;
                case 'Honeypot Koruması': $entry['reason'] = 'Honeypot Koruması'; break;
                case 'Çerez Tabanlı Karar': $entry['reason'] = 'Çerez Tabanlı Karar'; break;
                default: break;
            }

            switch ($entry['target']) {
                case 'WordPress (Internal)': $entry['target'] = 'Cloaker Sayfası (WP)'; break;
                case 'Main Script (Internal)': $entry['target'] = 'Reklam Sayfası (Mobil)'; break;
                case 'N/A': $entry['target'] = 'Bilinmiyor'; break;
                default: break;
            }
            // --- TERİMLERİ TÜRKÇELEŞTİRME SONU ---

            $log_entries_parsed[] = $entry;
            $parsed_count_for_display++;
        }
    }

    if (count($lines) > $max_lines_for_display) {
        $log_entries_parsed[] = [
            'timestamp' => '...',
            'action' => '',
            'reason' => 'Log dosyası çok büyük, sadece son ' . $max_lines_for_display . ' kayıt gösteriliyor.',
            'target' => '',
            'ip' => '',
            'country' => '',
            'isp' => '',
            'ua' => '', 'referer' => '', 'request_uri' => '', 'details' => '', 'is_google_ad_bot' => 'no'
        ];
    }

} else {
    $log_entries_parsed[] = [
        'timestamp' => '',
        'action' => '',
        'reason' => 'Log dosyası mevcut değil veya okunabilir değil.',
        'target' => '',
        'ip' => '',
        'country' => '',
        'isp' => '',
        'ua' => '', 'referer' => '', 'request_uri' => '', 'details' => 'Lütfen cloaker_activity.log dosyasının var olduğundan ve yazma/okuma izinlerinin doğru olduğundan emin olun.', 'is_google_ad_bot' => 'no'
    ];
}
?>

<div class="page-body">
    <div class="container-xl">
        <?php echo $message; ?>
        
        <div class="row row-cards g-3 mb-4"> <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                               <span class="avatar avatar-2 bg-purple-lt">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-select-all"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 8m0 1a1 1 0 0 1 1 -1h6a1 1 0 0 1 1 1v6a1 1 0 0 1 -1 1h-6a1 1 0 0 1 -1 -1z" /><path d="M12 20v.01" /><path d="M16 20v.01" /><path d="M8 20v.01" /><path d="M4 20v.01" /><path d="M4 16v.01" /><path d="M4 12v.01" /><path d="M4 8v.01" /><path d="M4 4v.01" /><path d="M8 4v.01" /><path d="M12 4v.01" /><path d="M16 4v.01" /><path d="M20 4v.01" /><path d="M20 8v.01" /><path d="M20 12v.01" /><path d="M20 16v.01" /><path d="M20 20v.01" /></svg>   </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    TOPLAM CLOAKER AKSİYONU
                                </div>
                                <div class="text-muted">
                                    <?php echo $total_cloaker_actions; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-2 bg-green-lt">
                                     <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-brand-wordpress"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9.5 9h3" /><path d="M4 9h2.5" /><path d="M11 9l3 11l4 -9" /><path d="M5.5 9l3.5 11l3 -7" /><path d="M18 11c.177 -.528 1 -1.364 1 -2.5c0 -1.78 -.776 -2.5 -1.875 -2.5c-.898 0 -1.125 .812 -1.125 1.429c0 1.83 2 2.058 2 3.571z" /><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0" /></svg>  </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    WORDPRESS GÖRÜNTÜLEME
                                </div>
                                <div class="text-muted">
                                    <?php echo $wp_views_cloaker; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-2 bg-yellow-lt">
                                    <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-device-mobile"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 5a2 2 0 0 1 2 -2h8a2 2 0 0 1 2 2v14a2 2 0 0 1 -2 2h-8a2 2 0 0 1 -2 -2v-14z" /><path d="M11 4h2" /><path d="M12 17v.01" /></svg></span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    MOBİL SİTE GÖRÜNTÜLEME
                                </div>
                                <div class="text-muted">
                                    <?php echo $mobile_views_cloaker; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto">
                                <span class="avatar avatar-2 bg-red-lt">
                                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-alert-hexagon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M19.875 6.27c.7 .398 1.13 1.143 1.125 1.948v7.284c0 .809 -.443 1.555 -1.158 1.948l-6.75 4.27a2.269 2.269 0 0 1 -2.184 0l-6.75 -4.27a2.225 2.225 0 0 1 -1.158 -1.948v-7.285c0 -.809 .443 -1.554 1.158 -1.947l6.75 -3.98a2.33 2.33 0 0 1 2.25 0l6.75 3.98h-.033z" /><path d="M12 8v4" /><path d="M12 16h.01" /></svg> </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    TOPLAM BOT TESPİTİ
                                </div>
                                <div class="text-muted">
                                    <?php echo $total_bot_detections; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>

        <div class="row row-cards g-3">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Cloaker Yönetim Ayarları</h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <form action="<?php echo ADMIN_URL; ?>manage_cloaker.php" method="POST">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cloaker_status" value="1" <?php echo (isset($current_settings['cloaker_status']) && $current_settings['cloaker_status'] == '1') ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Cloaker Durumu (Açık/Kapalı)</span>
                                </label>
                                <small class="form-hint">Bu ayar, Google botları ve gerçek kullanıcılar arasında içerik ayrımı yapmayı sağlar. <strong class="text-danger">Kapatıldığında tüm trafik ana sayfanıza yönlendirilir.</strong></small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mobil İçerik URL Yolu (Ana Script)</label>
                                <input type="text" name="cloaker_real_url" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_real_url'] ?? 'index.php'); ?>" placeholder="Örn: index.php">
                                <small class="form-hint">Mobil kullanıcılar için ana dizindeki bu dosya görüntülenecektir (sitenizin mobil uyumlu versiyonu).</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Masaüstü/Bot İçin WordPress (Placeholder)</label>
                                <input type="text" name="cloaker_compliant_desktop_url" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_compliant_desktop_url'] ?? 'wordpress_placeholder.html'); ?>" placeholder="Bu bir yer tutucudur.">
                                <small class="form-hint">Bu alanın değeri artık WordPress kurulumuna yönlendirdiğiniz için sadece bir yer tutucudur. Değerin anlamı değişmiştir, ancak boş bırakmayınız.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Mobil Bot İçerik URL Yolu (Kullanılmaz)</label>
                                <input type="text" name="cloaker_compliant_mobile_url" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_compliant_mobile_url'] ?? 'mobile_bot_placeholder.html'); ?>" placeholder="Bu bir yer tutucudur.">
                                <small class="form-hint">Bu alanın değeri artık kullanılmamaktadır. Mobil kullanıcılar direkt ana dizindeki "Mobil İçerik URL Yolu"nu görecektir.</small>
                            </div>
                             <div class="mb-3">
                                <label class="form-label">Fallback URL (Hata Durumunda)</label>
                                <input type="url" name="cloaker_fallback_url" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_fallback_url'] ?? BASE_URL . 'index.php'); ?>" placeholder="Örn: https://example.com/anasayfa">
                                <small class="form-hint">Cloaker bir sorun yaşarsa veya istenen sayfa bulunamazsa kullanıcıların yönlendirileceği tam URL.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Beyaz Liste IP Adresleri</label>
                                <input type="text" name="cloaker_whitelist_ips" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_whitelist_ips'] ?? ''); ?>" placeholder="Örn: 85.108.17.54, 192.168.1.0/24">
                                <small class="form-hint">Virgülle ayırarak birden fazla IP adresi veya CIDR aralığı girebilirsiniz. Bu IP'ler her zaman masaüstü (WordPress) içeriği görecektir.</small>
                            </div>
                            <hr>
                            <h4>Reklam Botları Yönlendirme Ayarları</h4>
                            <div class="mb-3">
                                <label class="form-label">WordPress İstemcisini Görecek Reklam Platformları</label>
                                <div> <div class="mb-2"> <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cloaker_wp_ad_bots[]" value="google_ads" <?php echo (isset($parsed_wp_ad_bots) && in_array('google_ads', $parsed_wp_ad_bots)) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">Google ADS Botları</span>
                                        </label>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cloaker_wp_ad_bots[]" value="meta" <?php echo (isset($parsed_wp_ad_bots) && in_array('meta', $parsed_wp_ad_bots)) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">Meta (Facebook/Instagram) Botları</span>
                                        </label>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cloaker_wp_ad_bots[]" value="tiktok" <?php echo (isset($parsed_wp_ad_bots) && in_array('tiktok', $parsed_wp_ad_bots)) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">TikTok Botları</span>
                                        </label>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cloaker_wp_ad_bots[]" value="twitter" <?php echo (isset($parsed_wp_ad_bots) && in_array('twitter', $parsed_wp_ad_bots)) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">Twitter Botları</span>
                                        </label>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cloaker_wp_ad_bots[]" value="other" <?php echo (isset($parsed_wp_ad_bots) && in_array('other', $parsed_wp_ad_bots)) ? 'checked' : ''; ?>>
                                            <span class="form-check-label">Diğer Reklam Botları</span>
                                        </label>
                                    </div>
                                </div>
                                <small class="form-hint">İşaretlenen reklam platformlarının botları, cloaker'dan etkilenmeden WordPress istemcisini görecektir.</small>
                            </div>
                            <hr>
                            <h4>Gelişmiş Cloaker Ayarları</h4>
                            <div class="mb-3">
                                <label class="form-label">Kara Liste IP Adresleri</label>
                                <input type="text" name="cloaker_blacklist_ips" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_blacklist_ips'] ?? ''); ?>" placeholder="Örn: 1.2.3.4, 5.6.7.8/24">
                                <small class="form-hint">Virgülle ayırarak birden fazla IP adresi veya CIDR aralığı girebilirsiniz. Bu IP'ler her zaman Fallback URL'ye yönlendirilecektir.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">User-Agent Beyaz Liste</label>
                                <input type="text" name="cloaker_ua_whitelist" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_ua_whitelist'] ?? ''); ?>" placeholder="Örn: Googlebot/2.1, MyCustomCrawler">
                                <small class="form-hint">Virgülle ayırarak User-Agent string'leri girin. Bu UA'lara sahip ziyaretçiler her zaman masaüstü (WordPress) içeriği görecektir.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">User-Agent Kara Liste</label>
                                <input type="text" name="cloaker_ua_blacklist" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_ua_blacklist'] ?? ''); ?>" placeholder="Örn: BadBot/1.0, SpamCrawler">
                                <small class="form-hint">Virgülle ayırarak User-Agent string'leri girin. Bu UA'lara sahip ziyaretçiler her zaman Fallback URL'ye yönlendirilecektir.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Referer Kara Liste (Tam URL, Kısmi Eşleşme veya REGEX)</label>
                                <input type="text" name="cloaker_referer_blacklist" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_referer_blacklist'] ?? ''); ?>" placeholder="Örn: spam.site.com, bad-forum.net/spam">
                                <small class="form-hint">Virgülle ayırarak domain veya URL parçaları girin. Bu referer'lardan gelen ziyaretçiler her zaman Fallback URL'ye yönlendirilecektir.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cloaker_enable_time_rule" value="1" <?php echo (isset($current_settings['cloaker_enable_time_rule']) && $current_settings['cloaker_enable_time_rule'] == '1') ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Zaman Bazlı Kuralı Etkinleştir</span>
                                </label>
                                <small class="form-hint">Belirli saatler arasında cloaker'ın davranışını değiştirmeyi sağlar.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kural Başlangıç Saati (24 Saat Formatı, Örn: 09:00)</label>
                                <input type="time" name="cloaker_rule_start_time" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_rule_start_time'] ?? '00:00'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kural Bitiş Saati (24 Saat Formatı, Örn: 17:00)</label>
                                <input type="time" name="cloaker_rule_end_time" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_rule_end_time'] ?? '23:59'); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Saat Kuralı Aktifken Gösterilecek İçerik</label>
                                <select name="cloaker_time_rule_action" class="form-select">
                                    <option value="wordpress" <?php echo (isset($current_settings['cloaker_time_rule_action']) && $current_settings['cloaker_time_rule_action'] == 'wordpress') ? 'selected' : ''; ?>>WordPress'i Göster</option>
                                    <option value="mobil" <?php echo (isset($current_settings['cloaker_time_rule_action']) && $current_settings['cloaker_time_rule_action'] == 'mobil') ? 'selected' : ''; ?>>Mobil Sayfayı Göster</option>
                                    <option value="fallback" <?php echo (isset($current_settings['cloaker_time_rule_action']) && $current_settings['cloaker_time_rule_action'] == 'fallback') ? 'selected' : ''; ?>>Fallback URL'ye Yönlendir</option>
                                </select>
                                <small class="form-hint">Belirtilen saatler arasında gelen ziyaretçiler için uygulanacak aksiyon.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cloaker_enable_honeypot" value="1" <?php echo (isset($current_settings['cloaker_enable_honeypot']) && $current_settings['cloaker_enable_honeypot'] == '1') ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Honeypot Korumasını Etkinleştir</span>
                                </label>
                                <small class="form-hint">Web sitesine gizli bir alan ekler. Bu alana veri giren botlar tespit edilerek engellenir.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cloaker_enable_honeypot_links" value="1" <?php echo (isset($current_settings['cloaker_enable_honeypot_links']) && $current_settings['cloaker_enable_honeypot_links'] == '1') ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Honeypot Gizli Linklerini Etkinleştir</span>
                                </label>
                                <small class="form-hint">Web sitesine görünmez linkler ekler. Bu linklere tıklayan botlar tespit edilerek engellenir.</small>
                            </div>
                            <hr>
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cloaker_enable_dynamic_fallback" value="1" <?php echo (isset($current_settings['cloaker_enable_dynamic_fallback']) && $current_settings['cloaker_enable_dynamic_fallback'] == '1') ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Dinamik Fallback İçeriği Etkinleştir</span>
                                </label>
                                <small class="form-hint">Etkinleştirilirse, Fallback URL yerine aşağıdaki HTML içeriği gösterilir. Boş bırakılırsa Fallback URL kullanılır.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Dinamik Fallback İçeriği (HTML Destekli)</label>
                                <textarea name="cloaker_dynamic_fallback_content" class="form-control" rows="6" placeholder="Buraya Fallback durumunda gösterilecek HTML içeriğini girin."><?php echo htmlspecialchars($current_settings['cloaker_dynamic_fallback_content'] ?? ''); ?></textarea>
                                <small class="form-hint">Bu içerik, botlar veya engellenen IP'ler için doğrudan sayfa içinde gösterilecektir. HTML kullanabilirsiniz.</small>
                            </div>
                            <hr>
                            
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cloaker_enable_logging" value="1" <?php echo (isset($current_settings['cloaker_enable_logging']) && $current_settings['cloaker_enable_logging'] == '1') ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Cloaker Loglamayı Etkinleştir</span>
                                </label>
                                <small class="form-hint">Tüm cloaker kararlarını ve ziyaretçi bilgilerini log dosyasına kaydeder (Performansı etkileyebilir!).</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Loglama Seviyesi</label>
                                <select name="cloaker_logging_level" class="form-select">
                                    <option value="basic" <?php echo (isset($current_settings['cloaker_logging_level']) && $current_settings['cloaker_logging_level'] == 'basic') ? 'selected' : ''; ?>>Temel (Sadece Karar)</option>
                                    <option value="detailed" <?php echo (isset($current_settings['cloaker_logging_level']) && $current_settings['cloaker_logging_level'] == 'detailed') ? 'selected' : ''; ?>>Detaylı (Tüm Başlıklar)</option>
                                </select>
                                <small class="form-hint">Detaylı loglama daha fazla sunucu kaynağı kullanır ve daha fazla disk alanı kaplar.</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="cloaker_enable_cookie_persistence" value="1" <?php echo (isset($current_settings['cloaker_enable_cookie_persistence']) && $current_settings['cloaker_enable_cookie_persistence'] == '1') ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Çerez Tabanlı Kalıcılığı Etkinleştir</span>
                                </label>
                                <small class="form-hint">Cloaker kararını bir çerezle hatırlar. Aynı ziyaretçi için tekrar eden kontrolleri atlar. (Tespit riskini artırabilir).</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Çerez Ömrü (Saat)</label>
                                <input type="number" name="cloaker_cookie_lifetime_hours" class="form-control" value="<?php echo htmlspecialchars($current_settings['cloaker_cookie_lifetime_hours'] ?? 24); ?>" min="1">
                                <small class="form-hint">Çerezin aktif kalacağı süre (saat cinsinden). Örneğin, 24 saat.</small>
                            </div>

                            <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
                        </form>
                        
                        
                    </div>
                
                </div>
            </div>
        </div>
        
        <div class="row row-cards mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Cloaker Logları</h3>
                        <div class="card-actions">
                            <form action="" method="POST" onsubmit="return confirm('Tüm logları silmek istediğinizden emin misiniz? Bu işlem geri alınamaz!');">
                                <input type="hidden" name="action" value="clear_cloaker_logs">
                                <button type="submit" class="btn btn-danger btn-sm">Logları Temizle</button>
                            </form>
                            <form action="" method="POST" onsubmit="return confirm('IP önbelleğini temizlemek istediğinizden emin misiniz? Bu işlem geri alınamaz ve bir süre API sorgularını artırabilir!');">
                                <input type="hidden" name="action" value="clear_ip_cache">
                                <button type="submit" class="btn btn-warning btn-sm">IP Önbelleğini Temizle</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                            <table class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                    <tr>
                                        <th>Zaman Damgası</th>
                                        <th>IP Adresi</th>
                                        <th>Ülke</th>
                                        <th>İSS</th>
                                        <th>İşlem</th>
                                        <th>Cihaz</th>
                                        <th>Hedef</th>
                                        <th>Kontrol</th>
                                        <th>İşlemler</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($log_entries_parsed)): ?>
                                        <?php foreach ($log_entries_parsed as $entry): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($entry['timestamp'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($entry['ip'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($entry['country'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($entry['isp'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($entry['action'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($entry['reason'] ?? ''); ?></td>
                                                <td><?php echo htmlspecialchars($entry['target'] ?? ''); ?></td>
                                                <td>
                                                    <?php
                                                    $details_text = '';
                                                    $is_ad_bot = (isset($entry['is_google_ad_bot']) && $entry['is_google_ad_bot'] === 'yes');

                                                    if ($is_ad_bot) {
                                                        $details_text .= '<span style="color: red; font-weight: bold;">Reklam Botu</span><br>';
                                                    } else {
                                                        $details_text .= '<span style="color: green; font-weight: bold;">Normal</span><br>';
                                                    }

                                                    if (isset($entry['ua']) && $entry['ua'] !== '') { $details_text .= 'UA: ' . htmlspecialchars($entry['ua']) . '<br>'; }
                                                    if (isset($entry['referer']) && $entry['referer'] !== '') { $details_text .= 'Referer: ' . htmlspecialchars($entry['referer']) . '<br>'; }
                                                    if (isset($entry['request_uri']) && $entry['request_uri'] !== '') { $details_text .= 'URI: ' . htmlspecialchars($entry['request_uri']) . '<br>'; }
                                                    if (isset($entry['details']) && $entry['details'] !== '') { $details_text .= 'Extra: ' . htmlspecialchars($entry['details']); }
                                                    echo $details_text;
                                                    ?>
                                                </td>
                                                <td>
                                                    <?php
                                                    // IP'nin geçerli ve kara listede olmadığından emin ol
                                                    $is_ip_valid_for_blacklist = !empty($entry['ip']) && $entry['ip'] !== 'N/A' && filter_var($entry['ip'], FILTER_VALIDATE_IP);
                                                    $is_already_blacklisted = false;
                                                    // current_settings'den gelen IP listesi string olabilir, diziye çevirip kontrol et
                                                    $current_blacklist_ips_from_settings = array_map('trim', explode(',', $current_settings['cloaker_blacklist_ips'] ?? ''));
                                                    
                                                    foreach($current_blacklist_ips_from_settings as $bl_ip_setting) {
                                                        if (ip_in_cidr($entry['ip'], $bl_ip_setting)) {
                                                            $is_already_blacklisted = true;
                                                            break;
                                                        }
                                                    }
                                                    
                                                    if ($is_ip_valid_for_blacklist && !$is_already_blacklisted): ?>
                                                            <form action="" method="POST" onsubmit="return confirm('<?php echo htmlspecialchars($entry['ip']); ?> IP adresini kara listeye almak istediğinizden emin misiniz?');">
                                                                <input type="hidden" name="action" value="blacklist_ip">
                                                                <input type="hidden" name="ip_to_blacklist" value="<?php echo htmlspecialchars($entry['ip']); ?>">
                                                                <button type="submit" class="btn btn-danger btn-sm">Yasakla</button>
                                                            </form>
                                                    <?php elseif ($is_ip_valid_for_blacklist && $is_already_blacklisted): ?>
                                                            <span class="badge bg-danger">Yasaklı</span>
                                                    <?php else: ?>
                                                            <span class="text-muted">N/A</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center"><?php echo (isset($log_entries_parsed[0]['reason']) && $log_entries_parsed[0]['reason'] !== '') ? htmlspecialchars($log_entries_parsed[0]['reason']) : 'Henüz log kaydı bulunmamaktadır veya log dosyası boş.'; ?></td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>