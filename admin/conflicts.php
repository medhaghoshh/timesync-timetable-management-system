<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rescan'])) {
    runAllConflictDetection($pdo);
    setFlash('success', 'Conflict scan complete. Any new conflicts have been logged.');
    header('Location: ' . BASE_URL . '/admin/conflicts.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['resolve_id'])) {
    $stmt = $pdo->prepare("UPDATE conflicts SET status='resolved' WHERE id=?");
    $stmt->execute([(int)$_POST['resolve_id']]);
    setFlash('success', 'Conflict marked as resolved.');
    header('Location: ' . BASE_URL . '/admin/conflicts.php?' . http_build_query($_GET));
    exit;
}

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY semester_number")->fetchAll();
$sections = $pdo->query("SELECT sec.*, d.code AS dept_code FROM sections sec JOIN departments d ON d.id=sec.department_id ORDER BY d.code, sec.section_name")->fetchAll();

$typeFilter = $_GET['type'] ?? '';
$sevFilter = $_GET['severity'] ?? '';
$deptFilter = $_GET['department_id'] ?? '';
$semFilter = $_GET['semester_id'] ?? '';
$secFilter = $_GET['section_id'] ?? '';

$where = ["c.status = 'open'"];
$params = [];
if ($typeFilter) { $where[] = 'c.conflict_type = ?'; $params[] = $typeFilter; }
if ($sevFilter) { $where[] = 'c.severity = ?'; $params[] = $sevFilter; }
if ($deptFilter) { $where[] = '(t1.department_id = ? OR t2.department_id = ?)'; $params[] = $deptFilter; $params[] = $deptFilter; }
if ($semFilter) { $where[] = '(t1.semester_id = ? OR t2.semester_id = ?)'; $params[] = $semFilter; $params[] = $semFilter; }
if ($secFilter) { $where[] = '(t1.section_id = ? OR t2.section_id = ?)'; $params[] = $secFilter; $params[] = $secFilter; }
$whereSql = implode(' AND ', $where);

$sql = "
    SELECT c.*, t1.day AS day1, t1.start_time AS start1, t1.end_time AS end1, s1.subject_name AS subject1, sec1.section_name AS sec1,
           t2.day AS day2, t2.start_time AS start2, t2.end_time AS end2, s2.subject_name AS subject2, sec2.section_name AS sec2
    FROM conflicts c
    JOIN timetables t1 ON t1.id = c.timetable_id_1 JOIN subjects s1 ON s1.id = t1.subject_id JOIN sections sec1 ON sec1.id=t1.section_id
    JOIN timetables t2 ON t2.id = c.timetable_id_2 JOIN subjects s2 ON s2.id = t2.subject_id JOIN sections sec2 ON sec2.id=t2.section_id
    WHERE $whereSql
    ORDER BY FIELD(c.severity,'HIGH','MEDIUM','LOW'), c.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$conflicts = $stmt->fetchAll();

$counts = $pdo->query("SELECT severity, COUNT(*) c FROM conflicts WHERE status='open' GROUP BY severity")->fetchAll(PDO::FETCH_KEY_PAIR);

