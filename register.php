<?php
require_once 'config.php'; // config.php ana dizinde olduğu için sadece dosya adı yeterli

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $phone_number = trim($_POST['phone_number']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $agree_terms = isset($_POST['agree_terms']);

    // Kullanıcı adı e-posta adresinin yerini alabilir veya ayrı bir alan olabilir.
    // Şimdilik username'i email olarak kabul edelim veya isterseniz yeni bir username alanı ekleyebilirsiniz.
    $username = $email; // Veya ayrı bir inputtan alın.

    if (empty($full_name) || empty($email) || empty($phone_number) || empty($password) || empty($password_confirm)) {
        $message = "Lütfen tüm zorunlu alanları doldurun.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Geçerli bir e-posta adresi girin.";
    } elseif (!preg_match('/^\+90\d{10}$/', $phone_number)) { // +90 ve ardından 10 rakam
        $message = "Telefon numarası +90 ile başlamalı ve 10 rakam içermelidir (örn: +905XXXXXXXXX).";
    } elseif ($password !== $password_confirm) {
        $message = "Şifreler uyuşmuyor.";
    } elseif (strlen($password) < 6) {
        $message = "Şifre en az 6 karakter olmalıdır.";
    } elseif (!$agree_terms) {
        $message = "Hüküm ve koşulları kabul etmelisiniz.";
    } else {
        // Kullanıcı adı (e-posta) veya telefon numarası zaten var mı kontrol et
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR phone_number = ?");
        $stmt->execute([$email, $phone_number]);
        if ($stmt->fetch()) {
            $message = "Bu e-posta adresi veya telefon numarası zaten kayıtlı.";
        } else {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare("INSERT INTO users (username, full_name, email, phone_number, password_hash, role) VALUES (?, ?, ?, ?, ?, 'user')");
            if ($stmt->execute([$username, $full_name, $email, $phone_number, $password_hash])) {
                $message = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
                // İsteğe bağlı: Kayıt sonrası otomatik giriş
                // $_SESSION['user_id'] = $pdo->lastInsertId();
                // $_SESSION['username'] = $username;
                // $_SESSION['user_role'] = 'user';
                // redirect(BASE_URL . 'profile.php');
            } else {
                $message = "Kayıt sırasında bir hata oluştu.";
            }
        }
    }
}
?>
<!doctype html>
<html lang="tr">
<head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover"/>
    <meta http-equiv="X-UA-Compatible" content="ie=edge"/>
    <title>Yeni Hesap Oluştur - Bonus Siteleri</title>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-flags.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-payments.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/tabler-vendors.min.css" rel="stylesheet"/>
    <link href="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/css/demo.min.css" rel="stylesheet"/>
    <style>
        :root { --tblr-font-sans-serif: 'Inter Var', -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; }
        body { font-feature-settings: "liga" 0; }
    </style>
</head>
<body class=" d-flex flex-column">
    <div class="page page-center">
        <div class="container container-tight py-4">
                        <div class="card card-md">
                <div class="card-body">
                    <h2 class="h2 text-center mb-4">Yeni Hesap Oluştur</h2>
                    <?php if ($message): ?>
                        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form action="<?php echo BASE_URL; ?>register.php" method="POST" autocomplete="off" novalidate>
                        <div class="mb-3">
                            <label class="form-label">İsim Soyisim</label>
                            <input type="text" name="full_name" class="form-control" placeholder="Adınızı ve soyadınızı girin" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">E-posta Adresi</label>
                            <input type="email" name="email" class="form-control" placeholder="E-posta adresinizi girin" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Telefon Numarası</label>
                            <input type="tel" name="phone_number" class="form-control" placeholder="+90XXXXXXXXXX" pattern="^\+90\d{10}$" title="+90 ile başlayıp 10 rakam içermelidir" required value="<?php echo htmlspecialchars($_POST['phone_number'] ?? ''); ?>">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password" class="form-control" placeholder="Şifrenizi girin (en az 6 karakter)" required>
                                <span class="input-group-text">
                                    <a href="#" class="link-secondary" data-bs-toggle="tooltip" aria-label="Show password" data-bs-original-title="Show password">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                                    </a>
                                </span>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre Tekrar</label>
                            <input type="password" name="password_confirm" class="form-control" placeholder="Şifrenizi tekrar girin" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-check">
                                <input type="checkbox" name="agree_terms" class="form-check-input" required>
                                <span class="form-check-label">
                                    <a href="#" tabindex="-1">Hüküm ve koşulları</a> kabul ediyorum.
                                </span>
                            </label>
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">Hesap Oluştur</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center text-muted mt-3">
                Zaten bir hesabınız var mı? <a href="<?php echo BASE_URL; ?>login.php" tabindex="-1">Giriş Yap</a>
            </div>
            <div class="text-center text-muted mt-2">
                <a href="<?php echo BASE_URL; ?>index.php" tabindex="-1">Anasayfaya Dön</a>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/tabler.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@latest/dist/js/demo.min.js" defer></script>
</body>
</html>