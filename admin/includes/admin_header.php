<?php
// admin/includes/admin_header.php
require_once __DIR__ . '/../../config.php'; // config.php'ye giden yol
check_admin_auth(); // Admin yetkisi kontrolü

// Sayfa başlığını dinamik olarak ayarlayalım, eğer ayarlanmamışsa 'Dashboard' olsun
$page_title = $page_title ?? 'Dashboard'; 
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title><?php echo htmlspecialchars($page_title); ?> - Admin Paneli</title>
    
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-flags.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-payments.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-vendors.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/demo.min.css" rel="stylesheet"/>
    
    <style>
        :root { --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; }
        body { font-feature-settings: "liga" 0; }
        /* Özel stil: İstatistik kartlarındaki grafik konteynerleri için */
        .card-chart-container {
            position: relative;
            height: 80px; /* Grafik yüksekliği için varsayılan */
            width: 100%;
        }
        .chart-sm-height { height: 50px; /* Daha küçük grafikler için */ }
        .chart-md-height { height: 80px; /* Orta boy grafikler için */ }
        .chart-lg-height { height: 120px; /* Büyük grafikler için */ }

        /* Navbar'daki kullanıcı avatarı ve ismi için */
        .navbar-nav .avatar { margin-right: 0.5rem; }
        .navbar-nav .d-xl-block div { line-height: 1.2; }
        /* Dashboard Sayfası için Özel Header ve Body Renkleri */
        .layout-navbar-light .navbar-expand-md { background-color: #fff; } /* Navbar arkaplanı beyaz */
        .layout-navbar-light .navbar-nav .nav-link { color: #333; } /* Navbar link rengi koyu */
        .layout-navbar-light .navbar-brand-image { filter: invert(1); } /* Dark logoyu light backgrounda uydur */
        .page-wrapper { background-color: #f5f7fb; } /* Genel sayfa arkaplanı */
        .page-header { background-color: #f5f7fb; } /* Header arkaplanı */
    </style>
</head>
<body class="layout-navbar-light"> <div class="page">
        <header class="navbar navbar-expand-md navbar-light d-print-none"> <div class="container-xl">
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbar-menu" aria-controls="navbar-menu" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <h1 class="navbar-brand navbar-brand-autodark d-none-navbar-horizontal pe-0 pe-md-3">
                    <a href="<?php echo ADMIN_URL; ?>">
                       
                    </a>
                </h1>
                <div class="navbar-nav flex-row order-md-last">
                    <div class="d-none d-md-flex">
                                           </div>
                    <div class="nav-item dropdown d-none d-md-flex me-3">
                        <a href="#" class="nav-link px-0" data-bs-toggle="dropdown" tabindex="-1" aria-label="Show notifications">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 5a2 2 0 0 1 4 0a7 7 0 0 1 4 6v3a4 4 0 0 0 2 3h-16a4 4 0 0 0 2 -3v-3a7 7 0 0 1 4 -6"/><path d="M9 17v1a3 3 0 0 0 6 0v-1"/></svg>
                            <span class="badge bg-red"></span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-arrow dropdown-menu-end dropdown-menu-card">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Son Bildirimler</h3>
                                </div>
                                <div class="list-group list-group-flush list-group-hoverable">
                                    <div class="list-group-item">
                                        <div class="row align-items-center">
                                            <div class="col-auto"><span class="status-dot status-dot-animated bg-green d-block"></span></div>
                                            <div class="col text-truncate">
                                                <a href="#" class="text-body d-block">Yeni Üye Kaydı</a>
                                                <small class="d-block text-muted text-truncate mt-n1">1 yeni üye katıldı.</small>
                                            </div>
                                            <div class="col-auto">
                                                <a href="#" class="list-group-item-actions">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon text-muted" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 18l-2 0l-3.5 -5l-4.5 -2l5 -2l2 3z"/></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="nav-item dropdown">
                        <a href="#" class="nav-link d-flex lh-1 text-reset p-0" data-bs-toggle="dropdown" aria-label="Open user menu">
                           <span class="avatar avatar-0">
                        <!-- Download SVG icon from http://tabler.io/icons/icon/user -->
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon avatar-icon icon-2">
                          <path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"></path>
                          <path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"></path>
                        </svg>
                      </span>
                            <div class="d-none d-xl-block ps-2">
                                <div><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></div>
                                <div class="mt-1 small text-muted">Yetki: <?php echo htmlspecialchars(ucfirst($_SESSION['user_role'] ?? 'Yok')); ?></div>
                            </div>
                        </a>
                        <div class="dropdown-menu dropdown-menu-end dropdown-menu-arrow">
                            <a href="<?php echo BASE_URL; ?>logout.php" class="dropdown-item">Çıkış Yap</a>
                        </div>
                    </div>
                </div>
                <div class="collapse navbar-collapse" id="navbar-menu">
                    <div class="d-flex flex-column flex-md-row flex-fill align-items-stretch align-items-md-center">
                        <ul class="navbar-nav">
                            <li class="nav-item<?php echo (basename($_SERVER['PHP_SELF']) == 'index.php' ? ' active' : ''); ?>">
                                <a class="nav-link" href="<?php echo ADMIN_URL; ?>">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-home-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 21v-6a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2" /><path d="M19 13.488v-1.488h2l-9 -9l-9 9h2v7a2 2 0 0 0 2 2h4.525" /><path d="M15 19l2 2l4 -4" /></svg>
                                    </span>
                                    <span class="nav-link-title">
                                        Anasayfa
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_bonuses.php' || basename($_SERVER['PHP_SELF']) == 'edit_bonus.php' ? ' active' : ''); ?>">
                                <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_bonuses.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-unlink"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M17 22v-2" /><path d="M9 15l6 -6" /><path d="M11 6l.463 -.536a5 5 0 0 1 7.071 7.072l-.534 .464" /><path d="M13 18l-.397 .534a5.068 5.068 0 0 1 -7.127 0a4.972 4.972 0 0 1 0 -7.071l.524 -.463" /><path d="M20 17h2" /><path d="M2 7h2" /><path d="M7 2v2" /></svg>
                                    </span>
                                    <span class="nav-link-title">
                                        Bonusları Yönet
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_popups.php' ? ' active' : ''); ?>">
                                <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_popups.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                       <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-help-square-rounded"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c7.2 0 9 1.8 9 9s-1.8 9 -9 9s-9 -1.8 -9 -9s1.8 -9 9 -9z" /><path d="M12 16v.01" /><path d="M12 13a2 2 0 0 0 .914 -3.782a1.98 1.98 0 0 0 -2.414 .483" /></svg>
                                    </span>
                                    <span class="nav-link-title">
                                        Popup Yönetimi
                                    </span>
                                </a>
                            </li>

<li class="nav-item<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_carousel.php' ? ' active' : ''); ?>">
                                <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_carousel.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-slideshow"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l.01 0" /><path d="M3 3m0 3a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v8a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3z" /><path d="M3 13l4 -4a3 5 0 0 1 3 0l4 4" /><path d="M13 12l2 -2a3 5 0 0 1 3 0l3 3" /><path d="M8 21l.01 0" /><path d="M12 21l.01 0" /><path d="M16 21l.01 0" /></svg>
                                    </span>
                                    <span class="nav-link-title">
                                        Carousel Yönetimi
                                    </span>
                                </a>
                            </li>

                             <li class="nav-item<?php echo (basename($_SERVER['PHP_SELF']) == 'users.php' || basename($_SERVER['PHP_SELF']) == 'edit_user.php' ? ' active' : ''); ?>">
                                <a class="nav-link" href="<?php echo ADMIN_URL; ?>users.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                       <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-users"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0" /><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2" /><path d="M16 3.13a4 4 0 0 1 0 7.75" /><path d="M21 21v-2a4 4 0 0 0 -3 -3.85" /></svg>
                                    </span>
                                    <span class="nav-link-title">
                                        Üyeler
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_settings.php' ? ' active' : ''); ?>">
                                <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_settings.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                       <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-settings-bolt"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M13.256 20.473c-.855 .907 -2.583 .643 -2.931 -.79a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.07 .26 1.488 1.29 1.254 2.15" /><path d="M19 16l-2 3h4l-2 3" /><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" /></svg>
                                    </span>
                                    <span class="nav-link-title">
                                        Ayarlar
                                    </span>
                                </a>
                            </li>
                            <li class="nav-item<?php echo (basename($_SERVER['PHP_SELF']) == 'manage_cloaker.php' ? ' active' : ''); ?>">
                                <a class="nav-link" href="<?php echo ADMIN_URL; ?>manage_cloaker.php">
                                    <span class="nav-link-icon d-md-none d-lg-inline-block">
                                      <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-clock"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0" /><path d="M12 7v5l3 3" /></svg>
                                    </span>
                                    <span class="nav-link-title">
                                        Cloaker Ayarları
                                    </span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </header>
        <div class="page-wrapper">