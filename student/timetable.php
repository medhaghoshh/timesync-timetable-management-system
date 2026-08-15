<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('student');

$student = getStudentDetails($pdo, currentUserId());
$timetable = getStudentTimetable($pdo, (int)$student['section_id']);

// Group by day for the grid + mobile view
$byDay = array_fill_keys(DAYS_OF_WEEK, []);
foreach ($timetable as $class) {
    $byDay[$class['day']][] = $class;
}

$GRID_START_MIN = 8 * 60;  // 08:00
$GRID_END_MIN = 18 * 60;   // 18:00
$HOUR_PX = 64;

function minutesFromMidnight(string $time): int {
    [$h, $m] = explode(':', $time);
    return ((int)$h) * 60 + (int)$m;
}

$pageTitle = 'My Timetable';
$role = 'student';
$activePage = 'timetable';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar no-print">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>My Weekly Timetable</h1>
        </div>
        <button class="btn btn-outline btn-sm" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
    </div>

    <div class="page-body">

        <!-- Print header (only visible when printing) -->
        <div style="display:none;" class="print-only">
            <h2><?= e($student['name']) ?> — <?= e($student['roll_number']) ?></h2>
            <p><?= e($student['department_name']) ?> · Semester <?= (int)$student['semester_number'] ?> · Section <?= e($student['section_name']) ?></p>
        </div>

        <?php if (!$timetable): ?>
            <div class="card">
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-regular fa-calendar-xmark"></i></div>
                    <h3>No timetable available</h3>
                    <p>Your timetable hasn't been generated yet. Please check back once the administrator uploads your section's schedule.</p>
                    <button class="btn btn-outline" onclick="location.reload()">Refresh</button>
                </div>
            </div>
        <?php else: ?>

        <!-- ============ DESKTOP GRID ============ -->
        <div class="card card-pad desktop-timetable">
            <div class="timetable-grid-wrap">
                <div class="timetable-grid" style="grid-template-rows:auto repeat(10, <?= $HOUR_PX ?>px);">
                    <div class="tt-head"></div>
                    <?php foreach (DAYS_OF_WEEK as $day): ?>
                        <div class="tt-head"><?= $day ?></div>
                    <?php endforeach; ?>

                    <?php for ($h = 8; $h < 18; $h++): ?>
                        <div class="tt-time"><?= date('g A', mktime($h)) ?></div>
                        <?php foreach (DAYS_OF_WEEK as $day): ?>
                            <div class="tt-cell"></div>
                        <?php endforeach; ?>
                    <?php endfor; ?>
                </div>

                <!-- Overlay classes absolutely on top of the grid using computed offsets -->
                <div style="position:relative;margin-top:-<?= 10 * $HOUR_PX ?>px;margin-left:70px;display:grid;grid-template-columns:repeat(6,1fr);min-width:910px;pointer-events:none;">
                    <?php foreach (DAYS_OF_WEEK as $day): ?>
                        <div style="position:relative;pointer-events:none;">
                            <?php foreach ($byDay[$day] as $class):
                                $start = minutesFromMidnight($class['start_time']);
                                $end = minutesFromMidnight($class['end_time']);
                                $top = max(0, ($start - $GRID_START_MIN)) / 60 * $HOUR_PX;
                                $height = max(30, ($end - $start) / 60 * $HOUR_PX) - 4;
                            ?>
                                <div class="tt-class-card" style="position:absolute;top:<?= $top + 2 ?>px;height:<?= $height ?>px;left:2px;right:2px;pointer-events:auto;"
                                     onclick='openClassModal(<?= json_encode($class) ?>)'>
                                    <div class="tt-subject"><?= e($class['subject_code']) ?></div>
                                    <div class="tt-meta"><?= date('g:i A', strtotime($class['start_time'])) ?>–<?= date('g:i A', strtotime($class['end_time'])) ?></div>
                                    <div class="tt-meta"><i class="fa-solid fa-door-open"></i> <?= e($class['room_number']) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- ============ MOBILE DAILY TIMELINE ============ -->
        <div class="mobile-timetable card card-pad">
            <div class="day-tabs" id="dayTabs">
                <?php foreach (DAYS_OF_WEEK as $i => $day): ?>
                    <button class="day-tab <?= $i === 0 ? 'active' : '' ?>" data-day="<?= $day ?>" onclick="switchDay('<?= $day ?>', this)"><?= substr($day, 0, 3) ?></button>
                <?php endforeach; ?>
            </div>
            <?php foreach (DAYS_OF_WEEK as $i => $day): ?>
                <div class="day-panel" data-day-panel="<?= $day ?>" style="<?= $i === 0 ? '' : 'display:none;' ?>">
                    <?php if (!$byDay[$day]): ?>
                        <div class="empty-state">
                            <div class="empty-icon"><i class="fa-regular fa-calendar"></i></div>
                            <h3>No classes</h3>
                            <p>Nothing scheduled for <?= $day ?>.</p>
                        </div>
                    <?php else: ?>
                        <div class="timeline">
                            <?php foreach ($byDay[$day] as $class): ?>
                                <div class="timeline-item" onclick='openClassModal(<?= json_encode($class) ?>)' style="cursor:pointer;">
                                    <div class="timeline-time"><?= date('g:i A', strtotime($class['start_time'])) ?> – <?= date('g:i A', strtotime($class['end_time'])) ?></div>
                                    <div class="timeline-card">
                                        <div class="tc-subject"><?= e($class['subject_name']) ?></div>
                                        <div class="tc-meta"><span><i class="fa-solid fa-door-open"></i> <?= e($class['room_number']) ?></span><span><i class="fa-solid fa-chalkboard-user"></i> <?= e($class['faculty_name']) ?></span></div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Class detail modal -->
<div class="modal-overlay" id="classModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="cmSubject">Class Details</h3>
            <button class="modal-close" onclick="closeModal('classModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="modal-body">
            <div class="modal-row"><span>Subject Code</span><span id="cmCode"></span></div>
            <div class="modal-row"><span>Faculty</span><span id="cmFaculty"></span></div>
            <div class="modal-row"><span>Room</span><span id="cmRoom"></span></div>
            <div class="modal-row"><span>Day</span><span id="cmDay"></span></div>
            <div class="modal-row"><span>Start Time</span><span id="cmStart"></span></div>
            <div class="modal-row"><span>End Time</span><span id="cmEnd"></span></div>
            <div class="modal-row"><span>Section</span><span id="cmSection"></span></div>
        </div>
    </div>
</div>

<style>
    @media (max-width: 900px) { .desktop-timetable { display: none; } }
    @media (min-width: 901px) { .mobile-timetable { display: none; } }
    @media print { .mobile-timetable { display: none !important; } .print-only { display: block !important; margin-bottom:16px; } }
</style>

<?php $extraScripts = ['/assets/js/timetable.js']; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
