<?php
/**
 * Core reusable business logic.
 * Kept separate from presentation (pages) so it's easy to read and explain in an interview.
 */
require_once __DIR__ . '/../config/database.php';

/* ============================================================
   FLASH MESSAGES / TOASTS
   ============================================================ */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/* ============================================================
   STUDENT / PROFILE HELPERS
   ============================================================ */
function getStudentDetails(PDO $pdo, int $userId): ?array {
    $stmt = $pdo->prepare("
        SELECT s.*, u.name, u.email, d.name AS department_name, d.code AS department_code,
               sem.semester_number, sec.section_name
        FROM students s
        JOIN users u ON u.id = s.user_id
        JOIN departments d ON d.id = s.department_id
        JOIN semesters sem ON sem.id = s.semester_id
        JOIN sections sec ON sec.id = s.section_id
        WHERE s.user_id = ?
    ");
    $stmt->execute([$userId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/* ============================================================
   TIMETABLE RETRIEVAL
   ============================================================ */

/**
 * Full weekly timetable for a section (a student's personalized timetable
 * is simply their section's timetable).
 */
function getStudentTimetable(PDO $pdo, int $sectionId): array {
    $stmt = $pdo->prepare("
        SELECT t.id, t.day, t.start_time, t.end_time,
               sub.subject_name, sub.subject_code,
               f.name AS faculty_name,
               r.room_number, r.building,
               sec.section_name
        FROM timetables t
        JOIN subjects sub ON sub.id = t.subject_id
        JOIN faculty f ON f.id = t.faculty_id
        JOIN rooms r ON r.id = t.room_id
        JOIN sections sec ON sec.id = t.section_id
        WHERE t.section_id = ?
        ORDER BY FIELD(t.day,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'), t.start_time
    ");
    $stmt->execute([$sectionId]);
    return $stmt->fetchAll();
}

function getTodayClasses(PDO $pdo, int $sectionId): array {
    $today = date('l'); // e.g. "Monday"
    if (!in_array($today, DAYS_OF_WEEK, true)) {
        return []; // Sunday - no classes
    }
    $stmt = $pdo->prepare("
        SELECT t.id, t.start_time, t.end_time,
               sub.subject_name, sub.subject_code,
               f.name AS faculty_name, r.room_number
        FROM timetables t
        JOIN subjects sub ON sub.id = t.subject_id
        JOIN faculty f ON f.id = t.faculty_id
        JOIN rooms r ON r.id = t.room_id
        WHERE t.section_id = ? AND t.day = ?
        ORDER BY t.start_time
    ");
    $stmt->execute([$sectionId, $today]);
    return $stmt->fetchAll();
}

function getUpcomingClass(array $todayClasses): ?array {
    $now = date('H:i:s');
    foreach ($todayClasses as $class) {
        if ($class['start_time'] >= $now) {
            return $class;
        }
    }
    return null;
}

/** Classify each of today's classes as completed / current / upcoming for UI badges. */
function classifyClassStatus(string $start, string $end): string {
    $now = date('H:i:s');
    if ($now < $start) return 'upcoming';
    if ($now >= $start && $now <= $end) return 'current';
    return 'completed';
}

/* ============================================================
   DASHBOARD STATISTICS
   ============================================================ */
function getDashboardStatistics(PDO $pdo): array {
    $stats = [];
    $stats['total_students']  = (int) $pdo->query("SELECT COUNT(*) c FROM students")->fetch()['c'];
    $stats['total_records']   = (int) $pdo->query("SELECT COUNT(*) c FROM timetables")->fetch()['c'];
    $stats['files_processed'] = (int) $pdo->query("SELECT COUNT(*) c FROM uploaded_files WHERE status='completed'")->fetch()['c'];
    $stats['open_conflicts']  = (int) $pdo->query("SELECT COUNT(*) c FROM conflicts WHERE status='open'")->fetch()['c'];
    return $stats;
}

function getStudentDashboardStats(PDO $pdo, array $student): array {
    $today = getTodayClasses($pdo, (int)$student['section_id']);
    $upcoming = getUpcomingClass($today);

    $subjectCount = $pdo->prepare("SELECT COUNT(DISTINCT subject_id) c FROM timetables WHERE section_id=?");
    $subjectCount->execute([$student['section_id']]);

    $conflictCount = $pdo->prepare("
        SELECT COUNT(*) c FROM conflicts c
        JOIN timetables t1 ON t1.id = c.timetable_id_1
        JOIN timetables t2 ON t2.id = c.timetable_id_2
        WHERE c.status='open' AND (t1.section_id = ? OR t2.section_id = ?)
    ");
    $conflictCount->execute([$student['section_id'], $student['section_id']]);

    return [
        'today_count'   => count($today),
        'next_class'    => $upcoming,
        'subject_count' => (int) $subjectCount->fetch()['c'],
        'conflict_count'=> (int) $conflictCount->fetch()['c'],
    ];
}

/* ============================================================
   EXCEL IMPORT - NORMALIZATION
   ============================================================ */

/**
 * Map many possible spreadsheet header spellings to our internal field names.
 * Returns the internal key, or null if the header is not recognized.
 */
function normalizeHeader(string $header): ?string {
    $header = strtolower(trim($header));
    $header = preg_replace('/[^a-z0-9]/', '', $header); // strip spaces/punctuation

    $map = [
        'day'                 => 'day',
        'date'                => 'day',
        'starttime'           => 'start_time',
        'time'                => 'start_time',
        'from'                => 'start_time',
        'endtime'             => 'end_time',
        'to'                  => 'end_time',
        'subject'             => 'subject_name',
        'subjectname'         => 'subject_name',
        'coursename'          => 'subject_name',
        'subjectcode'         => 'subject_code',
        'coursecode'          => 'subject_code',
        'faculty'             => 'faculty_name',
        'teacher'             => 'faculty_name',
        'facultyname'         => 'faculty_name',
        'instructor'          => 'faculty_name',
        'room'                => 'room_number',
        'classroom'           => 'room_number',
        'roomnumber'          => 'room_number',
        'venue'                => 'room_number',
        'section'             => 'section_name',
        'sec'                 => 'section_name',
        'semester'            => 'semester_number',
        'sem'                 => 'semester_number',
        'department'          => 'department_code',
        'dept'                => 'department_code',
        'branch'              => 'department_code',
    ];

    return $map[$header] ?? null;
}

/** Normalize "9am", "9:00", "09:00:00" etc into H:i:s used by MySQL TIME columns. */
function normalizeTimeValue($value): ?string {
    if ($value === null || $value === '') return null;

    // Excel sometimes gives a fraction-of-day float for time cells.
    if (is_numeric($value)) {
        $seconds = round(((float)$value) * 24 * 3600);
        $h = floor($seconds / 3600) % 24;
        $m = floor(($seconds % 3600) / 60);
        $s = $seconds % 60;
        return sprintf('%02d:%02d:%02d', $h, $m, $s);
    }

    $value = trim((string)$value);
    $value = str_ireplace(['am', 'pm', ' '], ['', '', ''], strtolower($value)) === $value
        ? $value : $value; // keep original for strtotime, AM/PM handled below

    $ts = strtotime($value);
    if ($ts === false) return null;
    return date('H:i:s', $ts);
}

function normalizeDayValue($value): ?string {
    $value = trim((string)$value);
    // If it's an actual date, convert to weekday name.
    $ts = strtotime($value);
    if ($ts !== false && preg_match('/\d{4}-\d{2}-\d{2}|\//', $value)) {
        $day = date('l', $ts);
        return in_array($day, DAYS_OF_WEEK, true) ? $day : null;
    }
    $value = ucfirst(strtolower($value));
    foreach (DAYS_OF_WEEK as $day) {
        if (str_starts_with($day, $value) || $value === $day) return $day;
    }
    return null;
}

/**
 * Turn one associative row (already keyed by normalized header) into a clean,
 * validated record ready for insertion. Returns ['valid'=>bool,'data'=>[],'error'=>string]
 */
function normalizeTimetableRow(array $row, array $context): array {
    $day = normalizeDayValue($row['day'] ?? '');
    $start = normalizeTimeValue($row['start_time'] ?? '');
    $end = normalizeTimeValue($row['end_time'] ?? '');
    $subjectName = trim($row['subject_name'] ?? '');
    $subjectCode = trim($row['subject_code'] ?? '');
    $facultyName = trim($row['faculty_name'] ?? '');
    $roomNumber = trim($row['room_number'] ?? '');
    $sectionName = trim($row['section_name'] ?? $context['default_section'] ?? '');

    if (!$day)               return ['valid' => false, 'error' => 'Invalid or missing Day'];
    if (!$start)              return ['valid' => false, 'error' => 'Invalid or missing Start Time'];
    if (!$end)                return ['valid' => false, 'error' => 'Invalid or missing End Time'];
    if ($end <= $start)       return ['valid' => false, 'error' => 'End time must be after start time'];
    if ($subjectName === '')  return ['valid' => false, 'error' => 'Missing Subject'];
    if ($facultyName === '')  return ['valid' => false, 'error' => 'Missing Faculty'];
    if ($roomNumber === '')   return ['valid' => false, 'error' => 'Missing Room'];
    if ($sectionName === '')  return ['valid' => false, 'error' => 'Missing Section'];

    if ($subjectCode === '') {
        $subjectCode = strtoupper(substr(preg_replace('/[^A-Za-z0-9]/', '', $subjectName), 0, 6));
    }

    return [
        'valid' => true,
        'data' => [
            'day' => $day, 'start_time' => $start, 'end_time' => $end,
            'subject_name' => $subjectName, 'subject_code' => $subjectCode,
            'faculty_name' => $facultyName, 'room_number' => $roomNumber,
            'section_name' => $sectionName,
        ],
    ];
}

/**
 * Insert a normalized row into MySQL, creating subject/faculty/room/section
 * lookup rows on the fly if they don't already exist (get-or-create pattern).
 */
function insertTimetableRecord(PDO $pdo, array $data, array $context, int $uploadedFileId): int {
    $departmentId = $context['department_id'];
    $semesterId   = $context['semester_id'];

    // Section (get or create within this department+semester)
    $stmt = $pdo->prepare("SELECT id FROM sections WHERE department_id=? AND semester_id=? AND section_name=?");
    $stmt->execute([$departmentId, $semesterId, $data['section_name']]);
    $sectionId = $stmt->fetchColumn();
    if (!$sectionId) {
        $ins = $pdo->prepare("INSERT INTO sections (department_id, semester_id, section_name) VALUES (?,?,?)");
        $ins->execute([$departmentId, $semesterId, $data['section_name']]);
        $sectionId = $pdo->lastInsertId();
    }

    // Subject (get or create)
    $stmt = $pdo->prepare("SELECT id FROM subjects WHERE subject_code=? AND department_id=?");
    $stmt->execute([$data['subject_code'], $departmentId]);
    $subjectId = $stmt->fetchColumn();
    if (!$subjectId) {
        $ins = $pdo->prepare("INSERT INTO subjects (subject_code, subject_name, department_id, semester_id) VALUES (?,?,?,?)");
        $ins->execute([$data['subject_code'], $data['subject_name'], $departmentId, $semesterId]);
        $subjectId = $pdo->lastInsertId();
    }

    // Faculty (get or create)
    $stmt = $pdo->prepare("SELECT id FROM faculty WHERE name=? AND department_id=?");
    $stmt->execute([$data['faculty_name'], $departmentId]);
    $facultyId = $stmt->fetchColumn();
    if (!$facultyId) {
        $ins = $pdo->prepare("INSERT INTO faculty (name, department_id) VALUES (?,?)");
        $ins->execute([$data['faculty_name'], $departmentId]);
        $facultyId = $pdo->lastInsertId();
    }

    // Room (get or create)
    $stmt = $pdo->prepare("SELECT id FROM rooms WHERE room_number=?");
    $stmt->execute([$data['room_number']]);
    $roomId = $stmt->fetchColumn();
    if (!$roomId) {
        $ins = $pdo->prepare("INSERT INTO rooms (room_number) VALUES (?)");
        $ins->execute([$data['room_number']]);
        $roomId = $pdo->lastInsertId();
    }

    // Duplicate check
    $dup = $pdo->prepare("SELECT id FROM timetables WHERE day=? AND start_time=? AND end_time=? AND subject_id=? AND section_id=?");
    $dup->execute([$data['day'], $data['start_time'], $data['end_time'], $subjectId, $sectionId]);
    if ($dup->fetchColumn()) {
        return 0; // duplicate, not inserted
    }

    $ins = $pdo->prepare("
        INSERT INTO timetables (day, start_time, end_time, subject_id, faculty_id, room_id, department_id, semester_id, section_id, uploaded_file_id)
        VALUES (?,?,?,?,?,?,?,?,?,?)
    ");
    $ins->execute([
        $data['day'], $data['start_time'], $data['end_time'],
        $subjectId, $facultyId, $roomId, $departmentId, $semesterId, $sectionId, $uploadedFileId,
    ]);
    return (int) $pdo->lastInsertId();
}

/* ============================================================
   EXCEL PROCESSING (uses PHPSpreadsheet)
   ============================================================ */
function processExcelFile(PDO $pdo, string $filePath, array $context, int $uploadedFileId): array {
    require_once __DIR__ . '/../vendor/autoload.php';

    $summary = ['total' => 0, 'imported' => 0, 'skipped' => 0, 'errors' => [], 'inserted_ids' => []];

    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);
    } catch (\Throwable $e) {
        error_log('Excel read error: ' . $e->getMessage());
        return ['total' => 0, 'imported' => 0, 'skipped' => 0,
                'errors' => [['row' => 0, 'reason' => 'Could not read this file. Please make sure it is a valid Excel/CSV file.']]];
    }

    if (empty($rows)) {
        return ['total' => 0, 'imported' => 0, 'skipped' => 0,
                'errors' => [['row' => 0, 'reason' => 'The spreadsheet appears to be empty.']]];
    }

    // --- Detect header row and map columns to internal field names ---
    $headerRow = array_shift($rows);
    $columnMap = []; // columnIndex => internalField
    foreach ($headerRow as $idx => $header) {
        $normalized = normalizeHeader((string) $header);
        if ($normalized) $columnMap[$idx] = $normalized;
    }

    $required = ['day', 'start_time', 'end_time', 'subject_name'];
    $foundFields = array_values($columnMap);
    foreach ($required as $req) {
        if (!in_array($req, $foundFields, true)) {
            $label = ucwords(str_replace('_', ' ', $req));
            return ['total' => 0, 'imported' => 0, 'skipped' => 0,
                    'errors' => [['row' => 1, 'reason' => "Unable to identify the {$label} column."]]];
        }
    }

    $rowNum = 1; // header was row 1
    foreach ($rows as $rawRow) {
        $rowNum++;
        // Skip fully blank rows
        if (count(array_filter($rawRow, fn($v) => trim((string)$v) !== '')) === 0) continue;

        $summary['total']++;
        $row = [];
        foreach ($columnMap as $idx => $field) {
            $row[$field] = $rawRow[$idx] ?? '';
        }

        $normalized = normalizeTimetableRow($row, $context);
        if (!$normalized['valid']) {
            $summary['skipped']++;
            $summary['errors'][] = ['row' => $rowNum, 'reason' => $normalized['error']];
            continue;
        }

        try {
            $id = insertTimetableRecord($pdo, $normalized['data'], $context, $uploadedFileId);
            if ($id === 0) {
                $summary['skipped']++;
                $summary['errors'][] = ['row' => $rowNum, 'reason' => 'Duplicate record (already exists)'];
            } else {
                $summary['imported']++;
                $summary['inserted_ids'][] = $id;
            }
        } catch (\Throwable $e) {
            error_log('Row insert error: ' . $e->getMessage());
            $summary['skipped']++;
            $summary['errors'][] = ['row' => $rowNum, 'reason' => 'Could not save this row.'];
        }
    }

    return $summary;
}

/* ============================================================
   CONFLICT DETECTION
   Two time ranges overlap when start1 < end2 AND start2 < end1
   ============================================================ */
function timesOverlap(string $start1, string $end1, string $start2, string $end2): bool {
    return ($start1 < $end2) && ($start2 < $end1);
}

function logConflict(PDO $pdo, string $type, string $severity, int $id1, int $id2, string $description): void {
    // Avoid duplicate conflict entries (in either order)
    $check = $pdo->prepare("
        SELECT id FROM conflicts
        WHERE conflict_type = ? AND
        ((timetable_id_1=? AND timetable_id_2=?) OR (timetable_id_1=? AND timetable_id_2=?))
    ");
    $check->execute([$type, $id1, $id2, $id2, $id1]);
    if ($check->fetchColumn()) return;

    $ins = $pdo->prepare("
        INSERT INTO conflicts (conflict_type, severity, timetable_id_1, timetable_id_2, description)
        VALUES (?,?,?,?,?)
    ");
    $ins->execute([$type, $severity, $id1, $id2, $description]);
}

/** Same section, overlapping time -> student physically cannot attend both. HIGH severity. */
function detectSectionConflicts(PDO $pdo, ?array $newIds = null): int {
    $sql = "SELECT id, day, start_time, end_time, section_id FROM timetables";
    if ($newIds) {
        $sql .= " WHERE section_id IN (SELECT section_id FROM timetables WHERE id IN (" . implode(',', array_map('intval', $newIds)) . "))";
    }
    $rows = $pdo->query($sql)->fetchAll();

    $bySection = [];
    foreach ($rows as $r) $bySection[$r['section_id']][] = $r;

    $count = 0;
    foreach ($bySection as $classes) {
        for ($i = 0; $i < count($classes); $i++) {
            for ($j = $i + 1; $j < count($classes); $j++) {
                $a = $classes[$i]; $b = $classes[$j];
                if ($a['day'] === $b['day'] && timesOverlap($a['start_time'], $a['end_time'], $b['start_time'], $b['end_time'])) {
                    logConflict($pdo, 'section', 'HIGH', $a['id'], $b['id'],
                        "Section has two overlapping classes on {$a['day']} ({$a['start_time']}-{$a['end_time']} vs {$b['start_time']}-{$b['end_time']})");
                    $count++;
                }
            }
        }
    }
    return $count;
}

/** Same faculty double-booked across two different sections at overlapping times. HIGH severity. */
function detectFacultyConflicts(PDO $pdo): int {
    $rows = $pdo->query("SELECT id, day, start_time, end_time, faculty_id FROM timetables")->fetchAll();
    $byFaculty = [];
    foreach ($rows as $r) $byFaculty[$r['faculty_id']][] = $r;

    $count = 0;
    foreach ($byFaculty as $classes) {
        for ($i = 0; $i < count($classes); $i++) {
            for ($j = $i + 1; $j < count($classes); $j++) {
                $a = $classes[$i]; $b = $classes[$j];
                if ($a['day'] === $b['day'] && timesOverlap($a['start_time'], $a['end_time'], $b['start_time'], $b['end_time'])) {
                    logConflict($pdo, 'faculty', 'HIGH', $a['id'], $b['id'],
                        "Faculty is scheduled for two overlapping classes on {$a['day']} ({$a['start_time']}-{$a['end_time']} vs {$b['start_time']}-{$b['end_time']})");
                    $count++;
                }
            }
        }
    }
    return $count;
}

/** Same room double-booked at overlapping times. MEDIUM severity (could be resolved by moving one class). */
function detectRoomConflicts(PDO $pdo): int {
    $rows = $pdo->query("SELECT id, day, start_time, end_time, room_id FROM timetables")->fetchAll();
    $byRoom = [];
    foreach ($rows as $r) $byRoom[$r['room_id']][] = $r;

    $count = 0;
    foreach ($byRoom as $classes) {
        for ($i = 0; $i < count($classes); $i++) {
            for ($j = $i + 1; $j < count($classes); $j++) {
                $a = $classes[$i]; $b = $classes[$j];
                if ($a['day'] === $b['day'] && timesOverlap($a['start_time'], $a['end_time'], $b['start_time'], $b['end_time'])) {
                    logConflict($pdo, 'room', 'MEDIUM', $a['id'], $b['id'],
                        "Room is double-booked on {$a['day']} ({$a['start_time']}-{$a['end_time']} vs {$b['start_time']}-{$b['end_time']})");
                    $count++;
                }
            }
        }
    }
    return $count;
}

/** Run all conflict detectors. Called after every import (and available as a manual re-scan). */
function runAllConflictDetection(PDO $pdo): array {
    return [
        'section' => detectSectionConflicts($pdo),
        'faculty' => detectFacultyConflicts($pdo),
        'room'    => detectRoomConflicts($pdo),
    ];
}
