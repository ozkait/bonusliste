<?php
require_once __DIR__ . '/includes/admin_header.php';

// Fonksiyon: Dosya yükleme ve yol döndürme (manage_bonuses.php'den kopyalandı)
function uploadImage($file, $upload_dir) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['status' => 'error', 'message' => 'Dosya yüklenmedi veya geçersiz dosya.'];
    }

    $target_dir = $upload_dir; // uploads/bonuses/
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_file_size = 5 * 1024 * 1024; // 5 MB

    // Dosya türü ve boyutu kontrolü
    if (!in_array($file_extension, $allowed_extensions)) {
        return ['status' => 'error', 'message' => 'Sadece JPG, JPEG, PNG ve GIF dosyalarına izin verilir.'];
    }
    if ($file['size'] > $max_file_size) {
        return ['status' => 'error', 'message' => 'Dosya boyutu 5MB\'ı geçemez.'];
    }

    // Dosya adını güvenli hale getir
    $new_file_name = uniqid('bonus_') . '.' . $file_extension;
    $target_file = $target_dir . $new_file_name;

    // Klasör yoksa oluştur
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0755, true);
    }

    // Dosyayı taşı
    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        return ['status' => 'success', 'file_path' => $target_file];
    } else {
        return ['status' => 'error', 'message' => 'Dosya yüklenirken bilinmeyen bir hata oluştu.'];
    }
}


$bonus_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$bonus = null;
$message = '';

// Bonus bilgilerini çek
if ($bonus_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM bonuses WHERE id = ?");
    $stmt->execute([$bonus_id]);
    $bonus = $stmt->fetch();

    if (!$bonus) {
        $message = '<div class="alert alert-danger">Bonus bulunamadı.</div>';
        $bonus_id = 0; // Geçersiz ID durumunda formu gösterme
    }
} else {
    $message = '<div class="alert alert-danger">Geçersiz bonus ID\'si.</div>';
}

// Form gönderildiğinde bonusu güncelle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && $bonus_id > 0) {
    // POST verilerini alırken null coalescing operatörünü kullan
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bonus_code = trim($_POST['bonus_code'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $current_image_url = $bonus['image_url'] ?? ''; // Mevcut görsel URL'si
    $image_url_to_save = $current_image_url; // Kaydedilecek görsel URL'si
    
    $status = isset($_POST['status']) ? 1 : 0; // Checkbox değeri
    $sort_order = (int)($_POST['sort_order'] ?? 0); // Sıralama değeri

    // Dosya yükleme kontrolü
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['image_file'], 'uploads/bonuses/');
        if ($upload_result['status'] == 'success') {
            $image_url_to_save = $upload_result['file_path'];
            // Eski görseli sil (eğer yerel olarak yüklenmişse)
            if ($current_image_url && strpos($current_image_url, 'uploads/bonuses/') !== false && file_exists($current_image_url)) {
                unlink($current_image_url);
            }
        } else {
            $message = '<div class="alert alert-danger">Görsel yüklenirken hata: ' . $upload_result['message'] . '</div>';
        }
    } elseif (!empty($_POST['image_url_text'])) { // Eğer dosya yüklenmediyse ve URL girildiyse
        $image_url_to_save = trim($_POST['image_url_text']);
        if (!filter_var($image_url_to_save, FILTER_VALIDATE_URL)) {
             $message = '<div class="alert alert-danger">Geçersiz görsel URL formatı.</div>';
             $image_url_to_save = $current_image_url; // Geçersizse mevcut olanı koru
        } else {
            // Eğer yeni bir URL girildiyse ve eski görsel yerelse, eskiyi sil
            if ($current_image_url && strpos($current_image_url, 'uploads/bonuses/') !== false && file_exists($current_image_url)) {
                unlink($current_image_url);
            }
        }
    }


    if (empty($title) || empty($description) || empty($link)) {
        $message = '<div class="alert alert-danger">Başlık, Açıklama ve Link alanları boş bırakılamaz.</div>';
    } elseif (empty($image_url_to_save)) { // Görsel URL'si veya yüklemesi yoksa
         $message = '<div class="alert alert-danger">Lütfen bir görsel yükleyin veya geçerli bir görsel URL\'si girin.</div>';
    } else {
        try {
            $stmt_update = $pdo->prepare("UPDATE bonuses SET title = ?, description = ?, bonus_code = ?, link = ?, image_url = ?, status = ?, sort_order = ? WHERE id = ?");
            if ($stmt_update->execute([$title, $description, $bonus_code, $link, $image_url_to_save, $status, $sort_order, $bonus_id])) {
                $message = '<div class="alert alert-success">Bonus başarıyla güncellendi!</div>';
                // Güncellenmiş verileri tekrar çek
                $stmt = $pdo->prepare("SELECT * FROM bonuses WHERE id = ?");
                $stmt->execute([$bonus_id]);
                $bonus = $stmt->fetch();
            } else {
                $message = '<div class="alert alert-danger">Bonus güncellenirken bir hata oluştu.</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
        }
    }
}

