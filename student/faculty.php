<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$student = getStudentDetails($pdo, currentUserId());
$search = trim($_GET['q'] ?? '');

$sql = "
    SELECT DISTINCT f.id, f.name, f.email, GROUP_CONCAT(DISTINCT sub.subject_name SEPARATOR ', ') AS subjects
    FROM timetables t
    JOIN faculty f ON f.id = t.faculty_id
    JOIN subjects sub ON sub.id = t.subject_id
    WHERE t.section_id = ?
";
$params = [$student['section_id']];
if ($search !== '') {
    $sql .= " AND f.name LIKE ?";
    $params[] = "%$search%";
}
$sql .= " GROUP BY f.id ORDER BY f.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$facultyList = $stmt->fetchAll();

$pageTitle = 'Faculty';
$role = 'student';
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
                <h3>No faculty found</h3>
                <p>Try a different search term.</p>
            </div></div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($facultyList as $f): ?>
                    <div class="card card-pad card-hover">
                        <div class="flex gap-12" style="align-items:center;margin-bottom:12px;">
                            <div class="stat-icon" style="border-radius:50%;"><i class="fa-solid fa-user"></i></div>
                            <div>
                                <h3 style="font-size:0.95rem;"><?= e($f['name']) ?></h3>
                                <p class="text-sm" style="margin:0;"><?= e($f['email'] ?: 'No email on file') ?></p>
                            </div>
                        </div>
                        <p class="text-sm"><i class="fa-solid fa-book"></i> <?= e($f['subjects']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