$pageTitle = 'Conflicts';
$role = 'admin';
$activePage = 'conflicts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Conflict Reports</h1>
        </div>
        <form method="POST"><button type="submit" name="rescan" class="btn btn-outline btn-sm"><i class="fa-solid fa-arrows-rotate"></i> Re-scan All</button></form>
    </div>

    <div class="page-body">
        <div class="grid grid-3 mb-24">
            <div class="card stat-card"><div class="stat-label"><span class="stat-icon" style="background:var(--danger-light);color:var(--danger);"><i class="fa-solid fa-circle"></i></span>High Severity</div><div class="stat-value"><?= (int)($counts['HIGH'] ?? 0) ?></div></div>
            <div class="card stat-card"><div class="stat-label"><span class="stat-icon" style="background:var(--warning-light);color:var(--warning);"><i class="fa-solid fa-circle"></i></span>Medium Severity</div><div class="stat-value"><?= (int)($counts['MEDIUM'] ?? 0) ?></div></div>
            <div class="card stat-card"><div class="stat-label"><span class="stat-icon" style="background:#F3F4F6;color:var(--muted);"><i class="fa-solid fa-circle"></i></span>Low Severity</div><div class="stat-value"><?= (int)($counts['LOW'] ?? 0) ?></div></div>
        </div>

        <form method="GET" class="card card-pad mb-24">
            <div class="grid grid-4" style="gap:14px;">
                <div class="form-group" style="margin:0;">
                    <label>Type</label>
                    <select name="type" class="form-control">
                        <option value="">All Types</option>
                        <option value="section" <?= $typeFilter==='section'?'selected':'' ?>>Section</option>
                        <option value="faculty" <?= $typeFilter==='faculty'?'selected':'' ?>>Faculty</option>
                        <option value="room" <?= $typeFilter==='room'?'selected':'' ?>>Room</option>
                        <option value="duplicate" <?= $typeFilter==='duplicate'?'selected':'' ?>>Duplicate</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Severity</label>
                    <select name="severity" class="form-control">
                        <option value="">All Severities</option>
                        <option value="HIGH" <?= $sevFilter==='HIGH'?'selected':'' ?>>High</option>
                        <option value="MEDIUM" <?= $sevFilter==='MEDIUM'?'selected':'' ?>>Medium</option>
                        <option value="LOW" <?= $sevFilter==='LOW'?'selected':'' ?>>Low</option>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Department</label>
                    <select name="department_id" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($departments as $d): ?><option value="<?= $d['id'] ?>" <?= (string)$deptFilter===(string)$d['id']?'selected':'' ?>><?= e($d['code']) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin:0;">
                    <label>Section</label>
                    <select name="section_id" class="form-control">
                        <option value="">All</option>
                        <?php foreach ($sections as $s): ?><option value="<?= $s['id'] ?>" <?= (string)$secFilter===(string)$s['id']?'selected':'' ?>><?= e($s['dept_code']) ?>-<?= e($s['section_name']) ?></option><?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="flex gap-12 mt-16">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-filter"></i> Apply Filters</button>
                <a href="<?= BASE_URL ?>/admin/conflicts.php" class="btn btn-outline btn-sm">Clear</a>
            </div>
        </form>

        <?php if (!$conflicts): ?>
            <div class="card"><div class="empty-state">
                <div class="empty-icon" style="color:var(--success);"><i class="fa-solid fa-circle-check"></i></div>
                <h3>No conflicts detected</h3>
                <p>Everything looks good. Try re-scanning after uploading new timetables.</p>
            </div></div>
        <?php else: foreach ($conflicts as $c):
            $sevClass = ['HIGH'=>'danger','MEDIUM'=>'warning','LOW'=>'muted'][$c['severity']];
        ?>
            <div class="card card-pad mb-16">
                <div class="flex-between mb-8">
                    <span class="badge badge-<?= $sevClass ?>"><?= $c['severity'] ?> · <?= ucfirst($c['conflict_type']) ?> Conflict</span>
                    <form method="POST" onsubmit="return confirm('Mark this conflict as resolved?');">
                        <input type="hidden" name="resolve_id" value="<?= $c['id'] ?>">
                        <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-check"></i> Mark Resolved</button>
                    </form>
                </div>
                <p style="color:var(--text);margin-bottom:10px;"><?= e($c['description']) ?></p>
                <div class="grid grid-2" style="gap:12px;">
                    <div class="card-pad" style="background:var(--background);border-radius:8px;">
                        <strong style="font-size:0.85rem;"><?= e($c['subject1']) ?></strong>
                        <p class="text-sm" style="margin:4px 0 0;">Section <?= e($c['sec1']) ?> · <?= $c['day1'] ?>, <?= date('g:i A', strtotime($c['start1'])) ?>–<?= date('g:i A', strtotime($c['end1'])) ?></p>
                    </div>
                    <div class="card-pad" style="background:var(--background);border-radius:8px;">
                        <strong style="font-size:0.85rem;"><?= e($c['subject2']) ?></strong>
                        <p class="text-sm" style="margin:4px 0 0;">Section <?= e($c['sec2']) ?> · <?= $c['day2'] ?>, <?= date('g:i A', strtotime($c['start2'])) ?>–<?= date('g:i A', strtotime($c['end2'])) ?></p>
                    </div>
                </div>
            </div>
        <?php endforeach; endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
