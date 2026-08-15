<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . (currentRole() === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY semester_number")->fetchAll();

$errors = [];
$old = ['name' => '', 'roll_number' => '', 'email' => '', 'department_id' => '', 'semester_id' => '', 'section_name' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old['name'] = trim($_POST['name'] ?? '');
    $old['roll_number'] = trim($_POST['roll_number'] ?? '');
    $old['email'] = trim($_POST['email'] ?? '');
    $old['department_id'] = $_POST['department_id'] ?? '';
    $old['semester_id'] = $_POST['semester_id'] ?? '';
    $old['section_name'] = strtoupper(trim($_POST['section_name'] ?? ''));
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if ($old['name'] === '') $errors[] = 'Full name is required.';
    if ($old['roll_number'] === '') $errors[] = 'Roll number is required.';
    if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters long.';
    if ($password !== $confirmPassword) $errors[] = 'Passwords do not match.';
    if (!$old['department_id']) $errors[] = 'Please select a department.';
    if (!$old['semester_id']) $errors[] = 'Please select a semester.';
    if ($old['section_name'] === '') $errors[] = 'Please select a section.';

    if (!$errors) {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$old['email']]);
        if ($check->fetch()) $errors[] = 'An account with this email already exists.';

        $check2 = $pdo->prepare("SELECT id FROM students WHERE roll_number = ?");
        $check2->execute([$old['roll_number']]);
        if ($check2->fetch()) $errors[] = 'This roll number is already registered.';
    }

    if (!$errors) {
        // Find or create the section for this department+semester+name
        $stmt = $pdo->prepare("SELECT id FROM sections WHERE department_id=? AND semester_id=? AND section_name=?");
        $stmt->execute([$old['department_id'], $old['semester_id'], $old['section_name']]);
        $sectionId = $stmt->fetchColumn();
        if (!$sectionId) {
            $ins = $pdo->prepare("INSERT INTO sections (department_id, semester_id, section_name) VALUES (?,?,?)");
            $ins->execute([$old['department_id'], $old['semester_id'], $old['section_name']]);
            $sectionId = $pdo->lastInsertId();
        }

        try {
            $pdo->beginTransaction();

            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $ins = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?, 'student')");
            $ins->execute([$old['name'], $old['email'], $hashedPassword]);
            $userId = $pdo->lastInsertId();

            $ins2 = $pdo->prepare("INSERT INTO students (user_id, roll_number, department_id, semester_id, section_id) VALUES (?,?,?,?,?)");
            $ins2->execute([$userId, $old['roll_number'], $old['department_id'], $old['semester_id'], $sectionId]);

            $pdo->commit();

            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = 'student';
            $_SESSION['name'] = $old['name'];
            setFlash('success', 'Account created successfully. Welcome to TimeSync!');
            header('Location: ' . BASE_URL . '/student/dashboard.php');
            exit;
        } catch (Throwable $e) {
            $pdo->rollBack();
            error_log('Registration error: ' . $e->getMessage());
            $errors[] = 'We could not create your account. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register · TimeSync</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    body { background: var(--background); min-height: 100vh; }
    .reg-wrap { max-width: 560px; margin: 0 auto; padding: 50px 20px; }
    .reg-logo { display: flex; align-items: center; gap: 10px; font-family: var(--font-display); font-weight: 700; font-size: 1.2rem; justify-content: center; margin-bottom: 26px; }
    .reg-logo .logo-icon { width: 34px; height: 34px; border-radius: 9px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; }
    .reg-card { padding: 34px; }
    .reg-card h1 { font-size: 1.5rem; text-align: center; margin-bottom: 4px; }
    .reg-card p.sub { text-align: center; margin-bottom: 26px; }
    .row-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    .alert-box { background: var(--danger-light); color: var(--danger); padding: 12px 14px; border-radius: var(--radius-sm); font-size: 0.85rem; margin-bottom: 18px; }
    @media (max-width: 560px) { .row-2 { grid-template-columns: 1fr; } }
</style>
</head>
<body>
<div class="reg-wrap">
    <a href="<?= BASE_URL ?>/index.php" class="reg-logo"><span class="logo-icon"><i class="fa-solid fa-calendar-days"></i></span>TimeSync</a>
    <div class="card reg-card">
        <h1>Create your student account</h1>
        <p class="sub text-muted">We'll build your personalized timetable automatically.</p>

        <?php if ($errors): ?>
            <div class="alert-box">
                <?php foreach ($errors as $err): ?>&bull; <?= e($err) ?><br><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="POST" id="regForm" novalidate>
            <div class="row-2">
                <div class="form-group">
                    <label for="name">Full Name</label>
                    <input type="text" class="form-control" id="name" name="name" value="<?= e($old['name']) ?>" required>
                </div>
                <div class="form-group">
                    <label for="roll_number">Roll Number</label>
                    <input type="text" class="form-control" id="roll_number" name="roll_number" value="<?= e($old['roll_number']) ?>" required>
                </div>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" value="<?= e($old['email']) ?>" required>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required minlength="8">
                    <div class="form-hint">At least 8 characters.</div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required minlength="8">
                </div>
            </div>

            <div class="row-2">
                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select class="form-control" id="department_id" name="department_id" required>
                        <option value="">Select department</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= (string)$old['department_id'] === (string)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester_id">Semester</label>
                    <select class="form-control" id="semester_id" name="semester_id" required>
                        <option value="">Select semester</option>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (string)$old['semester_id'] === (string)$s['id'] ? 'selected' : '' ?>>Semester <?= $s['semester_number'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="section_name">Section</label>
                <select class="form-control" id="section_name" name="section_name" required>
                    <option value="">Select section</option>
                    <?php foreach (['A','B','C','D'] as $s): ?>
                        <option value="<?= $s ?>" <?= $old['section_name'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <button type="submit" class="btn btn-primary btn-block" id="regBtn">Create Account</button>
        </form>

        <p class="text-muted mt-16" style="text-align:center;font-size:.9rem;">Already have an account? <a href="<?= BASE_URL ?>/login.php" style="color:var(--primary);font-weight:600;">Login</a></p>
    </div>
</div>
<script src="<?= BASE_URL ?>/assets/js/app.js"></script>
<script>
document.getElementById('regForm').addEventListener('submit', function (e) {
    const pw = document.getElementById('password').value;
    const cpw = document.getElementById('confirm_password').value;
    if (pw !== cpw) {
        e.preventDefault();
        showToast('Passwords do not match.', 'error');
        return;
    }
    const btn = document.getElementById('regBtn');
    btn.innerHTML = '<span class="loading-spinner dark"></span> Creating account...';
    btn.disabled = true;
});
</script>
</body>
</html>
