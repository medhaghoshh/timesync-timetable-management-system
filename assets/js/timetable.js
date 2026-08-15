/* =========================================================
   TimeSync - Timetable page interactions
   ========================================================= */

function formatTime12(t) {
    if (!t) return '';
    const [h, m] = t.split(':').map(Number);
    const period = h >= 12 ? 'PM' : 'AM';
    const hour12 = h % 12 === 0 ? 12 : h % 12;
    return `${hour12}:${String(m).padStart(2, '0')} ${period}`;
}

function openClassModal(cls) {
    document.getElementById('cmSubject').textContent = cls.subject_name;
    document.getElementById('cmCode').textContent = cls.subject_code || '—';
    document.getElementById('cmFaculty').textContent = cls.faculty_name;
    document.getElementById('cmRoom').textContent = cls.room_number + (cls.building ? ' · ' + cls.building : '');
    document.getElementById('cmDay').textContent = cls.day;
    document.getElementById('cmStart').textContent = formatTime12(cls.start_time);
    document.getElementById('cmEnd').textContent = formatTime12(cls.end_time);
    document.getElementById('cmSection').textContent = cls.section_name || '—';
    openModal('classModal');
}

function switchDay(day, btn) {
    document.querySelectorAll('.day-tab').forEach(t => t.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('[data-day-panel]').forEach(p => {
        p.style.display = p.getAttribute('data-day-panel') === day ? '' : 'none';
    });
}
