<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_room'])) {
    $number = trim($_POST['room_number'] ?? '');
    $building = trim($_POST['building'] ?? 'Main Building');
    $capacity = (int) ($_POST['capacity'] ?? 60);

    if ($number === '') {
        setFlash('error', 'Please provide a room number.');
    } else {
        try {
            $ins = $pdo->prepare("INSERT INTO rooms (room_number, building, capacity) VALUES (?,?,?)");
            $ins->execute([$number, $building, $capacity]);
            setFlash('success', 'Room added successfully.');
        } catch (Throwable $e) {
            setFlash('error', 'This room already exists in that building.');
        }
    }
    header('Location: ' . BASE_URL . '/admin/rooms.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    try {
        $pdo->prepare("DELETE FROM rooms WHERE id = ?")->execute([(int) $_POST['delete_id']]);
        setFlash('success', 'Room removed.');
    } catch (Throwable $e) {
        setFlash('error', 'This room has existing timetable records and cannot be deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/rooms.php');
    exit;
}

$rooms = $pdo->query("
    SELECT r.*,
           (SELECT COUNT(*) FROM timetables t WHERE t.room_id = r.id) AS usage_count,
           (SELECT COUNT(*) FROM timetables t JOIN conflicts c ON (c.timetable_id_1=t.id OR c.timetable_id_2=t.id)
                WHERE t.room_id = r.id AND c.conflict_type='room' AND c.status='open') AS conflict_count
    FROM rooms r ORDER BY r.building, r.room_number
")->fetchAll();

$pageTitle = 'Rooms';
$role = 'admin';
$activePage = 'rooms';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Rooms</h1>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openModal('addRoomModal')"><i class="fa-solid fa-plus"></i> Add Room</button>
    </div>

    <div class="page-body">
        <?php if (!$rooms): ?>
            <div class="card"><div class="empty-state">
                <div class="empty-icon"><i class="fa-solid fa-door-open"></i></div>
                <h3>No rooms yet</h3>
                <p>Rooms are created automatically on Excel import, or you can add one manually.</p>
                <button class="btn btn-primary" onclick="openModal('addRoomModal')">Add Room</button>
            </div></div>
        <?php else: ?>
            <div class="grid grid-3">
                <?php foreach ($rooms as $r): ?>
                    <div class="card card-pad card-hover">
                        <div class="flex-between mb-12">
                            <h3 style="font-size:1rem;margin:0;"><i class="fa-solid fa-door-open" style="color:var(--primary);"></i> <?= e($r['room_number']) ?></h3>
                            <form method="POST" onsubmit="return confirm('Remove this room?');">
                                <input type="hidden" name="delete_id" value="<?= $r['id'] ?>">
                                <button type="submit" class="btn btn-ghost btn-sm"><i class="fa-solid fa-trash" style="color:var(--danger);"></i></button>
                            </form>
                        </div>
                        <p class="text-sm text-muted"><?= e($r['building']) ?> · Capacity <?= (int)$r['capacity'] ?></p>
                        <div class="flex gap-8 mt-12">
                            <span class="badge badge-neutral"><?= (int)$r['usage_count'] ?> classes/week</span>
                            <?php if ($r['conflict_count'] > 0): ?>
                                <span class="badge badge-danger"><?= (int)$r['conflict_count'] ?> conflicts</span>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<div class="modal-overlay" id="addRoomModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>Add Room</h3>
            <button class="modal-close" onclick="closeModal('addRoomModal')"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <div class="form-group">
                    <label>Room Number</label>
                    <input type="text" name="room_number" class="form-control" placeholder="e.g. 402 or Lab 3" required>
                </div>
                <div class="form-group">
                    <label>Building</label>
                    <input type="text" name="building" class="form-control" value="Main Building">
                </div>
                <div class="form-group">
                    <label>Capacity</label>
                    <input type="number" name="capacity" class="form-control" value="60" min="1">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addRoomModal')">Cancel</button>
                <button type="submit" name="add_room" class="btn btn-primary">Add Room</button>
            </div>
        </form>
    </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
