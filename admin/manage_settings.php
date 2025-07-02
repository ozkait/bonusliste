<?php
require_once __DIR__ . '/includes/admin_header.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Kayan yazı ayarını güncelle
    if (isset($_POST['scrolling_text'])) {
        $scrolling_text = trim($_POST['scrolling_text']);
        $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES ('scrolling_text', ?, 'Anasayfadaki kayan yazı metni') ON DUPLICATE KEY UPDATE setting_value = ?");
        if ($stmt->execute([$scrolling_text, $scrolling_text])) {
            $message .= '<div class="alert alert-success">Kayan yazı başarıyla güncellendi!</div>';
        } else {
            $message .= '<div class="alert alert-danger">Kayan yazı güncellenirken bir hata oluştu.</div>';
        }
    }

    // Üst promosyon barı ayarlarını güncelle
    if (isset($_POST['top_promo_logo']) && isset($_POST['top_promo_text']) && isset($_POST['top_promo_button_text']) && isset($_POST['top_promo_button_link'])) {
        $top_promo_logo = trim($_POST['top_promo_logo']);
        $top_promo_text = trim($_POST['top_promo_text']);
        $top_promo_button_text = trim($_POST['top_promo_button_text']);
        $top_promo_button_link = trim($_POST['top_promo_button_link']);

        $settings_to_update = [
            'top_promo_logo' => 'Üstteki promosyon barı logosu',
            'top_promo_text' => 'Üstteki promosyon barı metni',
            'top_promo_button_text' => 'Üstteki promosyon barı buton metni',
            'top_promo_button_link' => 'Üstteki promosyon barı buton linki'
        ];

        foreach ($settings_to_update as $key => $desc) {
            $value = $$key; // Değişken değişken kullanma
            $stmt = $pdo->prepare("INSERT INTO settings (setting_key, setting_value, description) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            if ($stmt->execute([$key, $value, $desc, $value])) {
                // Her başarılı güncelleme için mesaj eklenebilir, veya tek bir genel başarı mesajı verilebilir.
            } else {
                $message .= '<div class="alert alert-danger">Üst promosyon barı ayarları güncellenirken bir hata oluştu: ' . htmlspecialchars($key) . '</div>';
            }
        }
        if (empty($message)) {
            $message .= '<div class="alert alert-success">Üst promosyon barı ayarları başarıyla güncellendi!</div>';
        }
    }
}

// Ayarları veritabanından çek
$stmt_settings = $pdo->query("SELECT setting_key, setting_value FROM settings");
$current_settings = $stmt_settings->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Genel Site Ayarları</h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <form action="<?php echo ADMIN_URL; ?>manage_settings.php" method="POST">
                            <h4>Kayan Yazı Ayarları</h4>
                            <div class="mb-3">
                                <label class="form-label">Kayan Yazı Metni</label>
                                <textarea name="scrolling_text" class="form-control" rows="3"><?php echo htmlspecialchars($current_settings['scrolling_text'] ?? ''); ?></textarea>
                            </div>
                            <hr>
                            <h4>Üst Promosyon Barı Ayarları</h4>
                            <div class="mb-3">
                                <label class="form-label">Logo URL</label>
                                <input type="url" name="top_promo_logo" class="form-control" value="<?php echo htmlspecialchars($current_settings['top_promo_logo'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Promosyon Metni</label>
                                <input type="text" name="top_promo_text" class="form-control" value="<?php echo htmlspecialchars($current_settings['top_promo_text'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Buton Metni</label>
                                <input type="text" name="top_promo_button_text" class="form-control" value="<?php echo htmlspecialchars($current_settings['top_promo_button_text'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Buton Linki</label>
                                <input type="url" name="top_promo_button_link" class="form-control" value="<?php echo htmlspecialchars($current_settings['top_promo_button_link'] ?? ''); ?>">
                            </div>

                            <button type="submit" class="btn btn-primary">Ayarları Kaydet</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>