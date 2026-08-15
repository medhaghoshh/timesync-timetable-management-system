<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . (currentRole() === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$errors = [];
$oldEmail = '';
$oldRole = 'student';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'student';
    $oldEmail = $email;
    $oldRole = $role;

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND role = ?");
        $stmt->execute([$email, $role]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid email or password for the selected role.';
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['name'] = $user['name'];
            setFlash('success', 'Welcome back, ' . explode(' ', $user['name'])[0] . '!');
            header('Location: ' . BASE_URL . '/' . ($role === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
            exit;
        }
    }
}

if (isset($_GET['error']) && $_GET['error'] === 'unauthorized') {
    $errors[] = 'You must log in with the correct role to view that page.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Login · TimeSync</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    body { min-height: 100vh; }
    .auth-wrap { display: grid; grid-template-columns: 1fr 1fr; min-height: 100vh; }
    .auth-brand { background: var(--text); color: #fff; display: flex; flex-direction: column; justify-content: center; padding: 60px; position: relative; overflow: hidden; }
    .auth-brand::after { content: ''; position: absolute; width: 400px; height: 400px; background: var(--primary); opacity: .25; border-radius: 50%; filter: blur(80px); top: -100px; right: -100px; }
    .auth-brand .logo-mark { display: flex; align-items: center; gap: 10px; font-family: var(--font-display); font-weight: 700; font-size: 1.3rem; margin-bottom: 40px; position: relative; }
    .auth-brand .logo-icon { width: 36px; height: 36px; border-radius: 10px; background: var(--primary); display: flex; align-items: center; justify-content: center; }
    .auth-brand h2 { font-size: 2.2rem; color: #fff; margin-bottom: 16px; position: relative; }
    .auth-brand p { color: #C7C9D9; max-width: 380px; position: relative; }
    .auth-form-wrap { display: flex; align-items: center; justify-content: center; padding: 40px; }
    .auth-card { width: 100%; max-width: 380px; }
    .auth-card h1 { font-size: 1.6rem; margin-bottom: 6px; }
    .role-toggle { display: flex; background: var(--background); border: 1px solid var(--border); border-radius: var(--radius-sm); padding: 4px; margin-bottom: 20px; }
    .role-toggle label { flex: 1; text-align: center; padding: 8px; border-radius: 7px; font-size: 0.85rem; font-weight: 600; cursor: pointer; margin: 0; color: var(--muted); }
    .role-toggle input { display: none; }
    .role-toggle input:checked + label { background: var(--surface); color: var(--primary); box-shadow: var(--shadow-sm); }
    .alert-box { background: var(--danger-light); color: var(--danger); padding: 12px 14px; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 18px; }
    .demo-box { background: var(--primary-light); border-radius: var(--radius-sm); padding: 12px 14px; font-size: 0.78rem; color: var(--primary-dark); margin-top: 20px; line-height: 1.6; }
    @media (max-width: 900px) { .auth-wrap { grid-template-columns: 1fr; } .auth-brand { display: none; } }
</style>
</head>
<body>
<div class="auth-wrap">
    <div class="auth-brand">
        <div class="logo-mark"><span class="logo-icon"><i class="fa-solid fa-calendar-days"></i></span>TimeSync</div>
        <h2>One login. Your entire semester, scheduled.</h2>
        <p>Sign in to see your personalized timetable, today's classes, and any conflicts affecting your section — instantly.</p>
    </div>
    <div class="auth-form-wrap">
        <div class="auth-card">
            <h1>Welcome back</h1>
            <p class="text-muted mb-24" style="font-size:.9rem;">Log in to continue to your dashboard.</p>

            <?php if ($errors): ?>
                <div class="alert-box"><?= e(implode(' ', $errors)) ?></div>
            <?php endif; ?>

            <form method="POST" id="loginForm" novalidate>
                <div class="role-toggle">
                    <input type="radio" name="role" id="roleStudent" value="student" <?= $oldRole === 'student' ? 'checked' : '' ?>>
                    <label for="roleStudent"><i class="fa-solid fa-user-graduate"></i> Student</label>
                    <input type="radio" name="role" id="roleAdmin" value="admin" <?= $oldRole === 'admin' ? 'checked' : '' ?>>
                    <label for="roleAdmin"><i class="fa-solid fa-user-shield"></i> Admin</label>
                </div>

                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?= e($oldEmail) ?>" required placeholder="you@college.edu">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-icon-wrap">
                        <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                        <button type="button" class="icon-btn" onclick="togglePasswordVisibility('password', this.querySelector('i'))" aria-label="Toggle password visibility">
                            <i class="fa-solid fa-eye"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block" id="loginBtn">
                    <span class="btn-text">Login</span>
                </button>
            </form>

            <p class="text-muted mt-16" style="font-size:.9rem;">Don't have an account? <a href="<?= BASE_URL ?>/register.php" style="color:var(--primary);font-weight:600;">Register</a></p>

            <div class="demo-box">
                <strong>Demo credentials</strong><br>
                Admin: admin@timesync.com / Admin@123<br>
                Student: student@timesync.com / Student@123
            </div>
        </div>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('loginForm').addEventListener('submit', function (e) {
    const btn = document.getElementById('loginBtn');
    btn.innerHTML = '<span class="loading-spinner"></span> Logging in...';
    btn.disabled = true;
});
</script>
</body>
</html>
