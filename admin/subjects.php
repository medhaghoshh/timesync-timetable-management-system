<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY semester_number")->fetchAll();

// ---- Add subject ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_subject'])) {
    $code = strtoupper(trim($_POST['subject_code'] ?? ''));
    $name = trim($_POST['subject_name'] ?? '');
    $deptId = (int) ($_POST['department_id'] ?? 0);
    $semId = (int) ($_POST['semester_id'] ?? 0);

    if ($code === '' || $name === '' || !$deptId || !$semId) {
        setFlash('error', 'Please fill in all fields to add a subject.');
    } else {
        try {
            $ins = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, department_id, semester_id) VALUES (?,?,?,?)");
            $ins->execute([$code, $name, $deptId, $semId]);
            setFlash('success', 'Subject added successfully.');
        } catch (Throwable $e) {
            setFlash('error', 'A subject with this code already exists in that department.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/subjects.php');
    exit;
}

// ---- Delete subject ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $pdo->prepare("DELETE FROM subjects WHERE id = ?")->execute([(int) $_POST['delete_id']]);
        setFlash('success', 'Subject removed.');
    } catch (Throwable $e) {
        setFlash('error', 'This subject is used in existing timetable records and cannot be deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/subjects.php');
    exit;
}

$search = trim($_GET['q'] ?? '');
$where = '';
$params = [];
if ($search !== '') {
    $where = 'WHERE sub.subject_name LIKE ? OR sub.subject_code LIKE ?';
    $params = ["%$search%", "%$search%"];
}
$stmt = $pdo->prepare("
    SELECT sub.*, d.code AS dept_code, sem.semester_number,
           (SELECT COUNT(*) FROM timetables t WHERE t.subject_id = sub.id) AS usage_count
    FROM subjects sub
    JOIN departments d ON d.id = sub.department_id
    JOIN semesters sem ON sem.id = sub.semester_id
    $where
    ORDER BY sub.subject_name
");
$stmt->execute($params);
$subjects = $stmt->fetchAll();

$pageTitle = 'Subjects';
$role = 'admin';
$activePage = 'subjects';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Subjects</h1>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addSubjectModal')"><i class="fa-solid fa-plus"></i> Add Subject</button>
    </div>

    <div class="page-body">
        <form method="GET" class="mb-24" style="max-width:400px;">
            <div class="input-icon-wrap">
                <input type="text" name="q" class="form-control" placeholder="Search subject or code..." value="<?= e($search) ?>">
                <button type="submit" class="icon-btn"><i class="fa-solid fa-magnifying-glass"></i></button>
            </div>
        </form>

        <?php if (!$subjects): ?>
            <div class="card"><div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-book"></i></div>
                <h3>No subjects yet</h3>
                <p>Subjects are created automatically on Excel import, or you can add one manually.</p>
                <button class="btn btn-primary" onclick="openModal('addSubjectModal')">Add Subject</button>
            </div></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Code</th><th>Subject Name</th><th>Department</th><th>Semester</th><th>Used In</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($subjects as $s): ?>
                    <tr>
                        <td><strong><?= e($s['subject_code']) ?></strong></td>
                        <td><?= e($s['subject_name']) ?></td>
                        <td><span class="badge badge-neutral"><?= e($s['dept_code']) ?></span></td>
                        <td>Semester <?= (int)$s['semester_number'] ?></td>
                        <td><?= (int)$s['usage_count'] ?> classes</td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this subject?');">
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

<div class="modal-overlay" id="addSubjectModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add Subject</h3>
            <button class="modal-close" onclick="closeModal('addSubjectModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Subject Code</label>
                    <input type="text" name="subject_code" class="form-control" placeholder="e.g. CS509" required>
                </div>
                <div class="form-group">
                    <label>Subject Name</label>
                    <input type="text" name="subject_name" class="form-control" placeholder="e.g. Cloud Computing" required>
                </div>
                <div class="form-group">
                    <label>Department</label>
                    <select name="department_id" class="form-control" required>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Semester</label>
                    <select name="semester_id" class="form-control" required>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?= $s['id'] ?>">Semester <?= $s['semester_number'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addSubjectModal')">Cancel</button>
                <button type="submit" name="add_subject" class="btn btn-primary">Add Subject</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
