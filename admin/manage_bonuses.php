<?php
require_once __DIR__ . '/includes/admin_header.php';

$message = '';

// Fonksiyon: Dosya yükleme ve yol döndürme
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


// Bonus silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $bonus_id = (int)$_GET['id'];
    try {
        // İlgili bonusun görsel yolunu alıp dosyayı sil
        $stmt_get_image = $pdo->prepare("SELECT image_url FROM bonuses WHERE id = ?");
        $stmt_get_image->execute([$bonus_id]);
        $image_path = $stmt_get_image->fetchColumn();

        $stmt_delete = $pdo->prepare("DELETE FROM bonuses WHERE id = ?");
        if ($stmt_delete->execute([$bonus_id])) {
            // Dosya sisteminden sil (sadece yerel yüklenmişse)
            if ($image_path && strpos($image_path, 'uploads/bonuses/') !== false && file_exists($image_path)) {
                unlink($image_path);
            }
            $message = '<div class="alert alert-success">Bonus başarıyla silindi!</div>';
        } else {
            $message = '<div class="alert alert-danger">Bonus silinirken bir hata oluştu.</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
    }
}

// Bonus sıralamasını AJAX ile güncelleme (sürükle-bırak için)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'update_order') {
    $order_data = json_decode($_POST['order'], true);
    
    if (is_array($order_data)) {
        $pdo->beginTransaction();
        try {
            foreach ($order_data as $index => $bonus_id) {
                $stmt_update = $pdo->prepare("UPDATE bonuses SET sort_order = ? WHERE id = ?");
                $stmt_update->execute([$index, $bonus_id]);
            }
            $pdo->commit();
            echo json_encode(['status' => 'success', 'message' => 'Sıralama başarıyla güncellendi.']);
        } catch (PDOException $e) {
            $pdo->rollBack();
            echo json_encode(['status' => 'error', 'message' => 'Sıralama güncellenirken veritabanı hatası: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Geçersiz sıralama verisi.']);
    }
    exit();
}

// Bonus ekleme işlemi (Yeni Bonus Ekle modalından gelen POST isteği)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_bonus'])) {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $bonus_code = trim($_POST['bonus_code'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $image_url = ''; // Başlangıçta boş, eğer dosya yüklenirse değişecek
    $status = isset($_POST['status']) ? 1 : 0;
    
    // Yeni bonus için en büyük sort_order'ı bulup 1 artırarak varsayılan değer ver
    $stmt_max_sort_order = $pdo->query("SELECT MAX(sort_order) FROM bonuses");
    $max_sort_order = $stmt_max_sort_order->fetchColumn();
    $sort_order = ($max_sort_order !== null) ? ($max_sort_order + 1) : 0;

    // Dosya yükleme kontrolü
    if (isset($_FILES['image_file']) && $_FILES['image_file']['error'] == UPLOAD_ERR_OK) {
        $upload_result = uploadImage($_FILES['image_file'], 'uploads/bonuses/');
        if ($upload_result['status'] == 'success') {
            $image_url = $upload_result['file_path']; // Yüklenen dosyanın yolunu al
        } else {
            $message = '<div class="alert alert-danger">Görsel yüklenirken hata: ' . $upload_result['message'] . '</div>';
        }
    } elseif (!empty($_POST['image_url_text'])) { // Eğer dosya yüklenmediyse ve URL girildiyse
        $image_url = trim($_POST['image_url_text']);
        if (!filter_var($image_url, FILTER_VALIDATE_URL)) {
             $message = '<div class="alert alert-danger">Geçersiz görsel URL formatı.</div>';
             $image_url = ''; // Geçersizse sıfırla
        }
    }


    if (empty($title) || empty($description) || empty($link)) {
        $message = '<div class="alert alert-danger">Başlık, Açıklama ve Link alanları boş bırakılamaz.</div>';
    } elseif (empty($image_url)) { // Görsel URL'si veya yüklemesi yoksa
         $message = '<div class="alert alert-danger">Lütfen bir görsel yükleyin veya geçerli bir görsel URL\'si girin.</div>';
    } else {
        try {
            $stmt_insert = $pdo->prepare("INSERT INTO bonuses (title, description, bonus_code, link, image_url, status, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt_insert->execute([$title, $description, $bonus_code, $link, $image_url, $status, $sort_order])) {
                $message = '<div class="alert alert-success">Bonus başarıyla eklendi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Bonus eklenirken bir hata oluştu.</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
        }
    }
}


