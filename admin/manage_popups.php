<?php
require_once __DIR__ . '/includes/admin_header.php';

$message = '';

// Fonksiyon: Dosya yükleme ve yol döndürme
// web kök dizinine göreceli yolu döndürür (örn: admin/uploads/popups/resim.png)
function uploadImage($file, $upload_dir_relative_to_webroot) { 
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        $error_code = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        $error_message = "Dosya yüklenmedi veya geçersiz dosya. Hata Kodu: " . $error_code;
        switch ($error_code) {
            case UPLOAD_ERR_INI_SIZE: $error_message .= " (Yüklenen dosya boyutu php.ini'deki upload_max_filesize değerini aşıyor.)"; break;
            case UPLOAD_ERR_FORM_SIZE: $error_message .= " (Yüklenen dosya boyutu HTML formundaki MAX_FILE_SIZE değerini aşıyor.)"; break;
            case UPLOAD_ERR_PARTIAL: $error_message .= " (Dosyanın sadece bir kısmı yüklendi.)"; break;
            case UPLOAD_ERR_NO_FILE: $error_message .= " (Dosya yüklenmedi.)"; break;
            case UPLOAD_ERR_NO_TMP_DIR: $error_message .= " (Geçici klasör eksik.)"; break;
            case UPLOAD_ERR_CANT_WRITE: $error_message .= " (Diske yazma başarısız.)"; break;
            case UPLOAD_ERR_EXTENSION: $error_message .= " (PHP uzantısı dosya yüklemesini durdurdu.)"; break;
        }
        return ['status' => 'error', 'message' => $error_message];
    }

    $base_upload_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $upload_dir_relative_to_webroot;

    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
    $max_file_size = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file_extension, $allowed_extensions)) {
        return ['status' => 'error', 'message' => 'Sadece JPG, JPEG, PNG ve GIF dosyalarına izin verilir.'];
    }
    if ($file['size'] > $max_file_size) {
        return ['status' => 'error', 'message' => 'Dosya boyutu 5MB\'ı geçemez.'];
    }

    $new_file_name = uniqid('popup_') . '.' . $file_extension;
    $target_file_system_path = $base_upload_path . $new_file_name;

    if (!is_dir($base_upload_path)) {
        mkdir($base_upload_path, 0755, true);
    }

    if (move_uploaded_file($file['tmp_name'], $target_file_system_path)) {
        return ['status' => 'success', 'file_path' => $upload_dir_relative_to_webroot . $new_file_name];
    } else {
        return ['status' => 'error', 'message' => 'Dosya taşıma başarısız oldu. Yükleme klasörü izinlerini kontrol edin.'];
    }
}


