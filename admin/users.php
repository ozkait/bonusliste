<?php
require_once __DIR__ . '/includes/admin_header.php';

$message = '';

// Telefon numaralarını CSV olarak dışa aktarma işlemi
if (isset($_GET['action']) && $_GET['action'] == 'export_phones') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="telefon_numaralari.csv"');
    
    $output = fopen('php://output', 'w');
    fputcsv($output, array('Telefon Numarası')); // CSV başlığı

    $stmt = $pdo->query("SELECT phone_number FROM users WHERE phone_number IS NOT NULL AND phone_number != ''");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit();
}

// Üyeleri çek
$stmt_users = $pdo->query("SELECT id, username, full_name, email, phone_number, role, created_at, last_login FROM users ORDER BY created_at DESC");
$users = $stmt_users->fetchAll();
?>

<div class="page-body">
    <div class="container-xl">
        <div class="row row-cards">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Site Üyeleri</h3>
                        <div class="card-actions">
                            <a href="<?php echo ADMIN_URL; ?>users.php?action=export_phones" class="btn btn-primary d-none d-sm-inline-block">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 10a6 6 0 1 0 0 12a6 6 0 0 0 0 -12"/><path d="M15 12h-6"/><path d="M12 9v6"/><path d="M22 10h-2l-3.3 -3.3a4 4 0 0 0 -5.4 -1.3l-2.4 2.4l-3.2 -3.2a4 4 0 0 0 -1.3 -5.4l-3.3 -3.3"/></svg>
                                Telefon Numaralarını CSV Olarak Aktar
                            </a>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table card-table table-vcenter text-nowrap datatable">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kullanıcı Adı</th>
                                <th>İsim Soyisim</th>
                                <th>E-posta</th>
                                <th>Telefon</th>
                                <th>Rol</th>
                                <th>Kayıt Tarihi</th>
                                <th>Son Giriş</th>
                                <th>İşlemler</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php if (!empty($users)): ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['id']); ?></td>
                                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['full_name'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone_number'] ?? '-'); ?></td>
                                        <td><span class="badge bg-<?php echo ($user['role'] == 'admin' ? 'purple' : 'azure'); ?>"><?php echo htmlspecialchars(ucfirst($user['role'])); ?></span></td>
                                        <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                        <td><?php echo $user['last_login'] ? date('d.m.Y H:i', strtotime($user['last_login'])) : 'Hiç giriş yapmadı'; ?></td>
                                        <td>
                                            <a href="<?php echo ADMIN_URL; ?>edit_user.php?id=<?php echo $user['id']; ?>" class="btn btn-sm btn-icon btn-primary" title="Düzenle">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M8 7a4 4 0 1 0 8 0a4 4 0 0 0 -8 0"/><path d="M6 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.167a.784 .784 0 0 0 1.07 -.139l.75 -1.094a.5 .5 0 0 1 .74 -.093l.974 .975a.5 .5 0 0 1 .093 .74l-1.094 .75a.784 .784 0 0 0 -.139 1.07l.385 .925a.5 .5 0 0 1 -.238 .622l-1.094 .557a.784 .784 0 0 0 -.972 .594l-.385 1.026a.5 .5 0 0 1 -.622 .238l-.557 -1.094a.784 .784 0 0 0 -1.07 -.139l-.925 .385a.5 .5 0 0 1 -.622 -.238l-.557 -1.094a.784 .784 0 0 0 -.139 -1.07l1.094 -.75a.784 .784 0 0 0 .139 -1.07l-.385 -.925a.5 .5 0 0 1 .238 -.622l1.094 -.557a.784 .784 0 0 0 .972 -.594l.385 -1.026a.5 .5 0 0 1 .622 -.238l.557 1.094z"/></svg>
                                            </a>
                                            </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="9" class="text-center">Henüz kayıtlı kullanıcı bulunmamaktadır.</td>
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