// Kullanıcı silme işlemi (bu sayfadan yapılabilir veya users.php'ye eklenir)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && $bonus_id > 0) { // bonus_id kullanıldı
    try {
        // İlgili bonusun görsel yolunu alıp dosyayı sil
        $stmt_get_image = $pdo->prepare("SELECT image_url FROM bonuses WHERE id = ?");
        $stmt_get_image->execute([$bonus_id]);
        $image_path = $stmt_get_image->fetchColumn();

        $stmt_delete = $pdo->prepare("DELETE FROM bonuses WHERE id = ?"); // bonuses tablosu hedeflendi
        if ($stmt_delete->execute([$bonus_id])) {
            // Dosya sisteminden sil (sadece yerel yüklenmişse)
            if ($image_path && strpos($image_path, 'uploads/bonuses/') !== false && file_exists($image_path)) {
                unlink($image_path);
            }
            $message = '<div class="alert alert-success">Bonus başarıyla silindi!</div>';
            redirect(ADMIN_URL . 'manage_bonuses.php'); // Silme başarılı ise bonus listesine dön
        } else {
            $message = '<div class="alert alert-danger">Bonus silinirken bir hata oluştu.</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Bonus silinirken bir veritabanı hatası oluştu: ' . $e->getMessage() . '</div>';
    }
}

?>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Bonus Düzenle: <?php echo htmlspecialchars($bonus['title'] ?? 'Yeni Bonus'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <?php if ($bonus): ?>
                        <form action="<?php echo ADMIN_URL; ?>edit_bonus.php?id=<?php echo htmlspecialchars($bonus['id']); ?>" method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label class="form-label">Bonus Başlığı</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($bonus['title'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="description" class="form-control" rows="5" required><?php echo htmlspecialchars($bonus['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bonus Kodu (Opsiyonel)</label>
                                <input type="text" name="bonus_code" class="form-control" value="<?php echo htmlspecialchars($bonus['bonus_code'] ?? ''); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Link</label>
                                <input type="url" name="link" class="form-control" value="<?php echo htmlspecialchars($bonus['link'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Görsel Seçimi</label>
                                <?php if (!empty($bonus['image_url'])): ?>
                                    <div class="mb-2">
                                        <strong>Mevcut Görsel:</strong><br>
                                        <img src="<?php echo htmlspecialchars($bonus['image_url']); ?>" alt="Mevcut Bonus Görseli" style="max-width: 150px; height: auto;">
                                        <small class="text-muted ms-2">
                                            <a href="<?php echo htmlspecialchars($bonus['image_url']); ?>" target="_blank">Görseli Görüntüle</a>
                                        </small>
                                    </div>
                                <?php endif; ?>
                                <div class="form-group">
                                    <label class="form-label">Yeni Dosya Yükle</label>
                                    <input type="file" name="image_file" class="form-control" accept="image/*">
                                    <small class="form-hint">Mevcut görseli değiştirmek için JPG, PNG, GIF dosyası (max 5MB).</small>
                                </div>
                                <div class="hr-text my-3">veya</div>
                                <div class="form-group">
                                    <label class="form-label">Yeni Görsel URL'si Gir</label>
                                    <input type="url" name="image_url_text" class="form-control" placeholder="Görsel URL'sini girin (eğer dosya yüklemiyorsanız)">
                                    <small class="form-hint">Eğer dosya yüklemiyorsanız ve URL ile değiştirmek istiyorsanız buraya yeni URL girin.</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Sıralama Önceliği</label>
                                <input type="number" name="sort_order" class="form-control" value="<?php echo htmlspecialchars($bonus['sort_order'] ?? 0); ?>" required min="0">
                                <small class="form-hint">Düşük sayı daha yüksek öncelik anlamına gelir (örneğin, 0 en üstte).</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="status" <?php echo ($bonus['status'] == 1 ? 'checked' : ''); ?>>
                                    <span class="form-check-label">Aktif</span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                            <a href="<?php echo ADMIN_URL; ?>manage_bonuses.php" class="btn btn-secondary ms-2">İptal</a>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>