// Bonusları çek (sıralama önceliğine göre ve ardından ID'ye göre)
$stmt_bonuses = $pdo->query("SELECT * FROM bonuses ORDER BY sort_order ASC, id DESC");
$bonuses = $stmt_bonuses->fetchAll();
?>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Bonusları Yönet</h3>
                        <div class="card-actions">
                            <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-new-bonus">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                                Yeni Bonus Ekle
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <p class="text-muted">Bonusları sürükleyip bırakarak sıralayabilirsiniz.</p>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Başlık</th>
                                    <th>Kodu</th>
                                    <th>Link</th>
                                    <th>Görsel</th>
                                    <th>Durum</th>
                                    <th>Oluşturulma</th>
                                    <th>İşlemler</th>
                                </tr>
                                </thead>
                                <tbody id="bonuses-sortable">
                                <?php if (!empty($bonuses)): ?>
                                    <?php foreach ($bonuses as $bonus): ?>
                                        <tr data-id="<?php echo htmlspecialchars($bonus['id']); ?>">
                                            <td><?php echo htmlspecialchars($bonus['id']); ?></td>
                                            <td><?php echo htmlspecialchars($bonus['title'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($bonus['bonus_code'] ?? ''); ?></td>
                                            <td><a href="<?php echo htmlspecialchars($bonus['link'] ?? ''); ?>" target="_blank">Link</a></td>
                                            <td>
                                                <?php if (!empty($bonus['image_url'])): ?>
                                                    <img src="<?php echo htmlspecialchars($bonus['image_url'] ?? ''); ?>" alt="Bonus Görseli" style="max-width: 50px; max-height: 50px;">
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo ($bonus['status'] == 1 ? 'success' : 'danger'); ?> text-<?php echo ($bonus['status'] == 1 ? 'dark' : 'white'); ?>">
                                                    <?php echo ($bonus['status'] == 1 ? 'Aktif' : 'Pasif'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($bonus['created_at'])); ?></td>
                                            <td>
                                                <a href="<?php echo ADMIN_URL; ?>edit_bonus.php?id=<?php echo htmlspecialchars($bonus['id']); ?>" class="btn btn-sm btn-icon btn-primary" title="Düzenle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.167a.784 .784 0 0 0 1.07 -.139l.75 -1.094a.5 .5 0 0 1 .74 -.093l.974 .975a.5 .5 0 0 1 .093 .74l-1.094 .75a.784 .784 0 0 0 -.139 1.07l.385 .925a.5 .5 0 0 1 -.238 .622l-1.094 .557a.784 .784 0 0 0 -.972 .594l-.385 1.026a.5 .5 0 0 1 -.622 .238l-.557 -1.094a.784 .784 0 0 0 -1.07 -.139l-.925 .385a.5 .5 0 0 1 -.622 -.238l-.557 -1.094a.784 .784 0 0 0 -.139 -1.07l1.094 -.75a.784 .784 0 0 0 .139 -1.07l-.385 -.925a.5 .5 0 0 1 .238 -.622l1.094 -.557a.784 .784 0 0 0 .972 -.594l.385 -1.026a.5 .5 0 0 1 .622 -.238l.557 1.094z"/></svg>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>manage_bonuses.php?action=delete&id=<?php echo htmlspecialchars($bonus['id']); ?>" class="btn btn-sm btn-icon btn-danger" onclick="return confirm('Bu bonusu silmek istediğinizden emin misiniz?');" title="Sil">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center">Henüz kayıtlı bonus bulunmamaktadır.</td>
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

<div class="modal modal-blur fade" id="modal-new-bonus" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Bonus Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo ADMIN_URL; ?>manage_bonuses.php" method="POST" enctype="multipart/form-data"> 
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Bonus Başlığı</label>
                        <input type="text" name="title" class="form-control" placeholder="Bonus başlığını girin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Açıklama</label>
                        <textarea name="description" class="form-control" rows="5" placeholder="Bonus açıklamasını girin" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Bonus Kodu (Opsiyonel)</label>
                        <input type="text" name="bonus_code" class="form-control" placeholder="Varsa bonus kodunu girin">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Link</label>
                        <input type="url" name="link" class="form-control" placeholder="Bonus linkini girin (örn: https://example.com/bonus)" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Görsel Seçimi</label>
                        <div class="form-group">
                            <label class="form-label">Dosya Yükle</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*">
                            <small class="form-hint">JPG, PNG, GIF dosyaları (max 5MB).</small>
                        </div>
                        <div class="hr-text my-3">veya</div>
                        <div class="form-group">
                            <label class="form-label">Görsel URL'si Gir</label>
                            <input type="url" name="image_url_text" class="form-control" placeholder="Görsel URL'sini girin (örn: https://example.com/resim.png)">
                            <small class="form-hint">Eğer dosya yüklemiyorsanız, bir URL girebilirsiniz.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Sıralama Önceliği</label>
                        <input type="number" name="sort_order" class="form-control" value="0" min="0" required>
                        <small class="form-hint">Düşük sayı daha yüksek öncelik anlamına gelir (örneğin, 0 en üstte).</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="status" checked>
                            <span class="form-check-label">Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" name="add_bonus" class="btn btn-primary">Bonus Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    var el = document.getElementById('bonuses-sortable');
    if (el) {
        var sortable = new Sortable(el, {
            animation: 150,
            ghostClass: 'table-row-ghost', // Tabler'a uygun bir sınıf
            onEnd: function (evt) {
                var order = [];
                el.querySelectorAll('tr').forEach(function(row) {
                    order.push(row.dataset.id);
                });

                fetch('<?php echo ADMIN_URL; ?>manage_bonuses.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=update_order&order=' + JSON.stringify(order)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success') {
                        console.log(data.message);
                        location.reload(); // Sıralama güncellendikten sonra sayfayı yenileyelim
                    } else {
                        console.error(data.message);
                    }
                })
                .catch(error => {
                    console.error('Sıralama güncellenirken bir hata oluştu:', error);
                });
            }
        });
    }
});
</script>