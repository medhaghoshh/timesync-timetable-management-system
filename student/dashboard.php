<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$student = getStudentDetails($pdo, currentUserId());
if (!$student) { die('Student profile not found.'); }

$stats = getStudentDashboardStats($pdo, $student);
$hour = (int) date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');

$pageTitle = 'Dashboard';
$role = 'student';
$activePage = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1><?= $greeting ?>, <?= e(explode(' ', $student['name'])[0]) ?></h1>
        </div>
        <span class="badge badge-neutral"><?= e($student['department_code']) ?> · Sem <?= (int)$student['semester_number'] ?> · Sec <?= e($student['section_name']) ?></span>
    </div>

    <div class="page-body">
        <div class="grid grid-4 mb-24">
            <div class="card stat-card">
                <div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-calendar-day"></i></span>Today's Classes</div>
                <div class="stat-value"><?= $stats['today_count'] ?></div>
                <div class="stat-sub"><?= $stats['today_count'] === 0 ? 'No classes today' : 'scheduled today' ?></div>
            </div>
            <div class="card stat-card">
                <div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-hourglass-half"></i></span>Next Class</div>
                <?php if ($stats['next_class']): ?>
                    <div class="stat-value" style="font-size:1.3rem;"><?= e($stats['next_class']['subject_name']) ?></div>
                    <div class="stat-sub"><?= date('h:i A', strtotime($stats['next_class']['start_time'])) ?></div>
                <?php else: ?>
                    <div class="stat-value" style="font-size:1.3rem;">—</div>
                    <div class="stat-sub">Nothing left today</div>
                <?php endif; ?>
            </div>
            <div class="card stat-card">
                <div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-book"></i></span>Subjects</div>
                <div class="stat-value"><?= $stats['subject_count'] ?></div>
                <div class="stat-sub">this semester</div>
            </div>
            <div class="card stat-card">
                <div class="stat-label"><span class="stat-icon" style="background:<?= $stats['conflict_count'] > 0 ? 'var(--danger-light)' : 'var(--success-light)' ?>;color:<?= $stats['conflict_count'] > 0 ? 'var(--danger)' : 'var(--success)' ?>;"><i class="fa-solid fa-triangle-exclamation"></i></span>Conflicts</div>
                <div class="stat-value"><?= $stats['conflict_count'] ?></div>
                <div class="stat-sub"><?= $stats['conflict_count'] > 0 ? 'affecting your section' : 'all clear' ?></div>
            </div>
        </div>

        <div class="grid grid-2">
            <div class="card card-pad">
                <div class="flex-between mb-16">
                    <h3 class="section-title" style="margin:0;">Today's Schedule</h3>
                    <a href="<?= BASE_URL ?>/student/today.php" class="text-sm" style="color:var(--primary);font-weight:600;">View all →</a>
                </div>
                <?php
                $today = getTodayClasses($pdo, (int)$student['section_id']);
                if (!$today):
                ?>
                    <div class="empty-state">
                        <div class="empty-icon"><i class="fa-regular fa-calendar"></i></div>
                        <h3>No classes today</h3>
                        <p>Enjoy the free day — check tomorrow's schedule in your weekly timetable.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline">
                        <?php foreach (array_slice($today, 0, 4) as $class):
                            $status = classifyClassStatus($class['start_time'], $class['end_time']); ?>
                            <div class="timeline-item <?= $status ?>">
                                <div class="timeline-time"><?= date('h:i A', strtotime($class['start_time'])) ?> – <?= date('h:i A', strtotime($class['end_time'])) ?></div>
                                <div class="timeline-card">
                                    <div class="tc-subject"><?= e($class['subject_name']) ?></div>
                                    <div class="tc-meta">
                                        <span><i class="fa-solid fa-door-open"></i> <?= e($class['room_number']) ?></span>
                                        <span><i class="fa-solid fa-chalkboard-user"></i> <?= e($class['faculty_name']) ?></span>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="card card-pad">
                <h3 class="section-title mb-16">Your Profile</h3>
                <div class="modal-row"><span>Name</span><span><?= e($student['name']) ?></span></div>
                <div class="modal-row"><span>Roll Number</span><span><?= e($student['roll_number']) ?></span></div>
                <div class="modal-row"><span>Email</span><span><?= e($student['email']) ?></span></div>
                <div class="modal-row"><span>Department</span><span><?= e($student['department_name']) ?></span></div>
                <div class="modal-row"><span>Semester</span><span>Semester <?= (int)$student['semester_number'] ?></span></div>
                <div class="modal-row"><span>Section</span><span><?= e($student['section_name']) ?></span></div>
                <a href="<?= BASE_URL ?>/student/timetable.php" class="btn btn-primary btn-block mt-16"><i class="fa-solid fa-calendar-week"></i> View Full Timetable</a>
            </div>
        </div>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
