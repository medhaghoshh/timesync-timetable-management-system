<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_faculty'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $deptId = (int) ($_POST['department_id'] ?? 0);

    if ($name === '' || !$deptId) {
        setFlash('error', 'Please provide a name and department.');
    } else {
        $ins = $pdo->prepare("INSERT INTO faculty (name, email, department_id) VALUES (?,?,?)");
        $ins->execute([$name, $email ?: null, $deptId]);
        setFlash('success', 'Faculty member added successfully.');
    }
    header('Location: ' . BASE_URL . '/admin/faculty.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $pdo->prepare("DELETE FROM faculty WHERE id = ?")->execute([(int) $_POST['delete_id']]);
        setFlash('success', 'Faculty member removed.');
    } catch (Throwable $e) {
        setFlash('error', 'This faculty member has existing timetable records and cannot be deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/faculty.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE f.name LIKE ? OR f.email LIKE ?';
    $params = ["%$search%", "%$search%"];
}
$stmt = $pdo->prepare("
    SELECT f.*, d.code AS dept_code,
           (SELECT COUNT(*) FROM timetables t WHERE t.faculty_id = f.id) AS class_count,
           (SELECT COUNT(*) FROM timetables t JOIN conflicts c ON (c.timetable_id_1=t.id OR c.timetable_id_2=t.id)
                WHERE t.faculty_id = f.id AND c.conflict_type='faculty' AND c.status='open') AS conflict_count
    FROM faculty f
    JOIN departments d ON d.id = f.department_id
    $where
    ORDER BY f.name
");
$stmt->execute($params);
$facultyList = $stmt->fetchAll();

$pageTitle = 'Faculty';
$role = 'admin';
$activePage = 'faculty';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Faculty</h1>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addFacultyModal')"><i class="fa-solid fa-plus"></i> Add Faculty</button>
    </div>

    <div class="page-body">
        <form method="GET" class="mb-24" style="max-width:400px;">
            <div class="input-icon-wrap">
                <input type="text" name="q" class="form-control" placeholder="Search faculty..." value="<?= e($search) ?>">
                <button type="submit" class="icon-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <?php if (!$facultyList): ?>
            <div class="card"><div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-chalkboard-user"></i></div>
                <h3>No faculty yet</h3>
                <p>Faculty are created automatically on Excel import, or you can add one manually.</p>
                <button class="btn btn-primary" onclick="openModal('addFacultyModal')">Add Faculty</button>
            </div></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Name</th><th>Email</th><th>Department</th><th>Classes/Week</th><th>Conflicts</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($facultyList as $f): ?>
                    <tr>
                        <td><strong><?= e($f['name']) ?></strong></td>
                        <td><?= e($f['email'] ?: '—') ?></td>
                        <td><span class="badge badge-neutral"><?= e($f['dept_code']) ?></span></td>
                        <td><?= (int)$f['class_count'] ?></td>
                        <td><?= $f['conflict_count'] > 0 ? '<span class="badge badge-danger">' . (int)$f['conflict_count'] . '</span>' : '<span class="text-muted">0</span>' ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Remove this faculty member?');">
                                <input type="hidden" name="delete_id" value="<?= $f['id'] ?>">
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

<div class="modal-overlay" id="addFacultyModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add Faculty</h3>
            <button class="modal-close" onclick="closeModal('addFacultyModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Full Name</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g. Dr. Kapoor" required>
                </div>
                <div class="form-group">
                    <label>Email (optional)</label>
                    <input type="email" name="email" class="form-control" placeholder="kapoor@college.edu">
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-control" required>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addFacultyModal')">Cancel</button>
                <button type="submit" name="add_faculty" class="btn btn-primary">Add Faculty</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
