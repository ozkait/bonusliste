<?php
require_once __DIR__ . '/includes/admin_header.php';

// Sayfa başlığını ayarla
$page_title = 'Dashboard';

// --- Tarih Tanımlamaları ---
$today_date = date('Y-m-d');
$today_start = date('Y-m-d 00:00:00');
$today_end = date('Y-m-d 23:59:59');
$this_month_start = date('Y-m-01 00:00:00');
$last_day_of_last_month = date('Y-m-t 23:59:59', strtotime('last month'));

// Son 7, 30, 60 gün periyotları için başlangıç ve bitiş tarihleri
$last_7_days_start = date('Y-m-d 00:00:00', strtotime('-7 days'));
$previous_7_days_start = date('Y-m-d 00:00:00', strtotime('-14 days'));
$previous_7_days_end = date('Y-m-d 23:59:59', strtotime('-8 days'));
$last_30_days_start = date('Y-m-d 00:00:00', strtotime('-30 days'));
$previous_30_days_start = date('Y-m-d 00:00:00', strtotime('-60 days'));
$previous_30_days_end = date('Y-m-d 23:59:59', strtotime('-31 days'));


// --- İstatistikleri Çekme ve Hesaplama ---

// Genel Toplamlar (All-Time) - Kartların ana değerleri için
// 1. Toplam Üyeler (TOTAL USERS) - Tüm zamanlar
$stmt_total_users = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user'");
$stmt_total_users->execute();
$total_users = $stmt_total_users->fetchColumn();

// 2. Toplam Bonus Tıklaması (TOTAL BONUS CLICKS) - Tüm zamanlar
$stmt_total_bonus_clicks_all_time = $pdo->prepare("SELECT COUNT(*) FROM user_bonuses");
$stmt_total_bonus_clicks_all_time->execute();
$total_bonus_clicks = $stmt_total_bonus_clicks_all_time->fetchColumn();

// 3. Toplam Tekil Ziyaretçi (TOTAL UNIQUE VISITS) - Tüm zamanlar
$stmt_total_visits_all_time = $pdo->prepare("SELECT COUNT(DISTINCT ip_address, visit_date) FROM unique_visits");
$stmt_total_visits_all_time->execute();
$total_visits = $stmt_total_visits_all_time->fetchColumn();


// Periyodik İstatistikler ve Değişim Oranları

// Toplam Üyeler Kartı için değişim (Last Month)
$stmt_users_at_last_month_end = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND created_at <= ?");
$stmt_users_at_last_month_end->execute([$last_day_of_last_month]);
$total_users_at_last_month_end = $stmt_users_at_last_month_end->fetchColumn();

$increase_from_last_month_users = $total_users - $total_users_at_last_month_end;
$percentage_change_total_users = ($total_users_at_last_month_end > 0) ? ($increase_from_last_month_users / $total_users_at_last_month_end) * 100 : 0;
$change_color_class_total_users = ($increase_from_last_month_users > 0) ? 'text-success' : (($increase_from_last_month_users < 0) ? 'text-danger' : '');


// Aktif Kullanıcılar (ACTIVE USERS) - Son 30 gün
$stmt_active_users_30_days = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_bonuses WHERE clicked_at >= ? AND clicked_at <= ?");
$stmt_active_users_30_days->execute([$last_30_days_start, $today_end]);
$active_users_30_days = $stmt_active_users_30_days->fetchColumn();

// Yüzde için önceki 30 günlük periyot aktif kullanıcıları
$stmt_active_users_previous_30_days = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_bonuses WHERE clicked_at >= ? AND clicked_at <= ?");
$stmt_active_users_previous_30_days->execute([$previous_30_days_start, $previous_30_days_end]);
$active_users_previous_30_days_count = $stmt_active_users_previous_30_days->fetchColumn();

$active_users_change = $active_users_30_days - $active_users_previous_30_days_count;
$percentage_change_active_users = ($active_users_previous_30_days_count > 0) ? ($active_users_change / $active_users_previous_30_days_count) * 100 : 0;
$change_color_class_active_users = ($active_users_change > 0) ? 'text-success' : (($active_users_change < 0) ? 'text-danger' : '');

