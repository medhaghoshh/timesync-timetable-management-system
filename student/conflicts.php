<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$student = getStudentDetails($pdo, currentUserId());

$stmt = $pdo->prepare("
    SELECT c.*,
           t1.day AS day1, t1.start_time AS start1, t1.end_time AS end1, s1.subject_name AS subject1,
           t2.day AS day2, t2.start_time AS start2, t2.end_time AS end2, s2.subject_name AS subject2
    FROM conflicts c
    JOIN timetables t1 ON t1.id = c.timetable_id_1
    JOIN timetables t2 ON t2.id = c.timetable_id_2
    JOIN subjects s1 ON s1.id = t1.subject_id
    JOIN subjects s2 ON s2.id = t2.subject_id
    WHERE c.status = 'open' AND (t1.section_id = ? OR t2.section_id = ?)
    ORDER BY FIELD(c.severity,'HIGH','MEDIUM','LOW')
");
$stmt->execute([$student['section_id'], $student['section_id']]);
$conflicts = $stmt->fetchAll();

$pageTitle = 'Conflicts';
$role = 'student';
$activePage = 'conflicts';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Timetable Conflicts</h1>
        </div>
    </div>
    <div class="page-body">
        <?php if (!$conflicts): ?>
            <div class="card"><div class="empty-state">
                <div class="empty-icon" style="color:var(--success);"><i class="fa-solid fa-circle-check"></i></div>
                <h3>No conflicts detected</h3>
                <p>Everything looks good. Your section's timetable has no overlapping classes right now.</p>
            </div></div>
        <?php else: ?>
            <p class="text-muted mb-16"><?= count($conflicts) ?> conflict(s) currently affect your section.</p>
            <?php foreach ($conflicts as $c):
                $sevClass = ['HIGH'=>'danger','MEDIUM'=>'warning','LOW'=>'muted'][$c['severity']];
            ?>
                <div class="card card-pad mb-16">
                    <div class="flex-between mb-8">
                        <span class="badge badge-<?= $sevClass ?>"><?= $c['severity'] ?> · <?= ucfirst($c['conflict_type']) ?> Conflict</span>
                    </div>
                    <p style="color:var(--text);margin-bottom:10px;"><?= e($c['description']) ?></p>
                    <div class="grid grid-2" style="gap:12px;">
                        <div class="card-pad" style="background:var(--background);border-radius:8px;">
                            <strong style="font-size:0.85rem;"><?= e($c['subject1']) ?></strong>
                            <p class="text-sm" style="margin:4px 0 0;"><?= $c['day1'] ?>, <?= date('g:i A', strtotime($c['start1'])) ?>–<?= date('g:i A', strtotime($c['end1'])) ?></p>
                        </div>
                        <div class="card-pad" style="background:var(--background);border-radius:8px;">
                            <strong style="font-size:0.85rem;"><?= e($c['subject2']) ?></strong>
                            <p class="text-sm" style="margin:4px 0 0;"><?= $c['day2'] ?>, <?= date('g:i A', strtotime($c['start2'])) ?>–<?= date('g:i A', strtotime($c['end2'])) ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
