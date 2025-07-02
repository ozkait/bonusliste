<?php
// cloaker_bootstrap.php

// Eğer cloaker admin panelinden kapalıysa veya ayarlar yüklenemediyse hiçbir şey yapma
if (!isset($cloaker_settings['cloaker_status']) || $cloaker_settings['cloaker_status'] !== '1') {
    return;
}

// Cloaker fonksiyonlarını dahil et
require_once ROOT_PATH . 'includes/cloaker_functions.php';

// 1. Ziyaretçi bilgilerini topla
$details = get_request_details();

// 2. Coğrafi bilgileri al (daha sonra kullanılacak)
$geo_info = get_geoip_info($details['ip']);
$details['country'] = $geo_info['country'];
$details['isp'] = $geo_info['isp'];

// 3. Cihaz bilgilerini al
$device_info = detect_device_type($details['user_agent']);
$details['device_type'] = $device_info['device_type'];
$details['is_mobile'] = $device_info['is_mobile'];

// 4. Bot olup olmadığını kontrol et
$details['is_known_bot'] = is_known_bot($details['user_agent'], $details['ip']);

// --- KARAR VERME MANTIĞI ---
$decision = 'mobile'; // Varsayılan karar: mobil sayfayı göster
$reason = 'Mobil Kullanıcı (Normal Akış)';

// ÖNCELİK 1: ZAMAN KURALI (Her şeyi ezer)
$time_rule_decision = check_time_rule($cloaker_settings);
if ($time_rule_decision !== null) {
    $decision = $time_rule_decision;
    $reason = 'Zaman Kuralı: ' . ucfirst($decision);
} else {
    // ÖNCELİK 2: KARA LİSTELER (Anında engelleme)
    $blacklist_check = check_blacklists($details, $cloaker_settings);
    if ($blacklist_check !== null) {
        $decision = 'fallback';
        $reason = $blacklist_check['reason'];
    } else {
        // ÖNCELİK 3: WORDPRESS GÖSTERME KURALLARI
        $wp_reason = get_wordpress_display_reason($details, $cloaker_settings);
        if ($wp_reason !== null) {
            // ÖNCELİK 4: GELİŞMİŞ FİLTRELER (WP kararını geçersiz kılabilir)
            $filter_result = apply_advanced_filters($wp_reason, $details, $cloaker_settings);
            if ($filter_result['should_display']) {
                $decision = 'wordpress';
                $reason = $filter_result['reason'];
            } else {
                $decision = 'mobile'; // Filtreye takıldı, mobile düşür
                $reason = $filter_result['reason'];
            }
        }
    }
}

// --- AKSİYON ALMA ---
log_cloaker_action(
    $decision === 'fallback' ? 'Yönlendirme' : 'Görüntüleme', 
    $reason, 
    $decision, 
    'detailed', 
    $details
);

if ($decision === 'wordpress') {
    show_wordpress_page(DESKTOP_SITE_PHYSICAL_PATH);
} elseif ($decision === 'fallback') {
    handle_fallback($reason, $details); // Bu fonksiyon zaten includes/functions.php içinde olmalı
}
// Eğer karar 'mobile' ise, hiçbir şey yapmıyoruz ve script normal akışına devam ediyor.
?>