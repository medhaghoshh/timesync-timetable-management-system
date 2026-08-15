<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole('admin');

$departments = $pdo->query("SELECT * FROM departments ORDER BY name")->fetchAll();
$semesters = $pdo->query("SELECT * FROM semesters ORDER BY semester_number")->fetchAll();

$recentFiles = $pdo->query("SELECT uf.*, u.name AS uploader FROM uploaded_files uf JOIN users u ON u.id = uf.uploaded_by ORDER BY uploaded_at DESC")->fetchAll();

$pageTitle = 'Upload Timetables';
$role = 'admin';
$activePage = 'upload';
require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
?>
<div class="main-content">
    <div class="topbar">
        <div class="flex gap-16" style="align-items:center;">
            <button class="mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()"><i class="fa-solid fa-bars"></i></button>
            <h1>Upload Timetables</h1>
        </div>
        <a href="<?= BASE_URL ?>/api/download_template.php" class="btn btn-outline btn-sm"><i class="fa-solid fa-download"></i> Sample Template</a>
    </div>

    <div class="page-body">
        <div class="card card-pad mb-24">
            <h3 class="section-title mb-16">1. Select context for this file</h3>
            <p class="text-muted text-sm mb-16" style="margin-top:-10px;">Each spreadsheet is usually for one department & semester. This is used whenever the file doesn't already contain a Department/Semester column.</p>
            <div class="grid grid-2">
                <div class="form-group">
                    <label for="department_id">Department</label>
                    <select id="department_id" class="form-control">
                        <?php foreach ($departments as $d): ?>
                            <option value="<?= $d['id'] ?>"><?= e($d['name']) ?> (<?= e($d['code']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="semester_id">Semester</label>
                    <select id="semester_id" class="form-control">
                        <?php foreach ($semesters as $s): ?>
                            <option value="<?= $s['id'] ?>" <?= $s['semester_number']==5?'selected':'' ?>>Semester <?= $s['semester_number'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group" style="max-width:200px;">
                <label for="default_section">Default Section (if file has none)</label>
                <input type="text" id="default_section" class="form-control" placeholder="e.g. A" maxlength="5">
            </div>
        </div>

        <div class="card card-pad mb-24">
            <h3 class="section-title mb-16">2. Upload spreadsheets</h3>
            <div id="dropZone" class="drop-zone">
                <i class="fa-solid fa-cloud-arrow-up" style="font-size:2.2rem;color:var(--primary);"></i>
                <h3 class="mt-16">Upload timetable spreadsheets</h3>
                <p class="text-muted">Drag and drop your Excel files here, or <span style="color:var(--primary);font-weight:600;cursor:pointer;" onclick="document.getElementById('fileInput').click()">browse</span></p>
                <p class="text-sm text-muted">Supports .xlsx, .xls, .csv · Max 5 MB per file</p>
                <input type="file" id="fileInput" accept=".xlsx,.xls,.csv" multiple hidden>
            </div>
        </div>

        <div id="uploadQueue"></div>

        <div class="card card-pad">
            <h3 class="section-title mb-16">Upload History</h3>
            <?php if (!$recentFiles): ?>
                <div class="empty-state">
                    <div class="empty-icon"><i class="fa-solid fa-file-excel"></i></div>
                    <h3>No files uploaded yet</h3>
                    <p>Your uploaded timetable files will appear here.</p>
                </div>
            <?php else: ?>
            <div class="table-wrap">
                <table class="data-table">
                    <thead><tr><th>File</th><th>Uploaded By</th><th>Imported</th><th>Skipped</th><th>Conflicts</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody id="fileHistoryBody">
                        <?php foreach ($recentFiles as $f): ?>
                        <tr id="file-row-<?= $f['id'] ?>">
                            <td><?= e($f['file_name']) ?></td>
                            <td><?= e($f['uploader']) ?></td>
                            <td><?= (int)$f['records_imported'] ?></td>
                            <td><?= (int)$f['records_failed'] ?></td>
                            <td><?= (int)$f['conflicts_found'] ?></td>
                            <td>
                                <?php if ($f['status']==='completed'): ?><span class="badge badge-success">Processed</span>
                                <?php elseif ($f['status']==='failed'): ?><span class="badge badge-danger">Failed</span>
                                <?php else: ?><span class="badge badge-muted">Processing</span><?php endif; ?>
                            </td>
                            <td class="text-sm text-muted"><?= date('d M, h:i A', strtotime($f['uploaded_at'])) ?></td>
                            <td><button class="btn btn-ghost btn-sm" onclick="deleteFile(<?= $f['id'] ?>)"><i class="fa-solid fa-trash" style="color:var(--danger);"></i></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .drop-zone { border: 2px dashed var(--border); border-radius: var(--radius-lg); padding: 50px 20px; text-align: center; transition: all .15s ease; cursor: pointer; }
    .drop-zone.dragover { border-color: var(--primary); background: var(--primary-light); }
    .upload-item { background: var(--surface); border: 1px solid var(--border); border-radius: var(--radius); padding: 16px 18px; margin-bottom: 12px; }
    .upload-item .u-name { font-weight: 600; font-size: 0.9rem; }
    .upload-item .u-size { font-size: 0.78rem; color: var(--muted); }
    .progress-bar-wrap { height: 6px; background: var(--border); border-radius: 999px; overflow: hidden; margin-top: 10px; }
    .progress-bar-fill { height: 100%; background: var(--primary); width: 0%; transition: width .2s ease; }
    .summary-box { margin-top: 10px; font-size: 0.82rem; }
    .summary-box .row-err { color: var(--danger); }
</style>

<?php $extraScripts = ['/assets/js/upload.js']; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
