<?php
require_once __DIR__ . '/includes/admin_header.php';

$message = '';

// Bonus ekleme/düzenleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $logo_url = trim($_POST['logo_url']);
    $badge = trim($_POST['badge']);
    $amount = trim($_POST['amount']);
    $link = trim($_POST['link']);
    $category = trim($_POST['category']);
    $status = $_POST['status'];
    $bonus_id = isset($_POST['bonus_id']) ? (int)$_POST['bonus_id'] : 0;

    if (empty($name) || empty($logo_url) || empty($amount) || empty($link) || empty($category)) {
        $message = '<div class="alert alert-danger">Lütfen tüm zorunlu alanları doldurun.</div>';
    } else {
        if ($bonus_id > 0) {
            // Düzenleme
            $stmt = $pdo->prepare("UPDATE bonuses SET name = ?, logo_url = ?, badge = ?, amount = ?, link = ?, category = ?, status = ? WHERE id = ?");
            if ($stmt->execute([$name, $logo_url, $badge, $amount, $link, $category, $status, $bonus_id])) {
                $message = '<div class="alert alert-success">Bonus başarıyla güncellendi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Bonus güncellenirken bir hata oluştu.</div>';
            }
        } else {
            // Ekleme
            $stmt = $pdo->prepare("INSERT INTO bonuses (name, logo_url, badge, amount, link, category, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt->execute([$name, $logo_url, $badge, $amount, $link, $category, $status])) {
                $message = '<div class="alert alert-success">Bonus başarıyla eklendi!</div>';
            } else {
                $message = '<div class="alert alert-danger">Bonus eklenirken bir hata oluştu.</div>';
            }
        }
    }
}

// Bonus silme işlemi
if (isset($_GET['action']) && $_GET['action'] == 'delete' && isset($_GET['id'])) {
    $bonus_id = (int)$_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM bonuses WHERE id = ?");
    if ($stmt->execute([$bonus_id])) {
        $message = '<div class="alert alert-success">Bonus başarıyla silindi!</div>';
    } else {
        $message = '<div class="alert alert-danger">Bonus silinirken bir hata oluştu.</div>';
    }
    // Silme sonrası URL'den parametreleri temizle
    redirect(ADMIN_URL . 'manage_bonuses.php');
}

// Bonusları listele
$stmt_bonuses = $pdo->query("SELECT * FROM bonuses ORDER BY id DESC");
$bonuses = $stmt_bonuses->fetchAll();

