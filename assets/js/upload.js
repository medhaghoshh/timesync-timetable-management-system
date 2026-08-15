/* =========================================================
   TimeSync - Admin Upload Page
   ========================================================= */

const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const uploadQueue = document.getElementById('uploadQueue');

['dragenter', 'dragover'].forEach(evt => {
    dropZone.addEventListener(evt, (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
});
['dragleave', 'drop'].forEach(evt => {
    dropZone.addEventListener(evt, (e) => { e.preventDefault(); dropZone.classList.remove('dragover'); });
});
dropZone.addEventListener('drop', (e) => {
    handleFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', (e) => handleFiles(e.target.files));

function formatSize(bytes) {
    if (bytes < 1024) return bytes + ' B';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
}

function handleFiles(fileList) {
    const departmentId = document.getElementById('department_id').value;
    const semesterId = document.getElementById('semester_id').value;
    const defaultSection = document.getElementById('default_section').value.trim();

    [...fileList].forEach(file => {
        const allowed = ['xlsx', 'xls', 'csv'];
        const ext = file.name.split('.').pop().toLowerCase();
        const itemId = 'upload-' + Date.now() + '-' + Math.random().toString(36).slice(2, 7);

        const item = document.createElement('div');
        item.className = 'upload-item';
        item.id = itemId;
        item.innerHTML = `
            <div class="flex-between">
                <div>
                    <div class="u-name"><i class="fa-solid fa-file-excel" style="color:var(--success);margin-right:6px;"></i>${file.name}</div>
                    <div class="u-size">${formatSize(file.size)}</div>
                </div>
                <span class="badge badge-muted status-badge">Uploading...</span>
            </div>
            <div class="progress-bar-wrap"><div class="progress-bar-fill"></div></div>
            <div class="summary-box"></div>
        `;
        uploadQueue.prepend(item);

        if (!allowed.includes(ext)) {
            setItemError(itemId, 'Invalid file type. Please upload .xlsx, .xls or .csv only.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            setItemError(itemId, 'File exceeds the 5 MB size limit.');
            return;
        }

        uploadFile(file, itemId, departmentId, semesterId, defaultSection);
    });

    fileInput.value = '';
}

function setItemError(itemId, message) {
    const item = document.getElementById(itemId);
    item.querySelector('.status-badge').outerHTML = '<span class="badge badge-danger">Error</span>';
    item.querySelector('.summary-box').innerHTML = `<span class="row-err"><i class="fa-solid fa-circle-xmark"></i> ${message}</span>`;
    item.querySelector('.progress-bar-fill').style.width = '100%';
    item.querySelector('.progress-bar-fill').style.background = 'var(--danger)';
    showToast(message, 'error');
}

function uploadFile(file, itemId, departmentId, semesterId, defaultSection) {
    const item = document.getElementById(itemId);
    const fill = item.querySelector('.progress-bar-fill');
    const badge = item.querySelector('.status-badge');

    const formData = new FormData();
    formData.append('file', file);
    formData.append('department_id', departmentId);
    formData.append('semester_id', semesterId);
    formData.append('default_section', defaultSection);

    const xhr = new XMLHttpRequest();
    xhr.open('POST', BASE_URL + '/api/upload_process.php');

    xhr.upload.addEventListener('progress', (e) => {
        if (e.lengthComputable) {
            const pct = Math.min(90, Math.round((e.loaded / e.total) * 90));
            fill.style.width = pct + '%';
        }
    });

    xhr.onload = () => {
        fill.style.width = '100%';
        let res;
        try { res = JSON.parse(xhr.responseText); } catch (err) {
            setItemError(itemId, 'Unexpected server response.');
            return;
        }

        if (!res.success) {
            setItemError(itemId, res.message || 'Upload failed.');
            return;
        }

        const s = res.summary;
        if (s.errors && s.errors.length > 0 && s.imported === 0) {
            badge.outerHTML = '<span class="badge badge-danger">Failed</span>';
        } else if (s.conflicts > 0 || s.errors.length > 0) {
            badge.outerHTML = `<span class="badge badge-warning">⚠ ${s.conflicts} conflict(s)</span>`;
        } else {
            badge.outerHTML = '<span class="badge badge-success">✓ Processed</span>';
        }

        let html = `<div>Total rows: <strong>${s.total}</strong> · Imported: <strong style="color:var(--success);">${s.imported}</strong> · Skipped: <strong style="color:var(--warning);">${s.skipped}</strong> · Conflicts: <strong>${s.conflicts}</strong></div>`;
        if (s.errors && s.errors.length > 0) {
            html += '<div class="mt-8">';
            s.errors.slice(0, 8).forEach(err => {
                html += `<div class="row-err">Row ${err.row}: ${err.reason}</div>`;
            });
            if (s.errors.length > 8) html += `<div class="text-muted">+ ${s.errors.length - 8} more</div>`;
            html += '</div>';
        }
        item.querySelector('.summary-box').innerHTML = html;

        showToast(`${file.name}: ${s.imported} records imported${s.conflicts > 0 ? ', ' + s.conflicts + ' conflicts detected' : ''}.`, s.imported > 0 ? 'success' : 'warning');

        setTimeout(() => location.reload(), 2500);
    };

    xhr.onerror = () => setItemError(itemId, 'Network error while uploading. Please try again.');
    xhr.send(formData);
}

function deleteFile(fileId) {
    if (!confirm('Delete this file record? Associated timetable rows will remain unless you also clean them up.')) return;
    postJSON(BASE_URL + '/api/delete_file.php', { file_id: fileId }).then(res => {
        if (res.success) {
            document.getElementById('file-row-' + fileId)?.remove();
            showToast('File record deleted.', 'success');
        } else {
            showToast(res.message || 'Could not delete file.', 'error');
        }
    });
}