// Doughnut chart için oran (Aktif kullanıcı sayısı / Toplam kullanıcı sayısı)
$active_user_ratio_percentage = ($total_users > 0) ? ($active_users_30_days / $total_users) * 100 : 0;


// Bugünün Yeni Üyeleri (TODAY'S NEW USERS) - Bugün ve Dün karşılaştırması
$stmt_today_new_users = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND DATE(created_at) = ?");
$stmt_today_new_users->execute([$today_date]);
$today_new_users = $stmt_today_new_users->fetchColumn();

$yesterday_new_users_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND DATE(created_at) = DATE_SUB(?, INTERVAL 1 DAY)");
$yesterday_new_users_stmt->execute([$today_date]);
$yesterday_new_users = $yesterday_new_users_stmt->fetchColumn();

$today_new_users_change = $today_new_users - $yesterday_new_users;
$percentage_change_today_users = ($yesterday_new_users > 0) ? ($today_new_users_change / $yesterday_new_users) * 100 : 0;
$change_color_class_today_users = ($today_new_users_change > 0) ? 'text-success' : (($today_new_users_change < 0) ? 'text-danger' : '');


// Büyüme Oranı (GROWTH RATE) - Genel toplam kullanıcı bazlı, geçen aya göre büyüme
$overall_growth_percentage = ($total_users_at_last_month_end > 0) ? (($total_users / $total_users_at_last_month_end) * 100 - 100) : 0; // Yüzde büyüme
$overall_growth_change_color = ($overall_growth_percentage >= 0) ? 'text-success' : 'text-danger';


// REVENUE (BONUS CLICKS) - Son 7 gün toplam tıklama sayısı
$stmt_bonus_clicks_7_days = $pdo->prepare("SELECT COUNT(*) FROM user_bonuses WHERE clicked_at >= ? AND clicked_at <= ?");
$stmt_bonus_clicks_7_days->execute([$last_7_days_start, $today_end]);
$total_bonus_clicks_7_days = $stmt_bonus_clicks_7_days->fetchColumn();

$stmt_bonus_clicks_previous_7_days = $pdo->prepare("SELECT COUNT(*) FROM user_bonuses WHERE clicked_at >= ? AND clicked_at <= ?");
$stmt_bonus_clicks_previous_7_days->execute([$previous_7_days_start, $previous_7_days_end]);
$total_bonus_clicks_previous_7_days_count = $stmt_bonus_clicks_previous_7_days->fetchColumn();

$bonus_clicks_change_7_days = $total_bonus_clicks_7_days - $total_bonus_clicks_previous_7_days_count;
$percentage_change_bonus_clicks_7_days = ($total_bonus_clicks_previous_7_days_count > 0) ? ($bonus_clicks_change_7_days / $total_bonus_clicks_previous_7_days_count) * 100 : 0;
$change_color_class_bonus_clicks_7_days = ($bonus_clicks_change_7_days > 0) ? 'text-success' : (($bonus_clicks_change_7_days < 0) ? 'text-danger' : '');


// UNIQUE VISITS (Son 7 gün tekil ziyaretçi sayısı)
$stmt_unique_visits_7_days = $pdo->prepare("SELECT COUNT(DISTINCT ip_address, visit_date) FROM unique_visits WHERE visit_date >= ? AND visit_date <= ?");
$stmt_unique_visits_7_days->execute([$last_7_days_start, $today_end]);
$total_visits_7_days = $stmt_unique_visits_7_days->fetchColumn(); 

$stmt_visits_previous_7_days = $pdo->prepare("SELECT COUNT(DISTINCT ip_address, visit_date) FROM unique_visits WHERE visit_date >= ? AND visit_date <= ?");
$stmt_visits_previous_7_days->execute([$previous_7_days_start, $previous_7_days_end]);
$total_visits_previous_7_days_count = $stmt_visits_previous_7_days->fetchColumn();

