<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$student = getStudentDetails($pdo, currentUserId());
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->execute([currentUserId()]);
    $hash = $stmt->fetchColumn();

    if (!password_verify($current, $hash)) {
        $errors[] = 'Current password is incorrect.';
    } elseif (strlen($new) < 8) {
        $errors[] = 'New password must be at least 8 characters.';
    } elseif ($new !== $confirm) {
        $errors[] = 'New passwords do not match.';
    } else {
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->execute([password_hash($new, PASSWORD_DEFAULT), currentUserId()]);
        setFlash('success', 'Password updated successfully.');
        header('Location: ' . BASE_URL . '/student/profile.php');
        exit;
    }
}

$pageTitle = 'Profile';
$role = 'student';
$activePage = 'profile';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Profile</h1>
        </div>
    </div>
    <div class="page-body">
        <div class="grid grid-2" style="max-width:820px;">
            <div class="card card-pad">
                <h3 class="section-title mb-16">Account Details</h3>
                <div class="modal-row"><span>Name</span><span><?= e($student['name']) ?></span></div>
                <div class="modal-row"><span>Roll Number</span><span><?= e($student['roll_number']) ?></span></div>
                <div class="modal-row"><span>Email</span><span><?= e($student['email']) ?></span></div>
                <div class="modal-row"><span>Department</span><span><?= e($student['department_name']) ?></span></div>
                <div class="modal-row"><span>Semester</span><span>Semester <?= (int)$student['semester_number'] ?></span></div>
                <div class="modal-row"><span>Section</span><span><?= e($student['section_name']) ?></span></div>
            </div>

            <div class="card card-pad">
                <h3 class="section-title mb-16">Change Password</h3>
                <?php if ($errors): ?>
                    <div style="background:var(--danger-light);color:var(--danger);padding:10px 14px;border-radius:8px;font-size:0.85rem;margin-bottom:14px;">
                        <?= e(implode(' ', $errors)) ?>
                    </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="form-group">
                        <label>Current Password</label>
                        <input type="password" name="current_password" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="8">
                    </div>
                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <input type="password" name="confirm_password" class="form-control" required minlength="8">
                    </div>
                    <button type="submit" name="change_password" class="btn btn-primary btn-block">Update Password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
