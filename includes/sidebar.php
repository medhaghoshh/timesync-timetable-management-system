<?php
/**
 * Reusable sidebar. Expects $role ('admin'|'student') and $activePage set by the including page.
 */
$activePage = $activePage ?? '';

$adminLinks = [
    'dashboard'  => ['icon' => 'fa-grid-2', 'label' => 'Dashboard', 'href' => '/admin/dashboard.php'],
    'upload'     => ['icon' => 'fa-cloud-arrow-up', 'label' => 'Upload Files', 'href' => '/admin/upload.php'],
    'timetables' => ['icon' => 'fa-table', 'label' => 'Timetables', 'href' => '/admin/timetables.php'],
    'conflicts'  => ['icon' => 'fa-triangle-exclamation', 'label' => 'Conflicts', 'href' => '/admin/conflicts.php'],
    'students'   => ['icon' => 'fa-user-graduate', 'label' => 'Students', 'href' => '/admin/students.php'],
    'subjects'   => ['icon' => 'fa-book', 'label' => 'Subjects', 'href' => '/admin/subjects.php'],
    'faculty'    => ['icon' => 'fa-chalkboard-user', 'label' => 'Faculty', 'href' => '/admin/faculty.php'],
    'rooms'      => ['icon' => 'fa-door-open', 'label' => 'Rooms', 'href' => '/admin/rooms.php'],
    'sections'   => ['icon' => 'fa-layer-group', 'label' => 'Sections', 'href' => '/admin/sections.php'],
    'analytics'  => ['icon' => 'fa-chart-simple', 'label' => 'Analytics', 'href' => '/admin/analytics.php'],
];

$studentLinks = [
    'dashboard'  => ['icon' => 'fa-grid-2', 'label' => 'Dashboard', 'href' => '/student/dashboard.php'],
    'timetable'  => ['icon' => 'fa-calendar-week', 'label' => 'My Timetable', 'href' => '/student/timetable.php'],
    'today'      => ['icon' => 'fa-clock', 'label' => "Today's Classes", 'href' => '/student/today.php'],
    'subjects'   => ['icon' => 'fa-book', 'label' => 'Subjects', 'href' => '/student/subjects.php'],
    'faculty'    => ['icon' => 'fa-chalkboard-user', 'label' => 'Faculty', 'href' => '/student/faculty.php'],
    'conflicts'  => ['icon' => 'fa-triangle-exclamation', 'label' => 'Conflicts', 'href' => '/student/conflicts.php'],
    'profile'    => ['icon' => 'fa-user', 'label' => 'Profile', 'href' => '/student/profile.php'],
];

$links = $role === 'admin' ? $adminLinks : $studentLinks;
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
        <span class="logo-icon"><i class="fa-solid fa-calendar-days"></i></span>
        TimeSync
    </div>
    <nav class="sidebar-nav">
        <div class="nav-section-label"><?= $role === 'admin' ? 'Administration' : 'My Space' ?></div>
        <?php foreach ($links as $key => $link): ?>
            <a href="<?= BASE_URL . $link['href'] ?>" class="sidebar-link <?= $activePage === $key ? 'active' : '' ?>">
                <i class="fa-solid <?= $link['icon'] ?>"></i> <span><?= $link['label'] ?></span>
            </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <a href="<?= BASE_URL ?>/logout.php" class="sidebar-link" style="color:var(--danger);">
            <i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span>
        </a>
    </div>
</aside>
