<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY semester_number")->fetchAll();

// ---- Filters ----
$deptFilter = $_GET['department_id'] ?? '';
$semFilter = $_GET['semester_id'] ?? '';
$dayFilter = $_GET['day'] ?? '';
$search = trim($_GET['q'] ?? '');
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 20;

$where = [];
$params = [];
if ($deptFilter !== '') { $where[] = 't.department_id = ?'; $params[] = $deptFilter; }
if ($semFilter !== '') { $where[] = 't.semester_id = ?'; $params[] = $semFilter; }
if ($dayFilter !== '') { $where[] = 't.day = ?'; $params[] = $dayFilter; }
if ($search !== '') {
    $where[] = '(sub.subject_name LIKE ? OR sub.subject_code LIKE ? OR f.name LIKE ? OR r.room_number LIKE ?)';
    array_push($params, "%$search%", "%$search%", "%$search%", "%$search%");
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(*) FROM timetables t
    JOIN subjects sub ON sub.id=t.subject_id JOIN faculty f ON f.id=t.faculty_id JOIN rooms r ON r.id=t.room_id
    $whereSql";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int) $countStmt->fetchColumn();
$totalPages = max(1, ceil($total / $perPage));
$offset = ($page - 1) * $perPage;

$sql = "
    SELECT t.*, sub.subject_name, sub.subject_code, f.name AS faculty_name, r.room_number,
           d.code AS dept_code, sem.semester_number, sec.section_name
    FROM timetables t
    JOIN subjects sub ON sub.id=t.subject_id
    JOIN faculty f ON f.id=t.faculty_id
    JOIN rooms r ON r.id=t.room_id
    JOIN departments d ON d.id=t.department_id
    JOIN semesters sem ON sem.id=t.semester_id
    JOIN sections sec ON sec.id=t.section_id
    $whereSql
    ORDER BY FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time
    LIMIT $perPage OFFSET $offset
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$records = $stmt->fetchAll();

$pageTitle = 'Timetables';
$role = 'admin';
$activePage = 'timetables';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>All Timetable Records</h1>
        </div>
        <span class="text-muted text-sm"><?= number_format($total) ?> total records</span>
    </div>

    <div class="page-body">
        <form method="GET" class="card card-pad mb-24">
            <div class="grid grid-4" style="gap:14px;">
                <div class="form-group" style="margin:0;">
                    <label>Search</label>
                    <input type="text" name="q" class="form-control" placeholder="Subject, faculty, room..." value="<?= e($search) ?>">
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>" <?= (string)$deptFilter===(string)$d['id']?'selected':'' ?>><?= e($d['code']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Semester</label>
                    <select name="semester_id" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= (string)$semFilter===(string)$s['id']?'selected':'' ?>>Sem <?= $s['semester_number'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Day</label>
                    <select name="day" class="form-control">
                        <option value="">All</option>
                        <?php foreach (DAYS_OF_WEEK as $d): ?>
                            <option value="<?= $d ?>" <?= $dayFilter===$d?'selected':'' ?>><?= $d ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex gap-12 mt-16">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a href="<?= BASE_URL ?>/admin/timetables.php" class="btn btn-outline btn-sm">Clear</a>
            </div>
        </form>

        <?php if (!$records): ?>
            <div class="card"><div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-table"></i></div>
                <h3>No records found</h3>
                <p>Try adjusting your filters, or upload a timetable to get started.</p>
            </div></div>
        <?php else: ?>
        <div class="table-wrap">
            <table class="data-table">
                <thead><tr><th>Day</th><th>Time</th><th>Subject</th><th>Faculty</th><th>Room</th><th>Dept/Sem/Sec</th></tr></thead>
                <tbody>
                <?php foreach ($records as $r): ?>
                    <tr>
                        <td><?= $r['day'] ?></td>
                        <td><?= date('g:i A', strtotime($r['start_time'])) ?> – <?= date('g:i A', strtotime($r['end_time'])) ?></td>
                        <td><strong><?= e($r['subject_code']) ?></strong> <?= e($r['subject_name']) ?></td>
                        <td><?= e($r['faculty_name']) ?></td>
                        <td><?= e($r['room_number']) ?></td>
                        <td><span class="badge badge-neutral"><?= e($r['dept_code']) ?> · S<?= $r['semester_number'] ?> · <?= e($r['section_name']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="flex gap-8 mt-24" style="justify-content:center;">
            <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
                   class="btn btn-sm <?= $p === $page ? 'btn-primary' : 'btn-outline' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
