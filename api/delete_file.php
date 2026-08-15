<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

if (currentRole() !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$fileId = (int) ($input['file_id'] ?? 0);

if (!$fileId) {
    echo json_encode(['success' => false, 'message' => 'Invalid file ID.']);
    exit;
}

try {
    $stmt = $pdo->prepare("SELECT file_path FROM uploaded_files WHERE id = ?");
    $stmt->execute([$fileId]);
    $row = $stmt->fetch();

    if (!$row) {
        echo json_encode(['success' => false, 'message' => 'File not found.']);
        exit;
    }

    // Detach any timetable rows from this file (keep the data, just unlink the source file)
    $pdo->prepare("UPDATE timetables SET uploaded_file_id = NULL WHERE uploaded_file_id = ?")->execute([$fileId]);
    $pdo->prepare("DELETE FROM uploaded_files WHERE id = ?")->execute([$fileId]);

    $physicalPath = __DIR__ . '/../' . $row['file_path'];
    if (is_file($physicalPath)) @unlink($physicalPath);

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('Delete file error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Unable to delete this record. Please try again.']);
}
