<?php
// Hata raporlamayı aç (Geliştirme aşamasında faydalı, canlıda kapatılmalı)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- GEREKLİ SABİTLERİN TANIMLANMASI ---

// Güvenli kök yolu oluşturma
$doc_root_fallback = rtrim(str_replace('\\', '/', dirname(__FILE__)), '/');
if (empty($_SERVER['DOCUMENT_ROOT']) || strpos($doc_root_fallback, $_SERVER['DOCUMENT_ROOT']) === false) {
    define('CLOAKER_ROOT_PATH', $doc_root_fallback . '/');
} else {
    define('CLOAKER_ROOT_PATH', rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/');
}

// Masaüstü ve botlar için gösterilecek sitenin (WordPress) fiziksel yolu
define('DESKTOP_SITE_PHYSICAL_PATH', CLOAKER_ROOT_PATH . 'website/'); // 'website/' kısmını WordPress klasörünüzün adıyla değiştirin

// URL Sabitleri
define('BASE_URL', 'https://www.bonuslistesi.xyz/'); // KENDİ PROJENİZİN ANA URL'SİNİ BURAYA YAZINIZ!
define('ADMIN_URL', BASE_URL . 'admin/');

// Cloaker'ın kendi veritabanı sabitleri (WordPress ile çakışmaması için 'CLOAKER_' ön eki kullanıldı)
define('CLOAKER_DB_HOST', 'localhost');
define('CLOAKER_DB_NAME', 'mszffsbpwz_circus1');
define('CLOAKER_DB_USER', 'mszffsbpwz_circus2');
define('CLOAKER_DB_PASS', 'CircusMedya!!16');


// --- YARDIMCI FONKSİYONLARIN TANIMLANMASI ---

if (!function_exists('ip_in_cidr')) {
    function ip_in_cidr($ip, $cidr) {
        if (strpos($cidr, '/') === false) { return $ip === $cidr; }
        list($subnet, $mask) = explode('/', $cidr);
        if ((long2ip(ip2long($ip) & ~((1 << (32 - $mask)) - 1))) == $subnet) { return true; }
        return false;
    }
}
if (!function_exists('log_cloaker_action')) {
    function log_cloaker_action($action, $reason, $target_page, $level = 'basic', $full_request_info = []) {
        global $cloaker_settings;
        if (!isset($cloaker_settings['cloaker_enable_logging']) || $cloaker_settings['cloaker_enable_logging'] !== '1' || strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false) { return; }
        $log_file = CLOAKER_ROOT_PATH . 'cloaker_activity.log';
        $timestamp = date('Y-m-d H:i:s');
        $remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        $referer = $_SERVER['HTTP_REFERER'] ?? 'N/A';
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        $country = 'Unknown'; $isp = 'Unknown';
        if ($remote_ip !== 'N/A' && filter_var($remote_ip, FILTER_VALIDATE_IP)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$remote_ip}?fields=country,isp");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            $geo_response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($http_code === 200 && $geo_response) {
                $geo_data = json_decode($geo_response, true);
                if ($geo_data && isset($geo_data['country'])) {
                    $country = $geo_data['country'];
                    $isp = $geo_data['isp'] ?? 'N/A';
                }
            }
        }
        $log_entry = "[{$timestamp}] ACTION: {$action} | REASON: {$reason} | TARGET: {$target_page} | IP: {$remote_ip} | COUNTRY: {$country} | ISP: {$isp}";
        if ($level === 'detailed' && ($cloaker_settings['cloaker_logging_level'] ?? 'basic') === 'detailed') {
            $log_entry .= " | UA: {$user_agent} | REFERER: {$referer} | REQUEST_URI: {$request_uri}";
            if (!empty($full_request_info)) { $log_entry .= " | DETAILS: " . json_encode($full_request_info); }
        }
        $log_entry .= "\n";
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}
if (!function_exists('handle_fallback')) {
    function handle_fallback($reason, $full_request_info = []) {
        global $cloaker_settings;
        $remote_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'N/A';
        $referer = $_SERVER['HTTP_REFERER'] ?? 'N/A';
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'N/A';
        $fallback_url = $cloaker_settings['cloaker_fallback_url'] ?? BASE_URL;
        $dynamic_content = $cloaker_settings['cloaker_dynamic_fallback_content'] ?? '';
        $enable_dynamic_fallback = ($cloaker_settings['cloaker_enable_dynamic_fallback'] ?? '0') === '1';
        if ($enable_dynamic_fallback && !empty($dynamic_content)) {
            log_cloaker_action('Görüntüleme', $reason, 'Dinamik Fallback Sayfası', 'detailed', array_merge(['ip' => $remote_ip, 'ua' => $user_agent, 'referer' => $referer, 'req_uri' => $request_uri], $full_request_info));
            header("HTTP/1.1 200 OK"); header("Status: 200 OK");
            echo $dynamic_content;
            exit();
        } else {
            log_cloaker_action('Yönlendirme', $reason, $fallback_url, 'detailed', array_merge(['ip' => $remote_ip, 'ua' => $user_agent, 'referer' => $referer, 'req_uri' => $request_uri], $full_request_info));
            header("Location: " . $fallback_url);
            exit();
        }
    }
}
if (!function_exists('redirect')) { function redirect($url) { header("Location: " . $url); exit(); } }
if (!function_exists('check_user_role')) {
    function check_user_role($required_role = 'user') {
        if (!isset($_SESSION['user_id'])) { redirect(BASE_URL . 'login.php'); }
        if ($required_role == 'admin' && ($_SESSION['user_role'] ?? '') !== 'admin') { redirect(BASE_URL . 'index.php'); }
    }
}
if (!function_exists('check_admin_auth')) {
    function check_admin_auth() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_role']) || ($_SESSION['user_role'] ?? '') !== 'admin') { redirect(ADMIN_URL . 'login.php'); }
    }
}
if (!function_exists('update_cloaker_bot_lists')) {
    function update_cloaker_bot_lists($pdo_connection) {
        // ... (Bu fonksiyon orijinaldeki gibi kalabilir, değişiklik yok) ...
    }
}


