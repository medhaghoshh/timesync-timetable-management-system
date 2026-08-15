<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY semester_number")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_section'])) {
    $deptId = (int) ($_POST['department_id'] ?? 0);
    $semId = (int) ($_POST['semester_id'] ?? 0);
    $name = strtoupper(trim($_POST['section_name'] ?? ''));

    if (!$deptId || !$semId || $name === '') {
        setFlash('error', 'Please fill in all fields to add a section.');
    } else {
        try {
            $ins = $pdo->prepare("INSERT INTO sections (department_id, semester_id, section_name) VALUES (?,?,?)");
            $ins->execute([$deptId, $semId, $name]);
            setFlash('success', 'Section added successfully.');
        } catch (Throwable $e) {
            setFlash('error', 'This section already exists for that department and semester.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/sections.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $pdo->prepare("DELETE FROM sections WHERE id = ?")->execute([(int) $_POST['delete_id']]);
        setFlash('success', 'Section removed.');
    } catch (Throwable $e) {
        setFlash('error', 'This section has students or timetable records and cannot be deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/sections.php');
    exit;
}

$sections = $pdo->query("
    SELECT sec.*, d.code AS dept_code, sem.semester_number,
           (SELECT COUNT(*) FROM students st WHERE st.section_id = sec.id) AS student_count,
           (SELECT COUNT(*) FROM timetables t WHERE t.section_id = sec.id) AS class_count
    FROM sections sec
    JOIN departments d ON d.id = sec.department_id
    JOIN semesters sem ON sem.id = sec.semester_id
    ORDER BY d.code, sem.semester_number, sec.section_name
")->fetchAll();

$pageTitle = 'Sections';
$role = 'admin';
$activePage = 'sections';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Sections</h1>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addSectionModal')"><i class="fa-solid fa-plus"></i> Add Section</button>
    </div>

    <div class="page-body">
        <?php if (!$sections): ?>
            <div class="card"><div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-layer-group"></i></div>
                <h3>No sections yet</h3>
                <p>Sections are created automatically when students register or timetables are imported.</p>
                <button class="btn btn-primary" onclick="openModal('addSectionModal')">Add Section</button>
            </div></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Department</th><th>Semester</th><th>Section</th><th>Students</th><th>Classes/Week</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($sections as $s): ?>
                    <tr>
                        <td><span class="badge badge-neutral"><?= e($s['dept_code']) ?></span></td>
                        <td>Semester <?= (int)$s['semester_number'] ?></td>
                        <td><strong><?= e($s['section_name']) ?></strong></td>
                        <td><?= (int)$s['student_count'] ?></td>
                        <td><?= (int)$s['class_count'] ?></td>
                        <td>
                            <form method="POST" onsubmit="return confirm('Delete this section?');">
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

<div class="modal-overlay" id="addSectionModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add Section</h3>
            <button class="modal-close" onclick="closeModal('addSectionModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
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
                <div class="form-group">
                    <label>Section Name</label>
                    <input type="text" name="section_name" class="form-control" placeholder="e.g. D" maxlength="5" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addSectionModal')">Cancel</button>
                <button type="submit" name="add_section" class="btn btn-primary">Add Section</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
