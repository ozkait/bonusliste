<?php
require_once __DIR__ . '/includes/admin_header.php';

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user = null;
$message = '';

if ($user_id > 0) {
    // Kullanıcıyı veritabanından çek
    $stmt = $pdo->prepare("SELECT id, username, full_name, email, phone_number, role FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        $message = '<div class="alert alert-danger">Kullanıcı bulunamadı.</div>';
        $user_id = 0; // Geçersiz ID durumunda formu gösterme
    }
} else {
    $message = '<div class="alert alert-danger">Geçersiz kullanıcı ID\'si.</div>';
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $user_id > 0) {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $role = $_POST['role'];
    $password = $_POST['password']; // Yeni şifre (isteğe bağlı)

    if (empty($full_name) || empty($email) || empty($phone_number) || empty($role)) {
        $message = '<div class="alert alert-danger">Lütfen tüm zorunlu alanları doldurun.</div>';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = '<div class="alert alert-danger">Geçerli bir e-posta adresi girin.</div>';
    } elseif (!preg_match('/^\+90\d{10}$/', $phone_number)) {
        $message = '<div class="alert alert-danger">Telefon numarası +90 ile başlamalı ve 10 rakam içermelidir (örn: +905XXXXXXXXX).</div>';
    } else {
        // E-posta veya telefon başka bir kullanıcıda var mı kontrol et (kendisi hariç)
        $stmt_check = $pdo->prepare("SELECT id FROM users WHERE (email = ? OR phone_number = ?) AND id != ?");
        $stmt_check->execute([$email, $phone_number, $user_id]);
        if ($stmt_check->fetch()) {
            $message = '<div class="alert alert-danger">Bu e-posta veya telefon numarası zaten başka bir kullanıcıya ait.</div>';
        } else {
            $sql = "UPDATE users SET full_name = ?, email = ?, phone_number = ?, role = ? ";
            $params = [$full_name, $email, $phone_number, $role];

            if (!empty($password)) { // Eğer yeni şifre girildiyse
                if (strlen($password) < 6) {
                    $message = '<div class="alert alert-danger">Yeni şifre en az 6 karakter olmalıdır.</div>';
                } else {
                    $password_hash = password_hash($password, PASSWORD_DEFAULT);
                    $sql .= ", password_hash = ? ";
                    $params[] = $password_hash;
                }
            }
            
            if (empty($message)) { // Önceki kontrollerde hata yoksa devam et
                $sql .= "WHERE id = ?";
                $params[] = $user_id;

                $stmt_update = $pdo->prepare($sql);
                if ($stmt_update->execute($params)) {
                    $message = '<div class="alert alert-success">Kullanıcı bilgileri başarıyla güncellendi!</div>';
                    // Bilgiler güncellendikten sonra kullanıcıyı tekrar çek
                    $stmt = $pdo->prepare("SELECT id, username, full_name, email, phone_number, role FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $user = $stmt->fetch();
                } else {
                    $message = '<div class="alert alert-danger">Kullanıcı bilgileri güncellenirken bir hata oluştu.</div>';
                }
            }
        }
    }
}

// Kullanıcı silme işlemi (bu sayfadan yapılabilir veya users.php'ye eklenir)
if (isset($_GET['action']) && $_GET['action'] == 'delete' && $user_id > 0) {
    if ($_SESSION['user_id'] == $user_id) { // Kendini silmeyi engelle
        $message = '<div class="alert alert-danger">Kendi hesabınızı silemezsiniz.</div>';
    } else {
        try {
            $stmt_delete = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt_delete->execute([$user_id])) {
                $message = '<div class="alert alert-success">Kullanıcı başarıyla silindi!</div>';
                redirect(ADMIN_URL . 'users.php'); // Silme başarılı ise kullanıcı listesine dön
            } else {
                $message = '<div class="alert alert-danger">Kullanıcı silinirken bir hata oluştu.</div>';
            }
        } catch (PDOException $e) {
            $message = '<div class="alert alert-danger">Kullanıcı silinirken bir veritabanı hatası oluştu: ' . $e->getMessage() . '</div>';
        }
    }
}

?>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Kullanıcı Düzenle: <?php echo htmlspecialchars($user['username'] ?? ''); ?></h3>
                    </div>
                    <div class="card-body">
                        <?php echo $message; ?>
                        <?php if ($user): ?>
                        <form action="<?php echo ADMIN_URL; ?>edit_user.php?id=<?php echo $user['id']; ?>" method="POST">
                            <div class="mb-3">
                                <label class="form-label">Kullanıcı Adı (Değiştirilemez)</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">İsim Soyisim</label>
                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">E-posta Adresi</label>
                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Telefon Numarası</label>
                                <input type="tel" name="phone_number" class="form-control" value="<?php echo htmlspecialchars($user['phone_number'] ?? ''); ?>" pattern="^\+90\d{10}$" title="+90 ile başlayıp 10 rakam içermelidir" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Rol</label>
                                <select name="role" class="form-select" <?php echo ($_SESSION['user_id'] == $user['id'] && $_SESSION['user_role'] == 'admin') ? 'disabled' : ''; ?>>
                                    <option value="user" <?php echo ($user['role'] == 'user' ? 'selected' : ''); ?>>User</option>
                                    <option value="admin" <?php echo ($user['role'] == 'admin' ? 'selected' : ''); ?>>Admin</option>
                                </select>
                                <?php if ($_SESSION['user_id'] == $user['id'] && $_SESSION['user_role'] == 'admin'): ?>
                                    <small class="form-hint text-danger">Kendi rolünüzü değiştiremezsiniz.</small>
                                    <input type="hidden" name="role" value="<?php echo htmlspecialchars($user['role']); ?>">
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Yeni Şifre (Değiştirmek istemiyorsanız boş bırakın)</label>
                                <input type="password" name="password" class="form-control" placeholder="Yeni şifre">
                                <small class="form-hint">En az 6 karakter.</small>
                            </div>
                            <button type="submit" class="btn btn-primary">Kaydet</button>
                            <a href="<?php echo ADMIN_URL; ?>users.php" class="btn btn-secondary ms-2">İptal</a>
                            <?php if ($_SESSION['user_id'] != $user['id']): // Kendi hesabını silme butonunu gizle ?>
                            <a href="<?php echo ADMIN_URL; ?>edit_user.php?action=delete&id=<?php echo $user['id']; ?>" class="btn btn-danger ms-auto" onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?');">Kullanıcıyı Sil</a>
                            <?php endif; ?>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/includes/admin_footer.php'; ?>