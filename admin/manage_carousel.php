<?php
require_once __DIR__ . '/includes/admin_header.php';

$message = '';

// Carousel slayt ekleme/düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $image_url = trim($_POST['image_url']);
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $order_num = (int)$_POST['order_num'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $slide_id = isset($_POST['slide_id']) ? (int)$_POST['slide_id'] : 0;

    if (empty($image_url) || empty($title)) {
        $message = '<div class="alert alert-danger">Görsel URL\'si ve Başlık zorunludur.</div>';
    } else {
        if ($slide_id > 0) {
            // Düzenleme
            $stmt = $pdo->prepare("UPDATE carousel_slides SET image_url = ?, title = ?, description = ?, order_num = ?, is_active = ? WHERE id = ?");
            if ($stmt->execute([$image_url, $title, $description, $order_num, $is_active, $slide_id])) {
                $message = '<div class="alert alert-success">Slayt başarıyla güncellendi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Slayt güncellenirken bir hata oluştu.</div>';
            }
        } else {
            // Ekleme
            $stmt = $pdo->prepare("INSERT INTO carousel_slides (image_url, title, description, order_num, is_active) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$image_url, $title, $description, $order_num, $is_active])) {
                $message = '<div class="alert alert-success">Slayt başarıyla eklendi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Slayt eklenirken bir hata oluştu.</div>';
            }
        }
    }
}

// Carousel slayt silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $slide_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM carousel_slides WHERE id = ?");
    if ($stmt->execute([$slide_id])) {
        $message = '<div class="alert alert-success">Slayt başarıyla silindi!</div>';
    } else {
        $message = '<div class="alert alert-danger">Slayt silinirken bir hata oluştu.</div>';
    }
    redirect(ADMIN_URL . 'manage_carousel.php');
}

// Carousel slaytlarını listele
$stmt_slides = $pdo->query("SELECT * FROM carousel_slides ORDER BY order_num ASC, id DESC");
$slides = $stmt_slides->fetchAll();

// Düzenlenecek slaytı getir
$edit_slide = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $slide_id = (int)$_GET['id'];
    $stmt_edit = $pdo->prepare("SELECT * FROM carousel_slides WHERE id = ?");
    $stmt_edit->execute([$slide_id]);
    $edit_slide = $stmt_edit->fetch();
}
?>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo ($edit_slide ? 'Slayt Düzenle' : 'Yeni Slayt Ekle'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <form action="<?php echo ADMIN_URL; ?>manage_carousel.php" method="POST">
                            <?php if ($edit_slide): ?>
                                <input type="hidden" name="slide_id" value="<?php echo htmlspecialchars($edit_slide['id']); ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Görsel URL</label>
                                <input type="url" name="image_url" class="form-control" value="<?php echo htmlspecialchars($edit_slide['image_url'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Başlık</label>
                                <input type="text" name="title" class="form-control" value="<?php echo htmlspecialchars($edit_slide['title'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Açıklama</label>
                                <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($edit_slide['description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Sıralama Numarası</label>
                                <input type="number" name="order_num" class="form-control" value="<?php echo htmlspecialchars($edit_slide['order_num'] ?? 0); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" <?php echo (isset($edit_slide['is_active']) && $edit_slide['is_active'] == 1) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Aktif</span>
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary"><?php echo ($edit_slide ? 'Slaytı Güncelle' : 'Slayt Ekle'); ?></button>
                            <?php if ($edit_slide): ?>
                                <a href="<?php echo ADMIN_URL; ?>manage_carousel.php" class="btn btn-secondary ms-2">İptal</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Mevcut Carousel Slaytları</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Görsel</th>
                                <th>Başlık</th>
                                <th>Açıklama</th>
                                <th>Sıra</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($slides)): ?>
                                <?php foreach ($slides as $slide): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($slide['id']); ?></td>
                                        <td><img src="<?php echo htmlspecialchars($slide['image_url']); ?>" alt="Görsel" style="width: 80px; height: auto;"></td>
                                        <td><?php echo htmlspecialchars($slide['title']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($slide['description'], 0, 50)) . (strlen($slide['description']) > 50 ? '...' : ''); ?></td>
                                        <td><?php echo htmlspecialchars($slide['order_num']); ?></td>
                                        <td><span class="badge bg-<?php echo ($slide['is_active'] ? 'green' : 'red'); ?>"><?php echo ($slide['is_active'] ? 'Aktif' : 'Pasif'); ?></span></td>
                                        <td>
                                            <a href="<?php echo ADMIN_URL; ?>manage_carousel.php?action=edit&id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-icon btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.167a.784 .784 0 0 0 1.07 -.139l.75 -1.094a.5 .5 0 0 1 .74 -.093l.974 .975a.5 .5 0 0 1 .093 .74l-1.094 .75a.784 .784 0 0 0 -.139 1.07l.385 .925a.5 .5 0 0 1 -.238 .622l-1.094 .557a.784 .784 0 0 0 -.972 .594l-.385 1.026a.5 .5 0 0 1 -.622 .238l-.557 -1.094a.784 .784 0 0 0 -1.07 -.139l-.925 .385a.5 .5 0 0 1 -.622 -.238l-.557 -1.094a.784 .784 0 0 0 -.139 -1.07l1.094 -.75a.784 .784 0 0 0 .139 -1.07l-.385 -.925a.5 .5 0 0 1 .238 -.622l1.094 -.557a.784 .784 0 0 0 .972 -.594l.385 -1.026a.5 .5 0 0 1 .622 -.238l-.557 1.094z"/></svg>
                                            </a>
                                            <a href="<?php echo ADMIN_URL; ?>manage_carousel.php?action=delete&id=<?php echo $slide['id']; ?>" class="btn btn-sm btn-icon btn-danger" onclick="return confirm('Bu slaytı silmek istediğinizden emin misiniz?');">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center">Henüz eklenmiş slayt bulunmamaktadır.</td>
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
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>