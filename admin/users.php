<?php
$page_title = "Registered Guests";
$page_subtitle = "Contact details and accounts registration log";

require_once 'includes/header.php';

$guests = [];
$alert_msg = "";

try {
    $stmt = $pdo->prepare("SELECT id, fullname, email, phone, created_at FROM users WHERE role = 'customer' ORDER BY created_at DESC");
    $stmt->execute();
    $guests = $stmt->fetchAll();
} catch (PDOException $e) {
    $alert_msg = "Error querying guests database: " . $e->getMessage();
}
?>

<?php if (!empty($alert_msg)): ?>
    <div class="alert alert-danger" style="margin-bottom: 2rem;">
        <?php echo $alert_msg; ?>
    </div>
<?php endif; ?>

<!-- Users List Grid -->
<div class="admin-panel">
    <div class="admin-panel-header">
        <h2>Customer Accounts Directory</h2>
        <span style="font-size: 0.9rem; background: rgba(212, 175, 55, 0.1); color: var(--primary-admin); padding: 0.3rem 0.8rem; border-radius: 50px; font-weight: 600;">
            Total: <?php echo count($guests); ?> Accounts
        </span>
    </div>
    
    <?php if (empty($guests)): ?>
        <p style="color: var(--text-admin-muted); text-align: center; padding: 3rem 0;">No guest accounts found in the database system.</p>
    <?php else: ?>
        <div class="custom-table-container">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>Guest ID</th>
                        <th>Full Name</th>
                        <th>Email Address</th>
                        <th>Phone Number</th>
                        <th>Registration Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($guests as $g): ?>
                        <tr>
                            <td>#<?php echo $g['id']; ?></td>
                            <td>
                                <strong style="color: var(--text-admin-main);"><?php echo htmlspecialchars($g['fullname']); ?></strong>
                            </td>
                            <td><?php echo htmlspecialchars($g['email']); ?></td>
                            <td>
                                <?php echo !empty($g['phone']) ? htmlspecialchars($g['phone']) : '<span style="color: var(--text-admin-muted); font-style: italic;">Not provided</span>'; ?>
                            </td>
                            <td>
                                <?php echo date('Y-m-d H:i', strtotime($g['created_at'])); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
