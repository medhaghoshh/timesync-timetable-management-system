<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

// Records by department
$byDept = $pdo->query("
    SELECT d.name, d.code, COUNT(t.id) AS total
    FROM departments d LEFT JOIN timetables t ON t.department_id = d.id
    GROUP BY d.id ORDER BY total DESC
")->fetchAll();

// Classes by day
$byDay = $pdo->query("
    SELECT day, COUNT(*) AS total FROM timetables GROUP BY day
")->fetchAll(PDO::FETCH_KEY_PAIR);
$dayCounts = [];
foreach (DAYS_OF_WEEK as $d) $dayCounts[$d] = (int) ($byDay[$d] ?? 0);

// Most used rooms
$topRooms = $pdo->query("
    SELECT r.room_number, COUNT(t.id) AS total
    FROM rooms r JOIN timetables t ON t.room_id = r.id
    GROUP BY r.id ORDER BY total DESC LIMIT 6
")->fetchAll();

// Faculty workload
$facultyLoad = $pdo->query("
    SELECT f.name, COUNT(t.id) AS total
    FROM faculty f JOIN timetables t ON t.faculty_id = f.id
    GROUP BY f.id ORDER BY total DESC LIMIT 8
")->fetchAll();

// Conflicts by type
$conflictsByType = $pdo->query("
    SELECT conflict_type, COUNT(*) AS total FROM conflicts WHERE status='open' GROUP BY conflict_type
")->fetchAll(PDO::FETCH_KEY_PAIR);

$stats = getDashboardStatistics($pdo);

$pageTitle = 'Analytics';
$role = 'admin';
$activePage = 'analytics';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Analytics</h1>
        </div>
    </div>

    <div class="page-body">
        <div class="grid grid-4 mb-24">
            <div class="card stat-card"><div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-table"></i></span>Total Records</div><div class="stat-value"><?= number_format($stats['total_records']) ?></div></div>
            <div class="card stat-card"><div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-user-graduate"></i></span>Students</div><div class="stat-value"><?= number_format($stats['total_students']) ?></div></div>
            <div class="card stat-card"><div class="stat-label"><span class="stat-icon"><i class="fa-solid fa-file-excel"></i></span>Files Processed</div><div class="stat-value"><?= number_format($stats['files_processed']) ?></div></div>
            <div class="card stat-card"><div class="stat-label"><span class="stat-icon" style="background:<?= $stats['open_conflicts']>0?'var(--danger-light)':'var(--success-light)' ?>;color:<?= $stats['open_conflicts']>0?'var(--danger)':'var(--success)' ?>;"><i class="fa-solid fa-triangle-exclamation"></i></span>Conflicts</div><div class="stat-value"><?= number_format($stats['open_conflicts']) ?></div></div>
        </div>

        <div class="grid grid-2 mb-24">
            <div class="card card-pad">
                <h3 class="section-title mb-16">Records by Department</h3>
                <canvas id="deptChart" height="220"></canvas>
            </div>
            <div class="card card-pad">
                <h3 class="section-title mb-16">Classes by Day</h3>
                <canvas id="dayChart" height="220"></canvas>
            </div>
        </div>

        <div class="grid grid-2 mb-24">
            <div class="card card-pad">
                <h3 class="section-title mb-16">Most Used Rooms</h3>
                <canvas id="roomChart" height="220"></canvas>
            </div>
            <div class="card card-pad">
                <h3 class="section-title mb-16">Conflicts by Type</h3>
                <?php if (array_sum($conflictsByType) === 0): ?>
                    <div class="empty-state">
                        <div class="empty-icon" style="color:var(--success);"><i class="fa-solid fa-circle-check"></i></div>
                        <h3>No conflicts</h3>
                        <p>Nothing to chart here — great job!</p>
                    </div>
                <?php else: ?>
                    <canvas id="conflictChart" height="220"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <div class="card card-pad">
            <h3 class="section-title mb-16">Faculty Workload (classes/week)</h3>
            <canvas id="facultyChart" height="200"></canvas>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script>
const chartColors = ['#4F46E5','#22D3C0','#F59E0B','#EF4444','#8B5CF6','#3B82F6','#10B981','#EC4899'];
Chart.defaults.font.family = "'Inter', sans-serif";
Chart.defaults.color = '#6B7280';

new Chart(document.getElementById('deptChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($byDept, 'code')) ?>,
        datasets: [{ label: 'Timetable Records', data: <?= json_encode(array_map('intval', array_column($byDept, 'total'))) ?>, backgroundColor: '#4F46E5', borderRadius: 6 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F0F0F3' } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('dayChart'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_keys($dayCounts)) ?>,
        datasets: [{ label: 'Classes', data: <?= json_encode(array_values($dayCounts)) ?>, borderColor: '#22D3C0', backgroundColor: 'rgba(34,211,192,0.12)', fill: true, tension: 0.35, pointRadius: 4 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F0F0F3' } }, x: { grid: { display: false } } } }
});

new Chart(document.getElementById('roomChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($topRooms, 'room_number')) ?>,
        datasets: [{ label: 'Classes Held', data: <?= json_encode(array_map('intval', array_column($topRooms, 'total'))) ?>, backgroundColor: '#F59E0B', borderRadius: 6 }]
    },
    options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, grid: { color: '#F0F0F3' } }, y: { grid: { display: false } } } }
});

<?php if (array_sum($conflictsByType) > 0): ?>
new Chart(document.getElementById('conflictChart'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode(array_map('ucfirst', array_keys($conflictsByType))) ?>,
        datasets: [{ data: <?= json_encode(array_map('intval', array_values($conflictsByType))) ?>, backgroundColor: chartColors }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});
<?php endif; ?>

new Chart(document.getElementById('facultyChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode(array_column($facultyLoad, 'name')) ?>,
        datasets: [{ label: 'Classes/Week', data: <?= json_encode(array_map('intval', array_column($facultyLoad, 'total'))) ?>, backgroundColor: '#4F46E5', borderRadius: 6 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, grid: { color: '#F0F0F3' } }, x: { grid: { display: false } } } }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
