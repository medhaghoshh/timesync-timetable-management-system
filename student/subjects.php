<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$student = getStudentDetails($pdo, currentUserId());

$search = trim($_GET['q'] ?? '');
$sql = "
    SELECT DISTINCT sub.subject_code, sub.subject_name, GROUP_CONCAT(DISTINCT f.name SEPARATOR ', ') AS faculty_names,
           COUNT(t.id) AS class_count
    FROM timetables t
    JOIN subjects sub ON sub.id = t.subject_id
    JOIN faculty f ON f.id = t.faculty_id
    WHERE t.section_id = ?
";
$params = [$student['section_id']];
if ($search !== '') {
    $sql .= " AND (sub.subject_name LIKE ? OR sub.subject_code LIKE ?)";
    $params[] = "%$search%"; $params[] = "%$search%";
}
$sql .= " GROUP BY sub.id ORDER BY sub.subject_name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$subjects = $stmt->fetchAll();

$pageTitle = 'Subjects';
$role = 'student';
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
                <h3>No subjects found</h3>
                <p>Try a different search term, or check back once your timetable is uploaded.</p>
            </div></div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($subjects as $s): ?>
                    <div class="card card-pad card-hover">
                        <span class="badge badge-neutral mb-16"><?= e($s['subject_code']) ?></span>
                        <h3 style="font-size:1rem;margin-bottom:8px;"><?= e($s['subject_name']) ?></h3>
                        <p class="text-sm"><i class="fa-solid fa-chalkboard-user"></i> <?= e($s['faculty_names']) ?></p>
                        <p class="text-sm"><i class="fa-solid fa-calendar-day"></i> <?= (int)$s['class_count'] ?> classes/week</p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