// --- VERİTABANI BAĞLANTISI VE CLOAKER AYARLARI ---
global $pdo;
global $cloaker_settings;
try {
    $dsn = "mysql:host=" . CLOAKER_DB_HOST . ";dbname=" . CLOAKER_DB_NAME . ";charset=utf8mb4";
    $options = [ PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false, ];
    $pdo = new PDO($dsn, CLOAKER_DB_USER, CLOAKER_DB_PASS, $options);
    $stmt_cloaker_settings = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'cloaker_%'");
    $cloaker_settings = [];
    while ($row = $stmt_cloaker_settings->fetch()) {
        $cloaker_settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (\PDOException $e) {
    error_log("Cloaker/Uygulama Veritabanı Hatası: " . $e->getMessage());
    $cloaker_settings['cloaker_status'] = '0';
}


// --- OTURUM YÖNETİMİ ---
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}


// --- CLOAKER MANTIK BLOĞU (Sadece admin paneli isteği değilse çalışır) ---
$is_admin_panel_request = (strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== false);

if (!$is_admin_panel_request) {
    if (!isset($cloaker_settings['cloaker_status']) || $cloaker_settings['cloaker_status'] !== '1') {
        // Cloaker kapalı, normal akış devam edecek.
    } else {
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $remote_ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '';
        $is_mobile_device = false; $is_google_bot_or_other_search_engine_bot = false; $is_whitelisted_ip = false; $is_blacklisted_ip = false; $is_blacklisted_ua = false; $is_whitelisted_ua = false; $is_blacklisted_referer = false;
        $user_country = 'Unknown'; $user_language = 'Unknown'; $detected_device_type = 'other'; $detected_ad_platform = ''; $is_ad_platform_bot_allowed_to_wp = false; $is_honeypot_form_bot = false; $is_honeypot_link_bot = false;

        $cloaker_cookie_name = 'cloaker_decision';
        $cloaker_cookie_lifetime_seconds = (int)($cloaker_settings['cloaker_cookie_lifetime_hours'] ?? 24) * 3600;
        if (($cloaker_settings['cloaker_enable_cookie_persistence'] ?? '0') === '1' && isset($_COOKIE[$cloaker_cookie_name])) {
            $cookie_value = $_COOKIE[$cloaker_cookie_name];
            @list($decision_type, $timestamp) = explode('_', $cookie_value);
            if ((time() - (int)$timestamp) < $cloaker_cookie_lifetime_seconds) {
                if ($decision_type === 'wordpress') {
                    log_cloaker_action('Görüntüleme', 'Çerez Tabanlı Karar', 'Cloaker Sayfası (WP)', 'detailed', ['ip' => $remote_ip, 'ua' => $user_agent, 'cookie_decision' => 'wordpress']);
                    $_SERVER['SCRIPT_FILENAME'] = DESKTOP_SITE_PHYSICAL_PATH . 'index.php';
                    $_SERVER['DOCUMENT_ROOT'] = DESKTOP_SITE_PHYSICAL_PATH;
                    chdir(DESKTOP_SITE_PHYSICAL_PATH);
                    if (ob_get_level() > 0) { ob_end_clean(); }
                    require DESKTOP_SITE_PHYSICAL_PATH . 'index.php';
                    exit();
                } elseif ($decision_type === 'mobile') {
                    log_cloaker_action('Görüntüleme', 'Çerez Tabanlı Karar', 'Reklam Sayfası (Mobil)', 'detailed', ['ip' => $remote_ip, 'ua' => $user_agent, 'cookie_decision' => 'mobile']);
                }
            } else {
                setcookie($cloaker_cookie_name, '', time() - 3600, '/');
            }
        }
        if (($_SERVER['REQUEST_METHOD'] === 'POST') && ($cloaker_settings['cloaker_enable_honeypot'] ?? '0') === '1') {
            if (isset($_POST['my_secret_field']) && !empty($_POST['my_secret_field'])) {
                handle_fallback('Honeypot Form Koruması', ['honeypot_field' => $_POST['my_secret_field'] ?? 'empty']);
            }
        }
        if (($_SERVER['REQUEST_METHOD'] === 'GET') && ($cloaker_settings['cloaker_enable_honeypot_links'] ?? '0') === '1') {
            if (strpos($request_uri, '/honeypot-trap') !== false) {
                handle_fallback('Honeypot Link Koruması');
            }
        }
        $referer_blacklist_array = !empty($cloaker_settings['cloaker_referer_blacklist']) ? array_map('trim', explode(',', $cloaker_settings['cloaker_referer_blacklist'])) : [];
        foreach ($referer_blacklist_array as $bl_referer) {
            if (strpos($bl_referer, '/') === 0 && substr($bl_referer, -1) === '/') { if (@preg_match($bl_referer, $referer)) { $is_blacklisted_referer = true; break; } } 
            else { if (stripos($referer, $bl_referer) !== false && !empty($referer)) { $is_blacklisted_referer = true; break; } }
        }
        if ($is_blacklisted_referer) { handle_fallback('Kara Listeye Alınan Referer', ['referer' => $referer]); }
        $blacklist_ips_array = !empty($cloaker_settings['cloaker_blacklist_ips']) ? array_map('trim', explode(',', $cloaker_settings['cloaker_blacklist_ips'])) : [];
        foreach ($blacklist_ips_array as $bl_ip) { if (ip_in_cidr($remote_ip, $bl_ip)) { $is_blacklisted_ip = true; break; } }
        if ($is_blacklisted_ip) { handle_fallback('Kara Listeye Alınan IP', ['ip' => $remote_ip]); }
        $ua_blacklist_array = !empty($cloaker_settings['cloaker_ua_blacklist']) ? array_map('trim', explode(',', $cloaker_settings['cloaker_ua_blacklist'])) : [];
        foreach ($ua_blacklist_array as $bl_ua) { if (stripos($user_agent, $bl_ua) !== false) { $is_blacklisted_ua = true; break; } }
        if ($is_blacklisted_ua) { handle_fallback('User-Agent Kara Listede', ['ua' => $user_agent]); }
        $ua_whitelist_array = !empty($cloaker_settings['cloaker_ua_whitelist']) ? array_map('trim', explode(',', $cloaker_settings['cloaker_ua_whitelist'])) : [];
        foreach ($ua_whitelist_array as $wl_ua) { if (stripos($user_agent, $wl_ua) !== false) { $is_whitelisted_ua = true; break; } }
        $whitelist_ips_array = !empty($cloaker_settings['cloaker_whitelist_ips']) ? array_map('trim', explode(',', $cloaker_settings['cloaker_whitelist_ips'])) : [];
        foreach ($whitelist_ips_array as $wl_ip) { if (ip_in_cidr($remote_ip, $wl_ip)) { $is_whitelisted_ip = true; break; } }

        if ($remote_ip !== 'N/A' && filter_var($remote_ip, FILTER_VALIDATE_IP)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, "http://ip-api.com/json/{$remote_ip}?fields=countryCode");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 1);
            $geo_response_country = curl_exec($ch);
            curl_close($ch);
            if ($geo_response_country) {
                $geo_data_country = json_decode($geo_response_country, true);
                if ($geo_data_country && isset($geo_data_country['countryCode'])) { $user_country = $geo_data_country['countryCode']; }
            }
        }
        $accept_language = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if (!empty($accept_language)) { $langs = explode(',', $accept_language); $user_language = strtolower(trim(explode(';', $langs[0])[0])); }
        if (strpos($referer, 'googleads.g.doubleclick.net') !== false || strpos($referer, 'www.google.com/url?') !== false) { $detected_ad_platform = 'google_ads'; } 
        elseif (strpos($referer, 'facebook.com') !== false || strpos($referer, 'l.facebook.com') !== false || strpos($referer, 'lm.facebook.com') !== false || strpos($referer, 'instagram.com') !== false) { $detected_ad_platform = 'meta'; } 
        elseif (strpos($referer, 'tiktok.com') !== false) { $detected_ad_platform = 'tiktok'; } 
        elseif (strpos($referer, 'twitter.com') !== false || strpos($referer, 't.co') !== false) { $detected_ad_platform = 'twitter'; } 
        elseif (!empty($referer) && !strpos($referer, $_SERVER['HTTP_HOST']) !== false) { $detected_ad_platform = 'other_ad'; }
        if (stripos($user_agent, 'Android') !== false) { $detected_device_type = 'android'; $is_mobile_device = true; } 
        elseif (stripos($user_agent, 'iPhone') !== false || stripos($user_agent, 'iPad') !== false || stripos($user_agent, 'iPod') !== false) { $detected_device_type = 'ios'; $is_mobile_device = true; } 
        elseif (stripos($user_agent, 'Windows Phone') !== false || stripos($user_agent, 'IEMobile') !== false) { $detected_device_type = 'windows_mobile'; $is_mobile_device = true; } 
        elseif (stripos($user_agent, 'Windows') !== false) { $detected_device_type = 'windows'; } 
        elseif (stripos($user_agent, 'Macintosh') !== false || stripos($user_agent, 'Mac OS X') !== false) { $detected_device_type = 'osx'; } 
        elseif (stripos($user_agent, 'Linux') !== false && stripos($user_agent, 'Android') === false) { $detected_device_type = 'linux'; }
        $bot_user_agents = [ 'Googlebot', 'AdsBot-Google', 'Mediapartners-Google', 'Googlebot-Image', 'Googlebot-News', 'Googlebot-Video', 'APIs-Google', 'Google-Adwords-InstantPreview', 'Google-Structured-Data-Testing-Tool', 'Bingbot', 'Slurp', 'DuckDuckBot', 'Baiduspider', 'YandexBot', 'facebookexternalhit', 'WhatsApp', 'TelegramBot', 'Twitterbot', 'Facebot', 'Embedly', 'LinkedInBot', 'Pinterestbot', 'Applebot', 'Bytespider', 'SemrushBot', 'AhrefsBot', 'DotBot', 'MJ12bot', 'SiteAuditBot', 'Curl', 'Wget', 'Python-urllib', 'Java/', 'Go-http-client', 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)', 'Mozilla/5.0 (Linux; Android 6.0.1; Nexus 5X Build/MTC19V) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2272.96 Mobile Safari/537.36 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)' ];
        foreach ($bot_user_agents as $bot_ua) {
            if (stripos($user_agent, $bot_ua) !== false) {
                $hostname = gethostbyaddr($remote_ip);
                if ($hostname && (strpos($hostname, '.googlebot.com') !== false || strpos($hostname, '.google.com') !== false || strpos($hostname, '.bing.com') !== false || strpos($hostname, '.yandex.com') !== false || strpos($hostname, '.yandex.ru') !== false || strpos($hostname, '.baidu.com') !== false || strpos($hostname, '.facebook.com') !== false || strpos($hostname, '.whatsapp.net') !== false || strpos($hostname, '.t.me') !== false || strpos($hostname, '.twitter.com') !== false || strpos($hostname, '.linkedin.com') !== false || strpos($hostname, '.pinterest.com') !== false || strpos($hostname, '.apple.com') !== false )) {
                    $is_google_bot_or_other_search_engine_bot = true;
                    break;
                }
            }
        }
        $wp_ad_bots_settings = !empty($cloaker_settings['cloaker_wp_ad_bots']) ? array_map('trim', explode(',', $cloaker_settings['cloaker_wp_ad_bots'])) : [];
        if ($is_google_bot_or_other_search_engine_bot && !empty($detected_ad_platform) && (in_array($detected_ad_platform, $wp_ad_bots_settings) || in_array('other', $wp_ad_bots_settings))) { $is_ad_platform_bot_allowed_to_wp = true; }
        
        $should_display_wordpress = false;
        $reason_for_action = 'Mobil Kullanıcı (Normal Akış)';
        if ($is_whitelisted_ua) { $should_display_wordpress = true; $reason_for_action = 'User-Agent Beyaz Listede'; } 
        elseif ($is_ad_platform_bot_allowed_to_wp) { $should_display_wordpress = true; $reason_for_action = 'Reklam Botu İzni: ' . $detected_ad_platform; } 
        elseif ($is_whitelisted_ip) { $should_display_wordpress = true; $reason_for_action = 'Beyaz Listeye Alınan IP'; } 
        elseif (!$is_mobile_device) { $should_display_wordpress = true; $reason_for_action = 'Bilgisayar Cihazı'; } 
        elseif ($is_google_bot_or_other_search_engine_bot) { $should_display_wordpress = true; $reason_for_action = 'Bot Tespiti'; } 
        elseif (!empty($detected_ad_platform) && (in_array($detected_ad_platform, array_map('trim', explode(',', $cloaker_settings['cloaker_ad_platforms'] ?? ''))) || in_array('other', array_map('trim', explode(',', $cloaker_settings['cloaker_ad_platforms'] ?? ''))))) { $should_display_wordpress = true; $reason_for_action = 'Reklam Platformu Tıklaması (' . $detected_ad_platform . ')'; }
        
        $filters_reject_wordpress = false;
        $reject_reason_details = [];
        if (!$is_whitelisted_ua && !$is_whitelisted_ip) {
            $allowed_countries_array = !empty($cloaker_settings['cloaker_allowed_countries']) ? array_map('trim', explode(',', strtoupper($cloaker_settings['cloaker_allowed_countries']))) : [];
            if (!empty($allowed_countries_array) && !in_array($user_country, $allowed_countries_array)) { $filters_reject_wordpress = true; $reject_reason_details[] = 'Ülke Filtresi Reddedildi: ' . $user_country; }
            $allowed_languages_array = !empty($cloaker_settings['cloaker_allowed_languages']) ? array_map('trim', explode(',', strtolower($cloaker_settings['cloaker_allowed_languages']))) : [];
            if (!empty($allowed_languages_array) && !in_array($user_language, $allowed_languages_array)) { $filters_reject_wordpress = true; $reject_reason_details[] = 'Dil Filtresi Reddedildi: ' . $user_language; }
            $allowed_devices_array = !empty($cloaker_settings['cloaker_allowed_devices']) ? array_map('trim', explode(',', $cloaker_settings['cloaker_allowed_devices'])) : [];
            if (!empty($allowed_devices_array) && !in_array($detected_device_type, $allowed_devices_array) && !in_array('other', $allowed_devices_array) && !in_array('windows_mobile', $allowed_devices_array)) { $filters_reject_wordpress = true; $reject_reason_details[] = 'Cihaz Filtresi Reddedildi: ' . $detected_device_type; }
        }
        if ($filters_reject_wordpress) { $should_display_wordpress = false; $reason_for_action = ($reason_for_action) . ' / Filtre Reddi: ' . implode(', ', $reject_reason_details); }
        
        if (($cloaker_settings['cloaker_enable_time_rule'] ?? '0') === '1') {
            date_default_timezone_set('Europe/Istanbul');
            $start_time = strtotime($cloaker_settings['cloaker_rule_start_time'] ?? '00:00');
            $end_time = strtotime($cloaker_settings['cloaker_rule_end_time'] ?? '23:59');
            $current_time = strtotime(date('H:i'));
            $is_time_rule_active_now = false;
            if ($start_time <= $end_time) { if ($current_time >= $start_time && $current_time <= $end_time) { $is_time_rule_active_now = true; } } 
            else { if ($current_time >= $start_time || $current_time <= $end_time) { $is_time_rule_active_now = true; } }
            if ($is_time_rule_active_now) {
                $time_rule_action = $cloaker_settings['cloaker_time_rule_action'] ?? 'wordpress';
                log_cloaker_action('Zaman Kuralı Aktif', 'Zaman Kuralı: ' . $time_rule_action, ($time_rule_action === 'wordpress' ? 'Cloaker Sayfası (WP)' : ($time_rule_action === 'mobil' ? 'Reklam Sayfası (Mobil)' : 'Fallback URL')), 'detailed', ['time_rule' => $time_rule_action]);
                if ($time_rule_action === 'wordpress') { $should_display_wordpress = true; $reason_for_action = 'Zaman Kuralı: WordPress Göster'; } 
                elseif ($time_rule_action === 'mobil') { $should_display_wordpress = false; $reason_for_action = 'Zaman Kuralı: Mobil Göster'; } 
                elseif ($time_rule_action === 'fallback') { handle_fallback('Zaman Kuralı: Fallback Yönlendirme'); }
            }
        }
        
        if (($cloaker_settings['cloaker_enable_cookie_persistence'] ?? '0') === '1') {
            $cookie_decision = $should_display_wordpress ? 'wordpress' : 'mobile';
            setcookie($cloaker_cookie_name, $cookie_decision . '_' . time(), time() + $cloaker_cookie_lifetime_seconds, '/', '', true, true);
            log_cloaker_action('Çerez Kaydedildi', 'Karar: ' . $cookie_decision, 'N/A', 'detailed', ['decision' => $cookie_decision]);
        }

        if ($should_display_wordpress) {
            log_cloaker_action('Görüntüleme', $reason_for_action, 'Cloaker Sayfası (WP)', 'detailed', ['ip' => $remote_ip, 'ua' => $user_agent, 'is_mobile' => ($is_mobile_device ? 'yes' : 'no'), 'is_bot' => ($is_google_bot_or_other_search_engine_bot ? 'yes' : 'no'), 'is_whitelisted' => ($is_whitelisted_ip ? 'yes' : 'no'), 'referer' => $referer, 'req_uri' => $request_uri, 'country' => $user_country, 'lang' => $user_language, 'device' => $detected_device_type, 'ad_platform' => $detected_ad_platform, 'is_google_ad_bot' => ($is_google_bot_or_other_search_engine_bot && $detected_ad_platform === 'google_ads' ? 'yes' : 'no')]);
            $_SERVER['SCRIPT_FILENAME'] = DESKTOP_SITE_PHYSICAL_PATH . 'index.php';
            $_SERVER['DOCUMENT_ROOT'] = DESKTOP_SITE_PHYSICAL_PATH;
            chdir(DESKTOP_SITE_PHYSICAL_PATH);
            if (ob_get_level() > 0) { ob_end_clean(); }
            require DESKTOP_SITE_PHYSICAL_PATH . 'index.php';
            exit();
        } else {
            log_cloaker_action('Görüntüleme', $reason_for_action, 'Reklam Sayfası (Mobil)', 'detailed', ['ip' => $remote_ip, 'ua' => $user_agent, 'is_mobile' => 'yes', 'referer' => $referer, 'req_uri' => $request_uri, 'country' => $user_country, 'lang' => $user_language, 'device' => $detected_device_type, 'ad_platform' => $detected_ad_platform, 'is_google_ad_bot' => ($is_google_bot_or_other_search_engine_bot && $detected_ad_platform === 'google_ads' ? 'yes' : 'no')]);
        }
    }
}
// --- CLOAKER MANTIĞI SONU ---


