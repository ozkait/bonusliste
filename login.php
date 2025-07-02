<?php
require_once 'config.php'; // config.php ana dizinde olduğu için sadece dosya adı yeterli

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username_or_email = trim($_POST['username_or_email']); // Kullanıcı adı veya e-posta alanı
    $password = $_POST['password'];

    if (empty($username_or_email) || empty($password)) {
        $message = "Lütfen kullanıcı adı/e-posta ve şifrenizi girin.";
    } else {
        // Kullanıcıyı hem username hem de email ile ara
        $stmt = $pdo->prepare("SELECT id, password_hash, username, role FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username_or_email, $username_or_email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username']; // Oturumda kullanıcı adını sakla
            $_SESSION['user_role'] = $user['role'];

            // Son giriş tarihini güncelle
            $stmt_update = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt_update->execute([$user['id']]);

            // Rolüne göre yönlendirme
            if ($user['role'] == 'admin') {
                $_SESSION['admin_logged_in'] = true;
                redirect(ADMIN_URL . 'index.php');
            } else {
                redirect(BASE_URL . 'profile.php');
            }
        } else {
            $message = "Geçersiz kullanıcı adı/e-posta veya şifre.";
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
    <title>Giriş Yap - Bonus Siteleri</title>
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
                    <h2 class="h2 text-center mb-4">Hesabınıza Giriş Yapın</h2>
                    <?php if ($message): ?>
                        <div class="alert alert-danger" role="alert"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <form action="<?php echo BASE_URL; ?>login.php" method="POST" autocomplete="off" novalidate>
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı veya E-posta</label>
                            <input type="text" name="username_or_email" class="form-control" placeholder="Kullanıcı adınızı veya e-posta adresinizi girin" autocomplete="off" required>
                        </div>
                        <div class="mb-2">
                            <label class="form-label">
                                Şifre
                                <span class="form-label-description">
                                    <a href="#">Şifremi unuttum</a>
                                </span>
                            </label>
                            <div class="input-group input-group-flat">
                                <input type="password" name="password" class="form-control" placeholder="Şifrenizi girin"  autocomplete="off" required>
                                <span class="input-group-text">
                                    <a href="#" class="link-secondary" data-bs-toggle="tooltip" aria-label="Show password" data-bs-original-title="Show password"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                                    </a>
                                </span>
                            </div>
                        </div>
                        <div class="mb-2">
                            <label class="form-check">
                                <input type="checkbox" class="form-check-input"/>
                                <span class="form-check-label">Beni hatırla</span>
                            </label>
                        </div>
                        <div class="form-footer">
                            <button type="submit" class="btn btn-primary w-100">Giriş Yap</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="text-center text-muted mt-3">
                Hesabınız yok mu? <a href="<?php echo BASE_URL; ?>register.php" tabindex="-1">Şimdi Üye Ol</a>
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