// Popup ekleme/düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST' && (isset($_POST['add_popup']) || isset($_POST['edit_popup']))) {
    $popup_id = isset($_POST['popup_id']) ? (int)$_POST['popup_id'] : 0;
    $title = trim($_POST['title'] ?? '');
    $link = trim($_POST['link'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $type = trim($_POST['type'] ?? 'image');
    $display_order = (int)($_POST['display_order'] ?? 0);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    $image_url_to_save = '';
    $current_image_url_db = ''; 

    if ($popup_id > 0) {
        $stmt_get_current_image = $pdo->prepare("SELECT image_url FROM popups WHERE id = ?");
        $stmt_get_current_image->execute([$popup_id]);
        $current_image_url_db = $stmt_get_current_image->fetchColumn();
        $image_url_to_save = $current_image_url_db;
    }

    if (isset($_FILES['image_file']) && $_FILES['image_file']['name'] != '') {
        // YÜKLEME DİZİNİ BURADA GÜNCELLENDİ: 'admin/uploads/popups/'
        $upload_result = uploadImage($_FILES['image_file'], 'admin/uploads/popups/'); 
        if ($upload_result['status'] == 'success') {
            $image_url_to_save = $upload_result['file_path'];
            // Eski görseli sil (sadece yerel yüklenmişse)
            if ($current_image_url_db && strpos($current_image_url_db, 'admin/uploads/popups/') !== false) {
                 $full_old_path_on_server = $_SERVER['DOCUMENT_ROOT'] . '/' . $current_image_url_db;
                 if (file_exists($full_old_path_on_server)) {
                     unlink($full_old_path_on_server);
                 }
            }
        } else {
            $message = '<div class="alert alert-danger">Görsel yüklenirken hata: ' . $upload_result['message'] . '</div>';
        }
    } elseif (!empty($_POST['image_url_text'])) {
        $image_url_to_save = trim($_POST['image_url_text']);
        if (!filter_var($image_url_to_save, FILTER_VALIDATE_URL)) {
             $message = '<div class="alert alert-danger">Geçersiz görsel URL formatı.</div>';
             $image_url_to_save = $current_image_url_db;
        } else {
            if ($current_image_url_db && strpos($current_image_url_db, 'admin/uploads/popups/') !== false) {
                 $full_old_path_on_server = $_SERVER['DOCUMENT_ROOT'] . '/' . $current_image_url_db;
                 if (file_exists($full_old_path_on_server)) {
                     unlink($full_old_path_on_server);
                 }
            }
        }
    }

    if (empty($title)) {
        $message = '<div class="alert alert-danger">Başlık boş bırakılamaz.</div>';
    } elseif ($type == 'image' && empty($image_url_to_save)) {
        $message = '<div class="alert alert-danger">Görsel popup için görsel yüklemeli veya URL girmelisiniz.</div>';
    } elseif ($type == 'text' && empty($content)) {
         $message = '<div class="alert alert-danger">Metin popup için içerik boş bırakılamaz.</div>';
    } else {
        try {
            if ($popup_id > 0) {
                $stmt = $pdo->prepare("UPDATE popups SET title = ?, image_url = ?, link = ?, content = ?, type = ?, display_order = ?, is_active = ? WHERE id = ?");
                if ($stmt->execute([$title, $image_url_to_save, $link, $content, $type, $display_order, $is_active, $popup_id])) {
                    $message = '<div class="alert alert-success">Popup başarıyla güncellendi!</div>';
                } else {
                    $error_info = $stmt->errorInfo();
                    $message = '<div class="alert alert-danger">Popup güncellenirken hata: ' . ($error_info[2] ?? 'Bilinmeyen SQL hatası.') . '</div>';
                }
            } else {
                $stmt = $pdo->prepare("INSERT INTO popups (title, image_url, link, content, type, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
                if ($stmt->execute([$title, $image_url_to_save, $link, $content, $type, $display_order, $is_active])) {
                    $message = '<div class="alert alert-success">Popup başarıyla eklendi!</div>';
                } else {
                    $error_info = $stmt->errorInfo();
                    $message = '<div class="alert alert-danger">Popup eklenirken hata: ' . ($error_info[2] ?? 'Bilinmeyen SQL hatası.') . '</div>';
                }
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
        }
    }
}

// Popup silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $popup_id = (int)$_GET['id'];
    try {
        $stmt_get_image = $pdo->prepare("SELECT image_url FROM popups WHERE id = ?");
        $stmt_get_image->execute([$popup_id]);
        $image_path_db = $stmt_get_image->fetchColumn(); 

        $stmt_delete = $pdo->prepare("DELETE FROM popups WHERE id = ?");
        if ($stmt_delete->execute([$popup_id])) {
            if ($image_path_db && strpos($image_path_db, 'admin/uploads/popups/') !== false) { // admin/uploads/popups/ yolunu kontrol et
                $full_file_path = $_SERVER['DOCUMENT_ROOT'] . '/' . $image_path_db;
                if (file_exists($full_file_path)) {
                    unlink($full_file_path);
                }
            }
            $message = '<div class="alert alert-success">Popup başarıyla silindi!</div>';
        } else {
            $message = '<div class="alert alert-danger">Popup silinirken hata oluştu.</div>';
        }
    } catch (PDOException $e) {
        $message = '<div class="alert alert-danger">Veritabanı hatası: ' . $e->getMessage() . '</div>';
    }
}


// Popup'ları çek
$stmt_popups = $pdo->query("SELECT * FROM popups ORDER BY display_order ASC, id DESC");
$popups = $stmt_popups->fetchAll();
?>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Popup Yönetimi</h3>
                        <div class="card-actions">
                            <a href="#" class="btn btn-primary d-none d-sm-inline-block" data-bs-toggle="modal" data-bs-target="#modal-new-popup">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                                Yeni Popup Ekle
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <div class="table-responsive">
                            <table class="table card-table table-vcenter text-nowrap datatable">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Başlık</th>
                                    <th>Tip</th>
                                    <th>Görsel/İçerik</th>
                                    <th>Link</th>
                                    <th>Sıra</th>
                                    <th>Aktif</th>
                                    <th>Oluşturulma</th>
                                    <th>İşlemler</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php if (!empty($popups)): ?>
                                    <?php foreach ($popups as $popup): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($popup['id']); ?></td>
                                            <td><?php echo htmlspecialchars($popup['title'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($popup['type'] ?? '')); ?></td>
                                            <td>
                                                <?php if ($popup['type'] == 'image' && !empty($popup['image_url'])): ?>
                                                    <?php
                                                    // Popup görsel yolunu oluşturma (BASE_URL + web kök dizinine göreceli yol)
                                                    $popup_image_src_display = htmlspecialchars($popup['image_url'] ?? '');
                                                    if (!empty($popup_image_src_display) && strpos($popup_image_src_display, 'http') !== 0) {
                                                        $popup_image_src_display = BASE_URL . $popup_image_src_display; 
                                                    }
                                                    ?>
                                                    <img src="<?php echo $popup_image_src_display; ?>" alt="Popup Görseli" style="max-width: 50px; max-height: 50px;">
                                                <?php elseif ($popup['type'] == 'text' && !empty($popup['content'])): ?>
                                                    <?php echo htmlspecialchars(substr($popup['content'], 0, 50)) . (strlen($popup['content']) > 50 ? '...' : ''); ?>
                                                <?php else: ?>
                                                    -
                                                <?php endif; ?>
                                            </td>
                                            <td><a href="<?php echo htmlspecialchars($popup['link'] ?? ''); ?>" target="_blank"><?php echo !empty($popup['link']) ? 'Link' : '-'; ?></a></td>
                                            <td><?php echo htmlspecialchars($popup['display_order']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo ($popup['is_active'] == 1 ? 'success' : 'danger'); ?> text-<?php echo ($popup['is_active'] == 1 ? 'dark' : 'white'); ?>">
                                                    <?php echo ($popup['is_active'] == 1 ? 'Aktif' : 'Pasif'); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d.m.Y H:i', strtotime($popup['created_at'])); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-icon btn-primary edit-popup-btn" data-bs-toggle="modal" data-bs-target="#modal-edit-popup" data-id="<?php echo htmlspecialchars($popup['id']); ?>" title="Düzenle">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.167a.784 .784 0 0 0 1.07 -.139l.75 -1.094a.5 .5 0 0 1 .74 -.093l.974 .975a.5 .5 0 0 1 .093 .74l-1.094 .75a.784 .784 0 0 0 -.139 1.07l.385 .925a.5 .5 0 0 1 -.238 .622l-1.094 .557a.784 .784 0 0 0 -.972 .594l-.385 1.026a.5 .5 0 0 1 -.622 .238l-.557 -1.094a.784 .784 0 0 0 -1.07 -.139l-.925 .385a.5 .5 0 0 1 -.622 -.238l-.557 -1.094a.784 .784 0 0 0 -.139 -1.07l1.094 -.75a.784 .784 0 0 0 .139 -1.07l-.385 -.925a.5 .5 0 0 1 .238 -.622l1.094 -.557a.784 .784 0 0 0 .972 -.594l.385 -1.026a.5 .5 0 0 1 .622 -.238l.557 1.094z"/></svg>
                                                </a>
                                                <a href="<?php echo ADMIN_URL; ?>manage_popups.php?action=delete&id=<?php echo htmlspecialchars($popup['id']); ?>" class="btn btn-sm btn-icon btn-danger" onclick="return confirm('Bu popup\'ı silmek istediğinizden emin misiniz?');" title="Sil">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center">Henüz kayıtlı popup bulunmamaktadır.</td>
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

<div class="modal modal-blur fade" id="modal-new-popup" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Yeni Popup Ekle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo ADMIN_URL; ?>manage_popups.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Popup Başlığı</label>
                        <input type="text" name="title" class="form-control" placeholder="Popup başlığını girin" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Popup Tipi</label>
                        <select name="type" id="popup_type_new" class="form-select">
                            <option value="image">Görsel Popup</option>
                            <option value="text">Metin Popup</option>
                        </select>
                    </div>

                    <div id="image_fields_new">
                        <div class="mb-3">
                            <label class="form-label">Görsel Dosyası Yükle</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*">
                            <small class="form-hint">JPG, PNG, GIF dosyaları (max 5MB).</small>
                        </div>
                        <div class="hr-text my-3">veya</div>
                        <div class="mb-3">
                            <label class="form-label">Görsel URL'si Gir</label>
                            <input type="url" name="image_url_text" class="form-control" placeholder="Harici görsel URL'si">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link (Görsele Tıklanınca Gidilecek)</label>
                            <input type="url" name="link" class="form-control" placeholder="Popup linki (isteğe bağlı)">
                        </div>
                    </div>

                    <div id="text_fields_new" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Popup İçeriği (HTML destekli)</label>
                            <textarea name="content" class="form-control" rows="5" placeholder="Popup metin içeriğini girin"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link (Metne Tıklanınca Gidilecek)</label>
                            <input type="url" name="link" class="form-control" placeholder="Popup linki (isteğe bağlı)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gösterim Sırası</label>
                        <input type="number" name="display_order" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" checked>
                            <span class="form-check-label">Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" name="add_popup" class="btn btn-primary">Popup Ekle</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal modal-blur fade" id="modal-edit-popup" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Popup Düzenle</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="<?php echo ADMIN_URL; ?>manage_popups.php" method="POST" enctype="multipart/form-data" id="edit-popup-form">
                <input type="hidden" name="popup_id" id="edit_popup_id">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Popup Başlığı</label>
                        <input type="text" name="title" id="edit_popup_title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Popup Tipi</label>
                        <select name="type" id="popup_type_edit" class="form-select">
                            <option value="image">Görsel Popup</option>
                            <option value="text">Metin Popup</option>
                        </select>
                    </div>

                    <div id="image_fields_edit">
                        <div class="mb-3" id="current_image_container">
                            <strong>Mevcut Görsel:</strong><br>
                            <img src="" id="current_popup_image" alt="Mevcut Görsel" style="max-width: 150px; height: auto;">
                            <small class="text-muted ms-2"><a href="" id="current_popup_image_link" target="_blank">Görseli Görüntüle</a></small>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Yeni Görsel Dosyası Yükle</label>
                            <input type="file" name="image_file" class="form-control" accept="image/*">
                            <small class="form-hint">Mevcut görseli değiştirmek için JPG, PNG, GIF dosyası (max 5MB).</small>
                        </div>
                        <div class="hr-text my-3">veya</div>
                        <div class="mb-3">
                            <label class="form-label">Yeni Görsel URL'si Gir</label>
                            <input type="url" name="image_url_text" id="edit_image_url_text" class="form-control" placeholder="Harici görsel URL'si">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link (Görsele Tıklanınca Gidilecek)</label>
                            <input type="url" name="link" id="edit_image_link" class="form-control" placeholder="Popup linki (isteğe bağlı)">
                        </div>
                    </div>

                    <div id="text_fields_edit" style="display: none;">
                        <div class="mb-3">
                            <label class="form-label">Popup İçeriği (HTML destekli)</label>
                            <textarea name="content" id="edit_popup_content" class="form-control" rows="5"></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Link (Metne Tıklanınca Gidilecek)</label>
                            <input type="url" name="link" id="edit_text_link" class="form-control" placeholder="Popup linki (isteğe bağlı)">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Gösterim Sırası</label>
                        <input type="number" name="display_order" id="edit_display_order" class="form-control" value="0" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" id="edit_is_active">
                            <span class="form-check-label">Aktif</span>
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Kapat</button>
                    <button type="submit" name="edit_popup" class="btn btn-primary">Kaydet</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>

<script>
document.addEventListener("DOMContentLoaded", function() {
    // Popup tipi seçimi değiştiğinde ilgili alanları göster/gizle
    function togglePopupFields(typeSelectId, imageFieldsId, textFieldsId) {
        const typeSelect = document.getElementById(typeSelectId);
        const imageFields = document.getElementById(imageFieldsId);
        const textFields = document.getElementById(textFieldsId);

        if (!typeSelect || !imageFields || !textFields) return;

        function updateVisibility() {
            if (typeSelect.value === 'image') {
                imageFields.style.display = 'block';
                textFields.style.display = 'none';
            } else {
                imageFields.style.display = 'none';
                textFields.style.display = 'block';
            }
        }
        typeSelect.addEventListener('change', updateVisibility);
        updateVisibility();
    }

    // Yeni popup modali için
    togglePopupFields('popup_type_new', 'image_fields_new', 'text_fields_new');
    // Düzenleme popup modali için
    togglePopupFields('popup_type_edit', 'image_fields_edit', 'text_fields_edit');

    // Düzenle butonuna tıklandığında popup verilerini modal'a yükle
    document.querySelectorAll('.edit-popup-btn').forEach(button => {
        button.addEventListener('click', function() {
            const popupId = this.dataset.id;
            
            fetch(`<?php echo ADMIN_URL; ?>manage_popups.php?action=get_popup&id=${popupId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'success' && data.popup) {
                        const popup = data.popup;
                        document.getElementById('edit_popup_id').value = popup.id;
                        document.getElementById('edit_popup_title').value = popup.title;
                        document.getElementById('popup_type_edit').value = popup.type;
                        document.getElementById('edit_display_order').value = popup.display_order;
                        document.getElementById('edit_is_active').checked = (popup.is_active == 1); // BOOLEAN için

                        // Görsel alanları
                        if (popup.type === 'image') {
                            const imageUrl = popup.image_url;
                            let fullImageUrl = '';
                            if (imageUrl && imageUrl.startsWith('http')) {
                                fullImageUrl = imageUrl;
                            } else if (imageUrl) {
                                // BASE_URL + admin/uploads/popups/path şeklinde oluştur
                                fullImageUrl = '<?php echo BASE_URL; ?>' + imageUrl; 
                            }
                            document.getElementById('current_popup_image').src = fullImageUrl || 'https://via.placeholder.com/150x150?text=NoImg';
                            document.getElementById('current_popup_image_link').href = fullImageUrl || '#';
                            // URL alanına mevcut URL'yi yaz, ancak yerel ise boş bırak
                            document.getElementById('edit_image_url_text').value = imageUrl && !imageUrl.startsWith('http') ? '' : imageUrl; 
                            document.getElementById('edit_image_link').value = popup.link || '';
                        } else {
                            document.getElementById('current_image_container').style.display = 'none'; // Görsel konteynerini gizle
                        }
                        
                        // Metin alanları
                        document.getElementById('edit_popup_content').value = popup.content || '';
                        document.getElementById('edit_text_link').value = popup.link || '';

                        // Popup tipi seçimiyle alanların görünürlüğünü tekrar ayarla
                        togglePopupFields('popup_type_edit', 'image_fields_edit', 'text_fields_edit');

                    } else {
                        console.error('Popup verileri çekilemedi:', data.message || 'Bilinmeyen hata');
                    }
                })
                .catch(error => {
                    console.error('Popup verilerini AJAX ile çekerken hata:', error);
                });
        });
    });
});
</script>