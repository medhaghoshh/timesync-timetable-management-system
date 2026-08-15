<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$stats = getDashboardStatistics($pdo);

$recentFiles = $pdo->query("SELECT * FROM uploaded_files ORDER BY uploaded_at DESC LIMIT 5")->fetchAll();
$recentConflicts = $pdo->query("
    SELECT c.*, s1.subject_name AS subject1, s2.subject_name AS subject2
    FROM conflicts c
    JOIN timetables t1 ON t1.id = c.timetable_id_1 JOIN subjects s1 ON s1.id = t1.subject_id
    JOIN timetables t2 ON t2.id = c.timetable_id_2 JOIN subjects s2 ON s2.id = t2.subject_id
    WHERE c.status='open' ORDER BY c.created_at DESC LIMIT 5
")->fetchAll();

$pageTitle = 'Dashboard';
$role = 'admin';
$activePage = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Admin Dashboard</h1>
        </div>
        <a href="<?= BASE_URL ?>/admin/upload.php" class="btn btn-primary btn-sm"><i class="fa-solid fa-cloud-arrow-up"></i> Upload Timetable</a>
    </div>

    <div class="page-body">
        <div class="grid grid-4 mb-24">
            <div class="card stat-card">
                <div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-user-graduate"></i></span>Students</div>
                <div class="stat-value"><?= number_format($stats['total_students']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-table"></i></span>Timetable Records</div>
                <div class="stat-value"><?= number_format($stats['total_records']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-file-excel"></i></span>Files Processed</div>
                <div class="stat-value"><?= number_format($stats['files_processed']) ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-label"><span class="stat-icon" style="background:<?= $stats['open_conflicts']>0?'var(--danger-light)':'var(--success-light)' ?>;color:<?= $stats['open_conflicts']>0?'var(--danger)':'var(--success)' ?>;"><i class="fa-solid fa-triangle-exclamation"></i></span>Conflicts</div>
                <div class="stat-value"><?= number_format($stats['open_conflicts']) ?></div>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card card-pad">
                <div class="flex-between mb-16">
                    <h3 class="section-title" style="margin:0;">Recent Uploads</h3>
                    <a href="<?= BASE_URL ?>/admin/upload.php" class="text-sm" style="color:var(--primary);font-weight:600;">Upload more →</a>
                </div>
                <?php if (!$recentFiles): ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-solid fa-file-excel"></i></div>
                        <h3>No files uploaded yet</h3>
                        <p>Upload your first timetable spreadsheet to get started.</p>
                    </div>
                <?php else: foreach ($recentFiles as $f): ?>
                    <div class="flex-between" style="padding:10px 0;border-bottom:1px dashed var(--border);">
                        <div>
                            <div style="font-weight:600;font-size:0.9rem;"><?= e($f['file_name']) ?></div>
                            <div class="text-sm text-muted"><?= (int)$f['records_imported'] ?> imported · <?= date('d M, h:i A', strtotime($f['uploaded_at'])) ?></div>
                        </div>
                        <?php if ($f['status'] === 'completed'): ?>
                            <span class="badge badge-success"><i class="fa-solid fa-check"></i> Processed</span>
                        <?php elseif ($f['status'] === 'failed'): ?>
                            <span class="badge badge-danger">Failed</span>
                        <?php else: ?>
                            <span class="badge badge-muted">Processing</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; endif; ?>
            </div>

            <div class="card card-pad">
                <div class="flex-between mb-16">
                    <h3 class="section-title" style="margin:0;">Latest Conflicts</h3>
                    <a href="<?= BASE_URL ?>/admin/conflicts.php" class="text-sm" style="color:var(--primary);font-weight:600;">View all →</a>
                </div>
                <?php if (!$recentConflicts): ?>
                    <div class="empty-state">
                        <div class="empty-icon" style="color:var(--success);"><i class="fa-solid fa-circle-check"></i></div>
                        <h3>No conflicts detected</h3>
                        <p>Everything looks good across all uploaded timetables.</p>
                    </div>
                <?php else: foreach ($recentConflicts as $c):
                    $sevClass = ['HIGH'=>'danger','MEDIUM'=>'warning','LOW'=>'muted'][$c['severity']];
                ?>
                    <div style="padding:10px 0;border-bottom:1px dashed var(--border);">
                        <span class="badge badge-<?= $sevClass ?>"><?= $c['severity'] ?> · <?= ucfirst($c['conflict_type']) ?></span>
                        <p class="text-sm mt-8" style="margin:8px 0 0;color:var(--text);"><?= e($c['subject1']) ?> vs <?= e($c['subject2']) ?></p>
                    </div>
                <?php endforeach; endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
