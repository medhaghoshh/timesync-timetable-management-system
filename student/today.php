<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$student = getStudentDetails($pdo, currentUserId());
$today = getTodayClasses($pdo, (int)$student['section_id']);
$dayName = date('l');

$pageTitle = "Today's Classes";
$role = 'student';
$activePage = 'today';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Today's Classes</h1>
        </div>
        <span class="badge badge-neutral"><?= $dayName ?>, <?= date('d M Y') ?></span>
    </div>

    <div class="page-body">
        <div class="card card-pad" style="max-width:680px;">
            <?php if (!in_array($dayName, DAYS_OF_WEEK, true)): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-regular fa-face-smile"></i></div>
                    <h3>It's the weekend</h3>
                    <p>No classes are scheduled for today.</p>
                </div>
            <?php elseif (!$today): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-regular fa-calendar"></i></div>
                    <h3>No classes today</h3>
                    <p>Your section has no classes scheduled for <?= $dayName ?>.</p>
                    <button class="btn btn-outline" onclick="location.reload()">Refresh</button>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach ($today as $class):
                        $status = classifyClassStatus($class['start_time'], $class['end_time']);
                        $badge = ['current' => ['Now', 'success'], 'upcoming' => ['Upcoming', 'neutral'], 'completed' => ['Done', 'muted']][$status];
                    ?>
                        <div class="timeline-item <?= $status ?>">
                            <div class="timeline-time"><?= date('h:i A', strtotime($class['start_time'])) ?> – <?= date('h:i A', strtotime($class['end_time'])) ?></div>
                            <div class="timeline-card">
                                <div class="flex-between">
                                    <div class="tc-subject"><?= e($class['subject_name']) ?> <span class="text-muted text-sm">(<?= e($class['subject_code']) ?>)</span></div>
                                    <span class="badge badge-<?= $badge[1] ?>"><?= $badge[0] ?></span>
                                </div>
                                <div class="tc-meta mt-8">
                                    <span><i class="fa-solid fa-door-open"></i> <?= e($class['room_number']) ?></span>
                                    <span><i class="fa-solid fa-chalkboard-user"></i> <?= e($class['faculty_name']) ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