// Düzenlenecek bonusu getir (eğer edit modundaysa)
$edit_bonus = null;
if (isset($_GET['action']) && $_GET['action'] == 'edit' && isset($_GET['id'])) {
    $bonus_id = (int)$_GET['id'];
    $stmt_edit = $pdo->prepare("SELECT * FROM bonuses WHERE id = ?");
    $stmt_edit->execute([$bonus_id]);
    $edit_bonus = $stmt_edit->fetch();
}
?>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title"><?php echo ($edit_bonus ? 'Bonus Düzenle' : 'Yeni Bonus Ekle'); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <form action="<?php echo ADMIN_URL; ?>manage_bonuses.php" method="POST">
                            <?php if ($edit_bonus): ?>
                                <input type="hidden" name="bonus_id" value="<?php echo htmlspecialchars($edit_bonus['id']); ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label class="form-label">Bonus Adı</label>
                                <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($edit_bonus['name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Logo URL</label>
                                <input type="url" name="logo_url" class="form-control" value="<?php echo htmlspecialchars($edit_bonus['logo_url'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rozet (Badge)</label>
                                <input type="text" name="badge" class="form-control" value="<?php echo htmlspecialchars($edit_bonus['badge'] ?? ''); ?>" placeholder="Örn: 💸 VIP Deneme Bonusu">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Bonus Miktarı/Açıklaması</label>
                                <input type="text" name="amount" class="form-control" value="<?php echo htmlspecialchars($edit_bonus['amount'] ?? ''); ?>" required placeholder="Örn: 1500₺ Deneme Bonusu">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yönlendirme Linki</label>
                                <input type="url" name="link" class="form-control" value="<?php echo htmlspecialchars($edit_bonus['link'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Kategori (Filtreleme için)</label>
                                <input type="text" name="category" class="form-control" value="<?php echo htmlspecialchars($edit_bonus['category'] ?? ''); ?>" required placeholder="Örn: vip, good, new (Küçük harfle yazın)">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Durum</label>
                                <select name="status" class="form-select">
                                    <option value="active" <?php echo (isset($edit_bonus['status']) && $edit_bonus['status'] == 'active') ? 'selected' : ''; ?>>Aktif</option>
                                    <option value="inactive" <?php echo (isset($edit_bonus['status']) && $edit_bonus['status'] == 'inactive') ? 'selected' : ''; ?>>Pasif</option>
                                </select>
                            </div>
                            <button type="submit" class="btn btn-primary"><?php echo ($edit_bonus ? 'Bonusu Güncelle' : 'Bonus Ekle'); ?></button>
                            <?php if ($edit_bonus): ?>
                                <a href="<?php echo ADMIN_URL; ?>manage_bonuses.php" class="btn btn-secondary ms-2">İptal</a>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-12 mt-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Mevcut Bonuslar</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Logo</th>
                                <th>Adı</th>
                                <th>Miktar</th>
                                <th>Rozet</th>
                                <th>Kategori</th>
                                <th>Durum</th>
                                <th>İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($bonuses)): ?>
                                <?php foreach ($bonuses as $bonus): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($bonus['id']); ?></td>
                                        <td><img src="<?php echo htmlspecialchars($bonus['logo_url']); ?>" alt="Logo" style="width: 50px; height: auto;"></td>
                                        <td><?php echo htmlspecialchars($bonus['name']); ?></td>
                                        <td><?php echo htmlspecialchars($bonus['amount']); ?></td>
                                        <td><?php echo htmlspecialchars($bonus['badge']); ?></td>
                                        <td><?php echo htmlspecialchars($bonus['category']); ?></td>
                                        <td><span class="badge bg-<?php echo ($bonus['status'] == 'active' ? 'green' : 'red'); ?>"><?php echo htmlspecialchars(ucfirst($bonus['status'])); ?></span></td>
                                        <td>
                                            <a href="<?php echo ADMIN_URL; ?>manage_bonuses.php?action=edit&id=<?php echo $bonus['id']; ?>" class="btn btn-sm btn-icon btn-primary">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.167a.784 .784 0 0 0 1.07 -.139l.75 -1.094a.5 .5 0 0 1 .74 -.093l.974 .975a.5 .5 0 0 1 .093 .74l-1.094 .75a.784 .784 0 0 0 -.139 1.07l.385 .925a.5 .5 0 0 1 -.238 .622l-1.094 .557a.784 .784 0 0 0 -.972 .594l-.385 1.026a.5 .5 0 0 1 -.622 .238l-.557 -1.094a.784 .784 0 0 0 -1.07 -.139l-.925 .385a.5 .5 0 0 1 -.622 -.238l-.557 -1.094a.784 .784 0 0 0 -.139 -1.07l1.094 -.75a.784 .784 0 0 0 .139 -1.07l-.385 -.925a.5 .5 0 0 1 .238 -.622l1.094 -.557a.784 .784 0 0 0 .972 -.594l.385 -1.026a.5 .5 0 0 1 .622 -.238l.557 1.094z"/></svg>
                                            </a>
                                            <a href="<?php echo ADMIN_URL; ?>manage_bonuses.php?action=delete&id=<?php echo $bonus['id']; ?>" class="btn btn-sm btn-icon btn-danger" onclick="return confirm('Bu bonusu silmek istediğinizden emin misiniz?');">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center">Henüz eklenmiş bonus bulunmamaktadır.</td>
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