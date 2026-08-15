<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');
require_once __DIR__ . '/../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Timetable');

$headers = ['Day', 'Start Time', 'End Time', 'Subject Code', 'Subject', 'Faculty', 'Room', 'Section'];
$sheet->fromArray($headers, null, 'A1');
$sheet->getStyle('A1:H1')->getFont()->setBold(true);

$sample = [
    ['Monday', '09:00', '10:00', 'CS501', 'Database Management Systems', 'Dr. Sharma', '204', 'A'],
    ['Monday', '10:00', '11:00', 'CS502', 'Operating Systems', 'Prof. Roy', '301', 'A'],
    ['Tuesday', '11:15', '12:15', 'CS503', 'Computer Networks', 'Prof. Sen', 'Lab 2', 'A'],
];
$sheet->fromArray($sample, null, 'A2');

foreach (range('A', 'H') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="timesync_timetable_template.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
