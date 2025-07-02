<?php
require_once 'config.php'; // Aynı dizinde olduğu için sadece dosya adı
check_user_role('user'); // Sadece giriş yapmış kullanıcılar erişebilir

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_role = $_SESSION['user_role'];

$current_page_section = isset($_GET['page']) ? $_GET['page'] : 'profile'; // Varsayılan olarak 'profile'

// Kullanıcı bilgilerini çek (Ayarlarım sayfası için de gerekli)
$stmt_user_info = $pdo->prepare("SELECT id, username, full_name, email, phone_number, created_at FROM users WHERE id = ?");
$stmt_user_info->execute([$user_id]);
$user_info = $stmt_user_info->fetch();

// --- Aldığınız/Tıkladığınız Bonuslar ---
$stmt_user_bonuses = $pdo->prepare("SELECT b.title, b.image_url, ub.clicked_at
                                     FROM user_bonuses ub
                                     JOIN bonuses b ON ub.bonus_id = b.id
                                     WHERE ub.user_id = ?
                                     ORDER BY ub.clicked_at DESC");
$stmt_user_bonuses->execute([$user_id]);
$user_clicked_bonuses = $stmt_user_bonuses->fetchAll();

// --- Ayarlarım Bölümü için POST İşlemi ---
$settings_message = '';
if ($current_page_section == 'settings' && $_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone_number = trim($_POST['phone_number'] ?? '');
    $new_password = $_POST['new_password'] ?? '';
    $new_password_confirm = $_POST['new_password_confirm'] ?? '';

    if (empty($full_name) || empty($email) || empty($phone_number)) {
        $settings_message = '<div class="alert alert-danger">Lütfen tüm zorunlu alanları doldurun.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $settings_message = '<div class="alert alert-danger">Geçerli bir e-posta adresi girin.</div>';
    } elseif (!preg_match('/^\+90\d{10}$/', $phone_number)) { // +90 ve ardından 10 rakam
        $settings_message = '<div class="alert alert-danger">Telefon numarası +90 ile başlamalı ve 10 rakam içermelidir (örn: +905XXXXXXXXX).</div>';
    } elseif ($new_password !== '') { // Yeni şifre girildiyse şifre kontrollerini yap
        if (strlen($new_password) < 6) {
            $settings_message = '<div class="alert alert-danger">Yeni şifre en az 6 karakter olmalıdır.</div>';
        } elseif ($new_password !== $new_password_confirm) {
            $settings_message = '<div class="alert alert-danger">Yeni şifreler uyuşmuyor.</div>';
        }
    }
    
    // E-posta veya telefon başka bir kullanıcıda var mı kontrol et (kendisi hariç)
    if (empty($settings_message)) { // Önceki kontrollerde hata yoksa devam et
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR phone_number = ?) AND id != ?");
        $stmt_check->execute([$email, $phone_number, $user_id]);
        if ($stmt_check->fetch()) {
            $settings_message = '<div class="alert alert-danger">Bu e-posta veya telefon numarası zaten başka bir kullanıcıya ait.</div>';
        }
    }

    if (empty($settings_message)) { // Son kontrollerden sonra hata yoksa güncelle
        $sql_update = "UPDATE users SET full_name = ?, email = ?, phone_number = ? ";
        $params_update = [$full_name, $email, $phone_number];

        if (!empty($new_password)) {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $sql_update .= ", password_hash = ? ";
            $params_update[] = $password_hash;
        }
        $sql_update .= " WHERE id = ?";
        $params_update[] = $user_id;
        
        try {
            $stmt_update_user = $pdo->prepare($sql_update);
            if ($stmt_update_user->execute($params_update)) {
                $settings_message = '<div class="alert alert-success">Bilgileriniz başarıyla güncellendi!</div>';
                // Kullanıcı bilgilerini formda güncel görünmesi için tekrar çek
                $stmt_user_info->execute([$user_id]);
                $user_info = $stmt_user_info->fetch();
            } else {
                $error_info = $stmt_update_user->errorInfo();
                $settings_message = '<div class="alert alert-danger">Bilgiler güncellenirken bir hata oluştu: ' . ($error_info[2] ?? 'Bilinmeyen SQL hatası.') . '</div>';
            }
        } catch (PDOException $e) {
            $settings_message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($username); ?> Profilim</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
    <style>
        /* Genel Stil */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #1a1a1a; /* Koyu arka plan */
            color: #e0e0e0; /* Açık yazı rengi */
            margin: 0;
            padding: 20px;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        /* Ana Konteyner */
        .main-container {
            flex-grow: 1; /* Sayfanın geri kalanını doldur */
            display: flex;
            flex-direction: column;
        }

        /* Header (Navigasyon Menüsü) */
        .profile-header {
            background-color: #2a2a2a; /* Koyu gri header */
            padding: 1.2rem 2rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #444;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .profile-header-left h1 {
            color: #4a90e2; /* Mavi başlık */
            margin: 0;
            font-size: 1.8rem;
            font-weight: 600;
        }

        .profile-nav ul {
            list-style: none;
            margin: 0;
            padding: 0;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .profile-nav a {
            color: #e0e0e0;
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            transition: background-color 0.2s ease, color 0.2s ease;
        }

        .profile-nav a:hover,
        .profile-nav a.active {
            background-color: #4a90e2;
            color: #fff;
        }

        /* İçerik Alanı */
        .profile-content {
            flex-grow: 1;
            padding: 2.5rem;
            background-color: #1a1a1a; /* Sayfa arka planıyla aynı */
        }

        .content-card {
            background-color: #2a2a2a;
            padding: 2.5rem;
            border-radius: 12px;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
            max-width: 900px;
            margin: 0 auto;
            border: 1px solid #3a3a3a;
        }

        .content-card h3 {
            color: #f5a623;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
            border-bottom: 2px solid #f5a623;
            padding-bottom: 10px;
            display: inline-block;
            width: auto;
        }

        /* Profil Bilgileri Stili */
        .profile-info p {
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        .profile-info strong {
            color: #4a90e2;
            display: inline-block;
            min-width: 120px; /* Label'ların hizalı olması için */
        }

        /* YENİ BONUS LİSTESİ TASARIMI */
        .bonuses-scroll-container {
            overflow-x: auto; /* Yatay kaydırma */
            padding-bottom: 15px; /* Kaydırma çubuğu için boşluk */
        }
        .bonus-cards-horizontal {
            display: flex; /* Yatay sıralama */
            gap: 15px; /* Kartlar arası boşluk */
            padding: 5px; /* İç boşluk */
            min-width: fit-content; /* İçeriğin kaydırması için */
            white-space: nowrap; /* İçeriklerin alt satıra geçmesini engelle */
        }

        .bonus-card-small {
            flex: 0 0 160px; /* Kartların sabit genişliği */
            background-color: #383838;
            border-radius: 10px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.3);
            transition: transform 0.2s ease-in-out, box-shadow 0.2s ease-in-out;
            min-height: 180px; /* Kart yüksekliğini koru */
            justify-content: space-between; /* İçeriği dikeyde yay */
            border: 1px solid #4a4a4a; /* Küçük kenarlık */
        }
        .bonus-card-small:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(0,0,0,0.4);
        }

        .bonus-card-image {
            width: 70px;
            height: 70px;
            object-fit: contain;
            border-radius: 6px;
            margin-bottom: 10px;
            border: 1px solid #555;
            padding: 5px;
            background-color: #444; /* Görsel arkaplanı */
        }

        .bonus-card-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: #ffffff;
            line-height: 1.2;
            margin-bottom: 8px;
            white-space: normal; /* Başlıkların tek satırda kalmasını engelle */
            word-wrap: break-word; /* Uzun kelimeleri böl */
            max-height: 3em; /* 2-3 satır başlık için */
            overflow: hidden; /* Fazla metni gizle */
        }

        .bonus-card-date {
            font-size: 0.75rem;
            color: #bbbbbb;
            margin-top: auto;
            padding-top: 8px;
            border-top: 1px dashed #555;
            width: 100%;
        }

        /* Form Stili (Ayarlarım) */
        .settings-form .form-group {
            margin-bottom: 1.5rem;
        }
        .settings-form label {
            display: block;
            margin-bottom: 0.5rem;
            color: #e0e0e0;
            font-weight: 500;
        }
        .settings-form input[type="text"],
        .settings-form input[type="email"],
        .settings-form input[type="tel"],
        .settings-form input[type="password"] {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid #444;
            border-radius: 6px;
            background-color: #333;
            color: #e0e0e0;
            font-size: 1rem;
            transition: border-color 0.2s ease, box-shadow 0.2s ease;
        }
        .settings-form input:focus {
            outline: none;
            border-color: #4a90e2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.3);
        }
        .settings-form button {
            background-color: #4a90e2;
            color: #fff;
            padding: 0.8rem 2rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1.1rem;
            font-weight: 600;
            transition: background-color 0.2s ease;
        }
        .settings-form button:hover {
            background-color: #357bd8;
        }
        .settings-message {
            margin-bottom: 1rem;
            padding: 10px 15px;
            border-radius: 5px;
            font-weight: 500;
        }
        .settings-message.alert-danger {
            background-color: #5c2b2b;
            color: #ff9898;
            border: 1px solid #e74c3c;
        }
        .settings-message.alert-success {
            background-color: #2b5c2b;
            color: #98ff98;
            border: 1px solid #2ecc71;
        }

        /* Genel Linkler */
        .bottom-links {
            text-align: center;
            margin-top: 30px;
            font-size: 1rem;
        }
        .bottom-links a {
            color: #4a90e2;
            text-decoration: none;
            font-weight: 500;
            padding: 0 10px;
            transition: text-decoration 0.2s ease;
        }
        .bottom-links a:hover {
            text-decoration: underline;
        }

        /* Responsive Ayarlar */
        @media (max-width: 768px) {
            .profile-header {
                flex-direction: column;
                text-align: center;
                gap: 1rem;
            }
            .profile-nav ul {
                justify-content: center;
                gap: 1rem;
            }
            .profile-content {
                padding: 1.5rem;
            }
            .content-card {
                padding: 1.5rem;
            }
            /* Küçük ekranlarda yatay kaydırma için ayar */
            .bonuses-scroll-container {
                padding-bottom: 10px; /* Kaydırma çubuğu için boşluk */
            }
        }
        @media (max-width: 480px) {
            .profile-header h1 {
                font-size: 1.5rem;
            }
            .profile-nav a {
                padding: 0.4rem 0.8rem;
                font-size: 0.9rem;
            }
        }
    </style>
</head>
<body>
    <div class="main-container">
        <header class="profile-header">
            <div class="profile-header-left">
                <h1>Merhaba, <?php echo htmlspecialchars($user_info['username']); ?>!</h1>
            </div>
            <nav class="profile-nav">
                <ul>
                    <li><a href="<?php echo BASE_URL; ?>profile.php?page=profile" class="<?php echo ($current_page_section == 'profile' ? 'active' : ''); ?>">Üye Profilim</a></li>
                    <li><a href="<?php echo BASE_URL; ?>profile.php?page=settings" class="<?php echo ($current_page_section == 'settings' ? 'active' : ''); ?>">Ayarlarım</a></li>
                    <?php if ($user_role == 'admin'): ?>
                        <li><a href="<?php echo ADMIN_URL; ?>index.php">Admin Paneli</a></li>
                    <?php endif; ?>
                    <li><a href="<?php echo BASE_URL; ?>logout.php">Çıkış Yap</a></li>
                </ul>
            </nav>
        </header>

        <div class="profile-content">
            <div class="content-card">
                <?php if ($current_page_section == 'profile'): ?>
                    <h3>Üye Profil Bilgileri</h3>
                    <div class="profile-info">
                        <p><strong>Kullanıcı Adı:</strong> <?php echo htmlspecialchars($user_info['username'] ?? 'N/A'); ?></p>
                        <p><strong>İsim Soyisim:</strong> <?php echo htmlspecialchars($user_info['full_name'] ?? 'N/A'); ?></p>
                        <p><strong>E-posta:</strong> <?php echo htmlspecialchars($user_info['email'] ?? 'N/A'); ?></p>
                        <p><strong>Telefon:</strong> <?php echo htmlspecialchars($user_info['phone_number'] ?? 'N/A'); ?></p>
                        <p><strong>Kayıt Tarihi:</strong> <?php echo date('d.m.Y H:i', strtotime($user_info['created_at'])); ?></p>
                    </div>

                    <div class="section-header-wrapper">
                         <h3>Aldığınız/Tıkladığınız Bonuslar</h3>
                    </div>
                    <?php if (!empty($user_clicked_bonuses)): ?>
                        <div class="bonuses-scroll-container"> <div class="bonus-cards-horizontal"> <?php foreach ($user_clicked_bonuses as $bonus): ?>
                                    <div class="bonus-card-small"> <?php
                                        // Görsel yolu oluşturma mantığı
                                        $bonus_image_src = htmlspecialchars($bonus['image_url'] ?? '');
                                        if (!empty($bonus_image_src)) {
                                            if (strpos($bonus_image_src, 'http') !== 0) { // Eğer URL 'http' ile başlamıyorsa (yani yerel bir yolsa)
                                                // Admin paneli altındaki uploads klasöründen gelsin
                                                $bonus_image_src = BASE_URL . 'admin/' . $bonus_image_src;
                                            }
                                        } else {
                                            $bonus_image_src = 'https://via.placeholder.com/70x70?text=Bonus'; // Varsayılan görsel
                                        }
                                        ?>
                                        <img src="<?php echo $bonus_image_src; ?>" alt="<?php echo htmlspecialchars($bonus['title'] ?? 'Bonus'); ?>" class="bonus-card-image">
                                        <div class="bonus-card-title"><?php echo htmlspecialchars($bonus['title'] ?? 'Bonus Adı Yok'); ?></div>
                                        <div class="bonus-card-date">Tıklanma: <?php echo date('d.m.Y', strtotime($bonus['clicked_at'])); ?></div> </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <p class="empty-message">Henüz aldığınız/tıkladığınız bir bonus bulunmamaktadır.</p>
                    <?php endif; ?>

                <?php elseif ($current_page_section == 'settings'): ?>
                    <h3>Hesap Ayarları</h3>
                    <?php if ($settings_message): ?>
                        <div class="settings-message <?php echo strpos($settings_message, 'başarıyla') !== false ? 'alert-success' : 'alert-danger'; ?>">
                            <?php echo $settings_message; ?>
                        </div>
                    <?php endif; ?>
                    <form action="<?php echo BASE_URL; ?>profile.php?page=settings" method="POST" class="settings-form">
                        <div class="form-group">
                            <label for="full_name">İsim Soyisim:</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user_info['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">E-posta:</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user_info['email'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="phone_number">Telefon Numarası:</label>
                            <input type="tel" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($user_info['phone_number'] ?? ''); ?>" pattern="^\+90\d{10}$" title="+90 ile başlayıp 10 rakam içermelidir" required>
                        </div>
                        <div class="form-group">
                            <label for="new_password">Yeni Şifre:</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Şifrenizi değiştirmek için girin">
                            <small class="form-hint" style="color: #bbb;">Boş bırakırsanız şifreniz değişmez.</small>
                        </div>
                        <div class="form-group">
                            <label for="new_password_confirm">Yeni Şifre Tekrar:</label>
                            <input type="password" id="new_password_confirm" name="new_password_confirm" placeholder="Yeni şifrenizi tekrar girin">
                        </div>
                        <button type="submit">Ayarları Kaydet</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="bottom-links">
            <a href="<?php echo BASE_URL; ?>index.php">Anasayfaya Dön</a> | <a href="<?php echo BASE_URL; ?>logout.php">Çıkış Yap</a>
        </div>
    </div>
</body>
</html>