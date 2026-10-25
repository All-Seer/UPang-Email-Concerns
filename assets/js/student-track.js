// Student status tracker.
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const form = document.getElementById('trackForm');
  const btn = document.getElementById('trackBtn');
  const errorBox = document.getElementById('trackError');
  const result = document.getElementById('result');
  const ORDER = ['Pending', 'In Progress', 'Resolved'];

  const fmt = (s) =>
    s ? new Date(s.replace(' ', 'T')).toLocaleString('en-PH', { month: 'long', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '';

  // Normalize what people paste: spaces and lowercase.
  form.elements.reference.addEventListener('blur', (e) => {
    e.target.value = e.target.value.trim().toUpperCase();
  });

  if (form.elements.reference.value) form.elements.studentId.focus();

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    errorBox.hidden = true;
    const reference = form.elements.reference.value.trim().toUpperCase();
    const studentId = form.elements.studentId.value.trim();
    if (!reference || !studentId) {
      errorBox.textContent = 'Enter both your reference number and student ID.';
      errorBox.hidden = false;
      return;
    }

    btn.disabled = true;
    btn.querySelector('.btn-text').textContent = 'Checking…';
    try {
      const res = await fetch('api/track.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify({ reference, studentId }),
      });
      const data = await res.json().catch(() => ({ success: false, message: 'Unexpected server response.' }));
      if (!data.success) throw new Error(data.message);
      render(data.data);
    } catch (err) {
      result.hidden = true;
      errorBox.textContent = err.message || 'Network error. Try again.';
      errorBox.hidden = false;
    } finally {
      btn.disabled = false;
      btn.querySelector('.btn-text').textContent = 'Check status';
    }
  });

  function render(d) {
    document.getElementById('rRef').textContent = d.reference;
    document.getElementById('rType').textContent = d.type === '2FA' ? '2-step verification' : d.type;
    const pill = document.getElementById('rStatus');
    pill.textContent = d.status;
    pill.className = 'pill status-' + d.status.toLowerCase().replace(/\s+/g, '-');
    document.getElementById('tSubmitted').textContent = fmt(d.submittedAt);
    document.getElementById('tResolved').textContent = d.resolvedAt ? fmt(d.resolvedAt) : 'Not yet';

    const reached = ORDER.indexOf(d.status);
    document.querySelectorAll('#rTimeline li').forEach((li) => {
      const i = ORDER.indexOf(li.dataset.step);
      li.classList.toggle('done', i <= reached);
      li.classList.toggle('current', i === reached);
    });

    const noteWrap = document.getElementById('rNoteWrap');
    noteWrap.hidden = !d.note;
    document.getElementById('rNote').textContent = d.note || '';
    result.hidden = false;
  }
})();
