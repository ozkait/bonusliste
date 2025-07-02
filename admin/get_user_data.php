<?php
require_once __DIR__ . '/../config.php'; // config.php bir üst dizinde olduğu için bu şekilde yol belirtilir
check_admin_auth(); // Sadece adminlerin erişebildiğinden emin olun

header('Content-Type: application/json'); // JSON çıktısı vereceğimizi belirtiyoruz

try {
    // Toplam üye sayısını çekme
    $stmt_total_users = $pdo->query("SELECT COUNT(id) AS total_users FROM users");
    $total_users = $stmt_total_users->fetchColumn();

    // Zaman içindeki üye kayıtlarını çekme (örneğin son 30 gün)
    // Bu veri, zaman serisi grafiği için kullanılabilir
    $stmt_users_by_date = $pdo->prepare("SELECT DATE(created_at) as register_date, COUNT(id) as count
                                         FROM users
                                         WHERE created_at >= CURDATE() - INTERVAL 30 DAY
                                         GROUP BY DATE(created_at)
                                         ORDER BY register_date ASC");
    $stmt_users_by_date->execute();
    $users_by_date = $stmt_users_by_date->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'total_users' => $total_users,
        'users_by_date' => $users_by_date
    ]);

} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => 'Veritabanı hatası: ' . $e->getMessage()
    ]);
}
?>