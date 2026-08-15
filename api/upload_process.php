<?php
/**
 * Handles a single spreadsheet upload: validates it, stores it, runs it through
 * PHPSpreadsheet-based processing, saves valid rows, and runs conflict detection.
 */
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (currentRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

if (empty($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file was received.']);
    exit;
}

$departmentId = (int) ($_POST['department_id'] ?? 0);
$semesterId = (int) ($_POST['semester_id'] ?? 0);
$defaultSection = trim($_POST['default_section'] ?? '');

if (!$departmentId || !$semesterId) {
    echo json_encode(['success' => false, 'message' => 'Please select a department and semester before uploading.']);
    exit;
}

$file = $_FILES['file'];
$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

if (!in_array($ext, ALLOWED_EXCEL_TYPES, true)) {
    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only .xlsx, .xls and .csv are supported.']);
    exit;
}
if ($file['size'] > MAX_UPLOAD_SIZE) {
    echo json_encode(['success' => false, 'message' => 'File exceeds the 5 MB size limit.']);
    exit;
}

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);

$safeName = preg_replace('/[^A-Za-z0-9._-]/', '_', $file['name']);
$storedName = date('YmdHis') . '_' . $safeName;
$destination = UPLOAD_DIR . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destination)) {
    echo json_encode(['success' => false, 'message' => 'Could not save the uploaded file. Please try again.']);
    exit;
}

// Create the uploaded_files record first (status = processing)
$ins = $pdo->prepare("INSERT INTO uploaded_files (file_name, file_path, uploaded_by, status) VALUES (?,?,?,'processing')");
$ins->execute([$file['name'], 'uploads/' . $storedName, currentUserId()]);
$uploadedFileId = (int) $pdo->lastInsertId();

$context = [
    'department_id' => $departmentId,
    'semester_id' => $semesterId,
    'default_section' => $defaultSection,
];

$summary = processExcelFile($pdo, $destination, $context, $uploadedFileId);

// Run conflict detection scoped to freshly-inserted rows (and their sections/faculty/rooms)
$conflictCounts = ['section' => 0, 'faculty' => 0, 'room' => 0];
if (!empty($summary['inserted_ids'])) {
    $conflictCounts['section'] = detectSectionConflicts($pdo, $summary['inserted_ids']);
    $conflictCounts['faculty'] = detectFacultyConflicts($pdo);
    $conflictCounts['room'] = detectRoomConflicts($pdo);
}
$totalConflicts = array_sum($conflictCounts);

$status = ($summary['imported'] === 0 && $summary['total'] > 0) ? 'failed' : 'completed';
if ($summary['total'] === 0 && !empty($summary['errors'])) {
    $status = 'failed';
}

$update = $pdo->prepare("UPDATE uploaded_files SET records_imported=?, records_failed=?, conflicts_found=?, status=? WHERE id=?");
$update->execute([$summary['imported'], $summary['skipped'], $totalConflicts, $status, $uploadedFileId]);

echo json_encode([
    'success' => true,
    'summary' => [
        'total' => $summary['total'],
        'imported' => $summary['imported'],
        'skipped' => $summary['skipped'],
        'conflicts' => $totalConflicts,
        'errors' => $summary['errors'],
    ],
]);
