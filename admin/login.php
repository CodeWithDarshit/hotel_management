<?php
require_once '../config.php';

// Redirect if already logged in as admin
if (isLoggedIn() && isAdmin()) {
    header("Location: index.php");
    exit;
}

$error_msg = "";
$email = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_msg = "Please enter credentials.";
    } else {
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = 'admin'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set Session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = 'admin';
                
                header("Location: index.php");
                exit;
            } else {
                $error_msg = "Unauthorized credentials or not an admin account.";
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aura Heights | Admin Login</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body style="background: var(--bg-main);">
    <div class="admin-login-wrapper">
        <div class="auth-card" style="background: var(--bg-surface); border: 1px solid var(--border-color);">
            <div style="text-align: center; color: var(--primary); font-size: 2.5rem; margin-bottom: 1rem;">👑</div>
            <h2 class="auth-title">AURA HEIGHTS</h2>
            <p class="auth-subtitle" style="color: var(--primary);">Staff Management Portal</p>
            
            <?php if (!empty($error_msg)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_msg; ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST">
                <div class="form-group">
                    <label for="email">Admin Email</label>
                    <input type="email" id="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" placeholder="admin@hotel.com" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Security Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn-primary auth-btn" style="background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);">
                    Unlock Console
                </button>
            </form>
            
            <p class="auth-redirect" style="margin-top: 1.5rem;"><a href="../index.php" style="color: var(--text-secondary);">← Back to Guest Site</a></p>
        </div>
    </div>
</body>
</html>