// --- ZİYARETÇİ TAKİP SİSTEMİ ---
if (isset($_SERVER['HTTP_USER_AGENT']) && $_SERVER['REQUEST_METHOD'] === 'GET' && !$is_admin_panel_request) {
    $user_ip = $_SERVER['REMOTE_ADDR'];
    $today_date = date('Y-m-d');
    try {
        if (isset($pdo)) {
            $stmt_check_visit = $pdo->prepare("SELECT id FROM unique_visits WHERE ip_address = ? AND visit_date = ?");
            $stmt_check_visit->execute([$user_ip, $today_date]);
            if (!$stmt_check_visit->fetch()) {
                $stmt_insert_visit = $pdo->prepare("INSERT INTO unique_visits (ip_address, visit_date) VALUES (?, ?)");
                $stmt_insert_visit->execute([$user_ip, $today_date]);
            } else {
                $stmt_update_visit = $pdo->prepare("UPDATE unique_visits SET last_visit_time = CURRENT_TIMESTAMP WHERE ip_address = ? AND visit_date = ?");
                $stmt_update_visit->execute([$user_ip, $today_date]);
            }
        } else {
            error_log("Ziyaretçi takip hatası: PDO objesi tanımlı değil.");
        }
    } catch (PDOException $e) {
        error_log("Ziyaretçi takip hatası: " . $e->getMessage());
    }
}
// --- ZİYARETÇİ TAKİP SİSTEMİ SONU ---

?>