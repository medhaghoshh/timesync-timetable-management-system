<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $stmt = $pdo->prepare("SELECT user_id FROM students WHERE id=?");
    $stmt->execute([(int)$_POST['delete_id']]);
    $userId = $stmt->fetchColumn();
    if ($userId) {
        $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$userId]); // cascades to students
        setFlash('success', 'Student removed successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/students.php?' . http_build_query($_GET));
    exit;
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$search = trim($_GET['q'] ?? '');
$deptFilter = $_GET['department_id'] ?? '';

$where = [];
$params = [];
if ($search !== '') { $where[] = '(u.name LIKE ? OR s.roll_number LIKE ? OR u.email LIKE ?)'; array_push($params, "%$search%", "%$search%", "%$search%"); }
if ($deptFilter !== '') { $where[] = 's.department_id = ?'; $params[] = $deptFilter; }
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
    SELECT s.id, s.roll_number, u.name, u.email, d.code AS dept_code, sem.semester_number, sec.section_name
    FROM students s
    JOIN users u ON u.id = s.user_id
    JOIN departments d ON d.id = s.department_id
    JOIN semesters sem ON sem.id = s.semester_id
    JOIN sections sec ON sec.id = s.section_id
    $whereSql
    ORDER BY u.name
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$students = $stmt->fetchAll();

$pageTitle = 'Students';
$role = 'admin';
$activePage = 'students';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Students</h1>
        </div>
        <span class="text-muted text-sm"><?= count($students) ?> total</span>
    </div>
    <div class="page-body">
        <form method="GET" class="card card-pad mb-24 flex gap-16" style="flex-wrap:wrap;align-items:flex-end;">
            <div class="form-group" style="margin:0;flex:1;min-width:200px;">
                <label>Search</label>
                <input type="text" name="q" class="form-control" placeholder="Name, roll no, email..." value="<?= e($search) ?>">
            </div>
            <div class="form-group" style="margin:0;min-width:180px;">
                <label>Department</label>
                <select name="department_id" class="form-control">
                    <option value="">All Departments</option>
                    <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= (string)$deptFilter===(string)$d['id']?'selected':'' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        </form>

        <?php if (!$students): ?>
            <div class="card"><div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-user-graduate"></i></div>
                <h3>No students found</h3>
                <p>Students appear here once they register on TimeSync.</p>
            </div></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Roll No.</th><th>Name</th><th>Email</th><th>Dept/Sem/Sec</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($students as $s): ?>
                    <tr>
                        <td><?= e($s['roll_number']) ?></td>
                        <td><?= e($s['name']) ?></td>
                        <td><?= e($s['email']) ?></td>
                        <td><span class="badge badge-neutral"><?= e($s['dept_code']) ?> · S<?= $s['semester_number'] ?> · <?= e($s['section_name']) ?></span></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this student account? This cannot be undone.');">
                                <input type="hidden" name="delete_id" value="<?= $s['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-trash" style="color:var(--danger);"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
