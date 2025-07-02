<?php
require_once 'config.php';

if (isset($_GET['bonus_id']) && isset($_GET['link'])) {
    $bonus_id = (int)$_GET['bonus_id'];
    $redirect_link = $_GET['link'];

    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];

        try {
            // Kullanıcının bu bonusu daha önce alıp almadığını kontrol et
            $stmt_check = $pdo->prepare("SELECT id FROM user_bonuses WHERE user_id = ? AND bonus_id = ?");
            $stmt_check->execute([$user_id, $bonus_id]);

            if (!$stmt_check->fetch()) { // Daha önce almamışsa kaydet
                $stmt_insert = $pdo->prepare("INSERT INTO user_bonuses (user_id, bonus_id) VALUES (?, ?)");
                $stmt_insert->execute([$user_id, $bonus_id]);
            }
        } catch (\PDOException $e) {
            // Hata durumunda (örn. benzersizlik kısıtlaması ihlali) logla ama kullanıcıya gösterme
            error_log("Bonus tıklama kaydedilirken hata oluştu: " . $e->getMessage());
        }
    }

    // Kullanıcıyı bonus linkine yönlendir
    redirect($redirect_link);
} else {
    redirect('index.php'); // Geçersiz istekse anasayfaya dön
}
?>