$visits_change_7_days = $total_visits_7_days - $total_visits_previous_7_days_count;
$percentage_change_visits_7_days = ($total_visits_previous_7_days_count > 0) ? ($visits_change_7_days / $total_visits_previous_7_days_count) * 100 : 0;
$change_color_class_visits_7_days = ($visits_change_7_days > 0) ? 'text-success' : (($visits_change_7_days < 0) ? 'text-danger' : '');


// --- En Çok Tıklanan Bonuslar Tablosu ---
$most_clicked_bonuses = [];
try {
    $stmt_most_clicked = $pdo->query("
        SELECT 
            b.title AS bonus_name,
            b.image_url AS bonus_image_url,
            b.id AS bonus_id,
            COUNT(ub.id) AS total_clicks,
            COUNT(DISTINCT ub.user_id) AS unique_users_clicked
        FROM user_bonuses ub
        JOIN bonuses b ON ub.bonus_id = b.id
        GROUP BY b.id, b.title, b.image_url
        ORDER BY total_clicks DESC
        LIMIT 10
    ");
    $most_clicked_bonuses = $stmt_most_clicked->fetchAll();
} catch (PDOException $e) {
    error_log("En çok tıklanan bonuslar çekilirken hata: " . $e->getMessage());
    $most_clicked_bonuses = [];
}

// --- Grafik Verilerini Çekme (SON 7 VEYA 30 GÜN İÇİN) ---

// TOTAL USERS Chart (Daily registrations for last 30 days)
$chart_labels_total_users = []; $chart_data_total_users = [];
for ($i = 29; $i >= 0; $i--) { $date = date('Y-m-d', strtotime("-$i days")); $display_label = date('d M', strtotime("-$i days")); $stmt_daily = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND DATE(created_at) = ?"); $stmt_daily->execute([$date]); $chart_labels_total_users[] = $display_label; $chart_data_total_users[] = $stmt_daily->fetchColumn(); }
$chart_labels_total_users_json = json_encode($chart_labels_total_users); $chart_data_total_users_json = json_encode($chart_data_total_users);

// ACTIVE USERS Chart (Daily active users for last 30 days)
$chart_labels_active_users = []; $chart_data_active_users = [];
for ($i = 29; $i >= 0; $i--) { $date = date('Y-m-d', strtotime("-$i days")); $display_label = date('d M', strtotime("-$i days")); $stmt_daily = $pdo->prepare("SELECT COUNT(DISTINCT user_id) FROM user_bonuses WHERE DATE(clicked_at) = ?"); $stmt_daily->execute([$date]); $chart_labels_active_users[] = $display_label; $chart_data_active_users[] = $stmt_daily->fetchColumn(); }
$chart_labels_active_users_json = json_encode($chart_labels_active_users); $chart_data_active_users_json = json_encode($chart_data_active_users);

// TODAY'S NEW USERS Chart (Daily new users for last 7 days)
$chart_labels_today_sales = []; $chart_data_today_sales = [];
for ($i = 6; $i >= 0; $i--) { $date = date('Y-m-d', strtotime("-$i days")); $display_label = date('D', strtotime("-$i days")); $stmt_daily = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'user' AND DATE(created_at) = ?"); $stmt_daily->execute([$date]); $chart_labels_today_sales[] = $display_label; $chart_data_today_sales[] = $stmt_daily->fetchColumn(); }
$chart_labels_today_sales_json = json_encode($chart_labels_today_sales); $chart_data_today_sales_json = json_encode($chart_data_today_sales);


// REVENUE Chart (Daily bonus clicks for last 7 days)
$chart_labels_revenue = []; $chart_data_revenue = [];
for ($i = 6; $i >= 0; $i--) { $date = date('Y-m-d', strtotime("-$i days")); $display_label = date('D', strtotime("-$i days")); $stmt_daily = $pdo->prepare("SELECT COUNT(*) FROM user_bonuses WHERE DATE(clicked_at) = ?"); $stmt_daily->execute([$date]); $chart_labels_revenue[] = $display_label; $chart_data_revenue[] = $stmt_daily->fetchColumn(); }
$chart_labels_revenue_json = json_encode($chart_labels_revenue); $chart_data_revenue_json = json_encode($chart_data_revenue);

// UNIQUE VISITS Chart (Daily unique visits for last 7 days)
$chart_labels_unique_visits = []; $chart_data_unique_visits = [];
for ($i = 6; $i >= 0; $i--) { $date = date('Y-m-d', strtotime("-$i days")); $display_label = date('D', strtotime("-$i days")); $stmt_daily = $pdo->prepare("SELECT COUNT(DISTINCT ip_address) FROM unique_visits WHERE visit_date = ?"); $stmt_daily->execute([$date]); $chart_labels_unique_visits[] = $display_label; $chart_data_unique_visits[] = $stmt_daily->fetchColumn(); }
$chart_labels_unique_visits_json = json_encode($chart_labels_unique_visits); $chart_data_unique_visits_json = json_encode($chart_data_unique_visits);

// ACTIVE SUBSCRIPTIONS Chart (Daily unique visits for last 7 days - same as UNIQUE VISITS for now)
$chart_labels_active_subscriptions = $chart_labels_unique_visits;
$chart_data_active_subscriptions = $chart_data_unique_visits;
$chart_labels_active_subscriptions_json = $chart_labels_unique_visits_json;
$chart_data_active_subscriptions_json = $chart_data_unique_visits_json;

?>



<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card card-md">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-lg-8">
                                <h3 class="h2 text-dark">Tekrar Hoş Geldiniz, <?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?>!</h3>
                                <p class="fs-4 text-muted">Ufak bir hatırlatma yapmak isterim lütfen Google Adsense reklamlarınızın aktif olup olmadığını kontrol edin.</p>
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
                               <span class="avatar avatar-2 bg-blue-lt">
                        <!-- Download SVG icon from http://tabler.io/icons/icon/user -->
                       <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-user"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2a5 5 0 1 1 -5 5l.005 -.217a5 5 0 0 1 4.995 -4.783z" /><path d="M14 14a5 5 0 0 1 5 5v1a2 2 0 0 1 -2 2h-10a2 2 0 0 1 -2 -2v-1a5 5 0 0 1 5 -5h4z" /></svg>
                      </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    TOPLAM ÜYELER
                                </div>
                                <div class="text-muted">
                                    <?php echo $total_users; ?> üye
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
                                <span class="bg-orange text-white avatar">
<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-trending-up"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 17l6 -6l4 4l8 -8" /><path d="M14 7l7 0l0 7" /></svg>

</svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    GENEL BÜYÜME ORANI
                                </div>
                                <div class="text-muted">
                                    <?php echo sprintf('%.1f%%', abs($overall_growth_percentage)); ?> <?php echo ($overall_growth_percentage >= 0) ? 'artış' : 'azalış'; ?>
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
                                <span class="bg-twitter text-white avatar">
<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-click"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12l3 0" /><path d="M12 3l0 3" /><path d="M7.8 7.8l-2.2 -2.2" /><path d="M16.2 7.8l2.2 -2.2" /><path d="M7.8 16.2l-2.2 2.2" /><path d="M12 12l9 3l-4 2l-2 4l-3 -9" /></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    TOPLAM BONUS TIKLAMASI
                                </div>
                                <div class="text-muted">
                                    <?php echo $total_bonus_clicks; ?> tıklama
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
                                <span class="bg-red text-white avatar">

<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-zoom-scan"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 8v-2a2 2 0 0 1 2 -2h2" /><path d="M4 16v2a2 2 0 0 0 2 2h2" /><path d="M16 4h2a2 2 0 0 1 2 2v2" /><path d="M16 20h2a2 2 0 0 0 2 -2v-2" /><path d="M8 11a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /><path d="M16 16l-2.5 -2.5" /></svg>
                                </span>
                            </div>
                            <div class="col">
                                <div class="font-weight-medium">
                                    TOPLAM TEKİL ZİYARET
                                </div>
                                <div class="text-muted">
                                    <?php echo $total_visits; ?> ziyaret
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="subheader">TOPLAM ÜYELER</div>
                            <div class="ms-auto lh-1">
                                <div class="dropdown">
                                                                       <div class="dropdown-menu dropdown-menu-end">
                                                                           </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline">
                            <div class="h1 mb-0 me-2"><?php echo $total_users; ?></div>
                            <div class="me-auto">
                                <span class="d-inline-flex align-items-center me-1 <?php echo $change_color_class_total_users; ?>">
                                    <?php echo sprintf('%.1f%%', abs($percentage_change_total_users)); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <?php if ($increase_from_last_month_users > 0): ?><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" transform="rotate(180 12 12)" /><?php elseif ($increase_from_last_month_users < 0): ?><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" /><?php endif; ?>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="card-chart-container chart-md-height">
                            <canvas id="totalUsersChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="subheader">AKTİF KULLANICILAR</div>
                            <div class="ms-auto lh-1">
                                <div class="dropdown">
                                  
                                    <div class="dropdown-menu dropdown-menu-end">
                                                                            </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex align-items-baseline">
                            <div class="h1 mb-0 me-2"><?php echo $active_users_30_days; ?></div>
                            <div class="me-auto">
                                <span class="d-inline-flex align-items-center me-1 <?php echo $change_color_class_active_users; ?>">
                                    <?php echo sprintf('%.1f%%', abs($percentage_change_active_users)); ?>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon ms-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                        <?php if ($active_users_change > 0): ?><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" transform="rotate(180 12 12)" /><?php elseif ($active_users_change < 0): ?><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" /><?php endif; ?>
                                    </svg>
                                </span>
                            </div>
                        </div>
                        <div class="card-chart-container chart-md-height d-flex justify-content-center align-items-center position-relative">
                            <canvas id="activeUsersChart"></canvas>
                            <div class="position-absolute fs-1 fw-bold text-primary" style="z-index: 1;">
                                <?php echo round($active_user_ratio_percentage); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-12">
                <div class="row row-cards">
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">BUGÜN YENİ ÜYELER</div>
                                    <div class="ms-auto lh-1">
                                        <span class="d-inline-flex align-items-center me-1 <?php echo $change_color_class_today_users; ?>">
                                            <?php echo sprintf('%.1f%%', abs($percentage_change_today_users)); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" transform="rotate(180 12 12)"/></svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="h1 mb-3"><?php echo $today_new_users; ?></div>
                                <div class="card-chart-container chart-sm-height">
                                    <canvas id="todaySalesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">GENEL BÜYÜME ORANI</div>
                                    <div class="ms-auto lh-1">
                                        <span class="d-inline-flex align-items-center me-1 <?php echo $overall_growth_change_color; ?>">
                                            <?php echo sprintf('%.1f%%', abs($overall_growth_percentage)); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" transform="rotate(180 12 12)"/></svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="h1 mb-3"><?php echo $total_users; ?></div>
                                <div class="card-chart-container chart-sm-height">
                                    <canvas id="growthRateChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">SON 7 GÜN BONUS TIKLAMA</div>
                                    <div class="ms-auto lh-1">
                                        <span class="d-inline-flex align-items-center me-1 <?php echo $change_color_class_bonus_clicks_7_days; ?>">
                                            <?php echo sprintf('%.1f%%', abs($percentage_change_bonus_clicks_7_days)); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <?php if ($bonus_clicks_change_7_days > 0): ?><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" transform="rotate(180 12 12)" /><?php elseif ($bonus_clicks_change_7_days < 0): ?><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" /><?php endif; ?>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="h1 mb-3"><?php echo $total_bonus_clicks_7_days; ?></div>
                                <div class="card-chart-container chart-sm-height">
                                    <canvas id="revenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-sm-6 col-lg-3">
                        <div class="card card-sm">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="subheader">SON 7 GÜN TEKİL ZİYARET</div>
                                    <div class="ms-auto lh-1">
                                        <span class="d-inline-flex align-items-center me-1 <?php echo $change_color_class_visits_7_days; ?>">
                                            <?php echo sprintf('%.1f%%', abs($percentage_change_visits_7_days)); ?>
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                                <?php if ($visits_change_7_days > 0): ?><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" transform="rotate(180 12 12)" /><?php elseif ($visits_change_7_days < 0): ?><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 15l-6 -6l-6 6h12" /><?php endif; ?>
                                            </svg>
                                        </span>
                                    </div>
                                </div>
                                <div class="h1 mb-3"><?php echo $total_visits_7_days; ?></div>
                                <div class="card-chart-container chart-sm-height">
                                    <canvas id="uniqueVisitsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-3">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">En Çok Tıklanan Bonuslar</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                                <tr>
                                    <th>Görsel</th>
                                    <th>Bonus Adı</th>
                                    <th>Toplam Tıklama</th>
                                    <th>Tekil Kullanıcı Tıklaması</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($most_clicked_bonuses)): ?>
                                    <?php foreach ($most_clicked_bonuses as $bonus): ?>
                                        <tr>
                                            <td>
                                                <?php
                                                $bonus_image_src = htmlspecialchars($bonus['bonus_image_url'] ?? '');
                                                if (!empty($bonus_image_src) && strpos($bonus_image_src, 'http') !== 0) {
                                                    $bonus_image_src = BASE_URL . $bonus_image_src; 
                                                }
                                                ?>
                                                <img src="<?php echo $bonus_image_src; ?>" alt="<?php echo htmlspecialchars($bonus['bonus_name'] ?? 'Görsel'); ?>" style="max-width: 40px; max-height: 40px; border-radius: 4px;">
                                            </td>
                                            <td><a href="<?php echo BASE_URL; ?>admin/edit_bonus.php?id=<?php echo htmlspecialchars($bonus['bonus_id']); ?>" class="text-reset" target="_blank"><?php echo htmlspecialchars($bonus['bonus_name'] ?? 'Bilinmiyor'); ?></a></td>
                                            <td><?php echo htmlspecialchars($bonus['total_clicks'] ?? 0); ?></td>
                                            <td><?php echo htmlspecialchars($bonus['unique_users_clicked'] ?? 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Henüz tıklanan bonus bulunmamaktadır.</td>
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

<?php
// Sayfa ziyareti sayacını artır (unique_visits tablosuna ekleme yapan kod config.php'de)
// Bu kısım artık kullanılmıyor, istatistikler doğrudan veritabanından çekiliyor.
// Eğer "total_visits_last_month" gibi settings değerlerini manuel güncellemek istersen buradan yapabilirsin.
// Veya cronjob ile aylık istatistik sıfırlama/kaydetme yapılabilir.
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tüm chartların ortak ayarları için bir nesne oluşturalım
    const commonChartOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false },
            tooltip: {
                mode: 'index',
                intersect: false,
                callbacks: {
                    title: function(tooltipItems) { return tooltipItems[0].label; },
                    label: function(tooltipItem) { return 'Değer: ' + tooltipItem.raw; }
                }
            },
            datalabels: {
                 display: false
            }
        },
        scales: {
            x: { display: false, grid: { display: false } },
            y: { beginAtZero: true, display: false, grid: { display: false } }
        }
    };

    // TOTAL USERS Chart (Line Chart)
    var ctxTotalUsers = document.getElementById('totalUsersChart');
    if (ctxTotalUsers) {
        var chartLabelsTotalUsers = <?php echo json_encode($chart_labels_total_users); ?>;
        var chartDataTotalUsers = <?php echo json_encode($chart_data_total_users); ?>;
        new Chart(ctxTotalUsers.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartLabelsTotalUsers,
                datasets: [{
                    label: 'Günlük Kayıt',
                    data: chartDataTotalUsers,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: commonChartOptions
        });
    }

    // ACTIVE USERS Chart (Doughnut Chart)
    var ctxActiveUsers = document.getElementById('activeUsersChart');
    if (ctxActiveUsers) {
        var activeUserRatioPercentage = <?php echo round($active_user_ratio_percentage); ?>;
        new Chart(ctxActiveUsers.getContext('2d'), {
            type: 'doughnut',
            data: {
                datasets: [{
                    data: [activeUserRatioPercentage, 100 - activeUserRatioPercentage],
                    backgroundColor: ['#206bc4', '#e0e6ed'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '75%',
                rotation: -90,
                circumference: 180,
                plugins: {
                    legend: { display: false },
                    tooltip: { enabled: false }
                },
                elements: {
                    arc: { borderWidth: 0 }
                }
            }
        });
    }

    // TODAY'S NEW USERS Chart (Line Chart)
    var ctxTodaySales = document.getElementById('todaySalesChart');
    if (ctxTodaySales) {
        var chartLabelsTodaySales = <?php echo json_encode($chart_labels_today_sales); ?>;
        var chartDataTodaySales = <?php echo json_encode($chart_data_today_sales); ?>;
        new Chart(ctxTodaySales.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartLabelsTodaySales,
                datasets: [{
                    label: 'Günlük Yeni Üye',
                    data: chartDataTodaySales,
                    borderColor: '#4299e1', // Tabler blue
                    backgroundColor: 'rgba(66, 153, 225, 0.2)',
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: commonChartOptions
        });
    }

    // GROWTH RATE Chart (Line Chart - using total users data for simplicity)
    var ctxGrowthRate = document.getElementById('growthRateChart');
    if (ctxGrowthRate) {
        // Reuse total users chart data for growth rate visualization
        var chartLabelsGrowthRate = <?php echo json_encode($chart_labels_total_users); ?>;
        var chartDataGrowthRate = <?php echo json_encode($chart_data_total_users); ?>;
        new Chart(ctxGrowthRate.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartLabelsGrowthRate,
                datasets: [{
                    label: 'Toplam Üye',
                    data: chartDataGrowthRate,
                    borderColor: '#2ecc71', // Tabler green
                    backgroundColor: 'rgba(46, 204, 113, 0.2)',
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: commonChartOptions
        });
    }

    // REVENUE Chart (Line Chart - Daily bonus clicks for last 7 days)
    var ctxRevenue = document.getElementById('revenueChart');
    if (ctxRevenue) {
        var chartLabelsRevenue = <?php echo json_encode($chart_labels_revenue); ?>;
        var chartDataRevenue = <?php echo json_encode($chart_data_revenue); ?>;
        new Chart(ctxRevenue.getContext('2d'), {
            type: 'line',
            data: {
                labels: chartLabelsRevenue,
                datasets: [{
                    label: 'Günlük Bonus Tıklaması',
                    data: chartDataRevenue,
                    borderColor: '#fab005', // Tabler yellow
                    backgroundColor: 'rgba(250, 176, 5, 0.2)',
                    borderWidth: 2,
                    pointRadius: 0,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: commonChartOptions
        });
    }

    // UNIQUE VISITS Chart (Line Chart - Daily unique visits for last 7 days)
    var ctxUniqueVisits = document.getElementById('uniqueVisitsChart');
    if (ctxUniqueVisits) {
        var chartLabelsUniqueVisits = <?php echo json_encode($chart_labels_unique_visits); ?>;
        var chartDataUniqueVisits = <?php echo json_encode($chart_data_unique_visits); ?>;
        new Chart(ctxUniqueVisits.getContext('2d'), {
            type: 'bar', // Bar chart as per Tabler example for Active Subscriptions
            data: {
                labels: chartLabelsUniqueVisits,
                datasets: [{
                    label: 'Günlük Tekil Ziyaret',
                    data: chartDataUniqueVisits,
                    backgroundColor: '#206bc4', // Tabler primary blue for bars
                    borderColor: '#206bc4',
                    borderWidth: 0,
                    barPercentage: 0.7
                }]
            },
            options: commonChartOptions
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>