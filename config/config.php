<?php
/**
 * Global configuration: session bootstrap, app constants, error display rules.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Never show raw PHP errors/warnings to end users. Log them instead.
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

define('APP_NAME', 'TimeSync');
define('BASE_URL', '/timesync'); // change if project folder name differs
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXCEL_TYPES', ['xlsx', 'xls', 'csv']);

define('DAYS_OF_WEEK', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday']);
