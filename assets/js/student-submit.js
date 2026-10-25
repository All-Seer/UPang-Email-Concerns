// Student concern form: client validation, submit, success screen.
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]').content;
  const form = document.getElementById('concernForm');
  const formView = document.getElementById('formView');
  const successView = document.getElementById('successView');
  const submitBtn = document.getElementById('submitBtn');
  const formError = document.getElementById('formError');
  const details = form.elements.details;
  const DOMAIN = '@phinmaed.com';

  function setError(name, msg) {
    const field = form.querySelector(`[data-field="${name}"]`);
    const err = form.querySelector(`[data-error="${name}"]`);
    field?.classList.toggle('invalid', !!msg);
    if (err) {
      err.textContent = msg || '';
      err.hidden = !msg;
    }
    const input = form.elements[name];
    if (input && input.setAttribute && !(input instanceof RadioNodeList)) {
      input.setAttribute('aria-invalid', msg ? 'true' : 'false');
    }
  }

  function validate() {
    const v = (n) => (form.elements[n].value || '').trim();
    const errs = {};
    if (!v('concern')) errs.concern = 'Choose what you need help with.';
    if (v('concern') === 'Others' && v('details').length < 10) errs.details = 'Tell us a bit more (at least 10 characters).';
    if (!v('firstName')) errs.firstName = 'First name is required.';
    if (!v('lastName')) errs.lastName = 'Last name is required.';
    if (!v('studentId')) errs.studentId = 'Student ID is required.';
    else if (!/^[0-9A-Za-z-]{6,20}$/.test(v('studentId'))) errs.studentId = 'Use numbers and dashes only, as shown on your ID.';
    const pm = v('phinmaedEmail').toLowerCase();
    if (!pm) errs.phinmaedEmail = 'PHINMAED email is required.';
    else if (!/^[^\s@]+@[^\s@]+$/.test(pm) || !pm.endsWith(DOMAIN)) errs.phinmaedEmail = `Must end with ${DOMAIN}.`;
    const pe = v('personalEmail').toLowerCase();
    if (!pe) errs.personalEmail = 'Personal email is required.';
    else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(pe)) errs.personalEmail = 'Enter a valid email address.';
    else if (pe === pm) errs.personalEmail = 'Use an email other than your PHINMAED account, so we can reach you.';
    return errs;
  }

  function showErrors(errs) {
    ['concern', 'details', 'firstName', 'lastName', 'studentId', 'phinmaedEmail', 'personalEmail'].forEach((n) => setError(n, errs[n]));
    const first = Object.keys(errs)[0];
    if (first) {
      const el = form.elements[first];
      (el instanceof RadioNodeList ? el[0] : el)?.focus();
    }
  }

  // Clear a field's error as soon as the user fixes it.
  form.addEventListener('input', (e) => {
    const name = e.target.name;
    if (!name) return;
    if (form.querySelector(`[data-field="${name}"].invalid`) || (name === 'concern')) {
      const errs = validate();
      setError(name, errs[name]);
    }
  });

  // "Others" makes the description required.
  form.addEventListener('change', (e) => {
    if (e.target.name === 'concern') {
      const others = e.target.value === 'Others';
      document.getElementById('detailsOpt').textContent = others ? '(required)' : '(optional)';
      details.required = others;
      if (others) details.focus();
      else setError('details', '');
    }
  });

  details.addEventListener('input', () => {
    document.getElementById('detailsCount').textContent = details.value.length;
  });

  function setLoading(on) {
    submitBtn.disabled = on;
    submitBtn.querySelector('.btn-text').textContent = on ? 'Submitting…' : 'Submit concern';
    const icon = submitBtn.querySelector('.btn-icon');
    icon.className = 'btn-icon ' + (on ? 'spinner' : 'material-symbols-outlined');
    icon.textContent = on ? '' : 'send';
  }

  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    formError.hidden = true;
    const errs = validate();
    showErrors(errs);
    if (Object.keys(errs).length) return;

    const body = Object.fromEntries(new FormData(form));
    setLoading(true);
    try {
      const res = await fetch('api/submit_concern.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf },
        body: JSON.stringify(body),
      });
      let data;
      try {
        data = await res.json();
      } catch {
        throw new Error('The server sent an unexpected response. Please try again.');
      }

      if (data.success) {
        showSuccess(data.reference, body.studentId);
        return;
      }
      if (data.errors) showErrors(data.errors);
      if (data.duplicate) {
        formError.replaceChildren(
          Object.assign(document.createElement('span'), { className: 'material-symbols-outlined', textContent: 'info' }),
          Object.assign(document.createElement('span'), { textContent: data.message + ' ' }),
          Object.assign(document.createElement('a'), { href: 'track.php?ref=' + encodeURIComponent(data.reference), textContent: 'Track it' })
        );
        formError.className = 'alert alert-warn';
      } else {
        formError.className = 'alert alert-error';
        formError.textContent = data.message || 'Something went wrong. Please try again.';
      }
      formError.hidden = false;
    } catch (err) {
      formError.className = 'alert alert-error';
      formError.textContent = err.message || 'Network error. Check your connection and try again.';
      formError.hidden = false;
    } finally {
      setLoading(false);
    }
  });

  function showSuccess(ref) {
    document.getElementById('refNo').textContent = ref;
    document.getElementById('trackLink').href = 'track.php?ref=' + encodeURIComponent(ref);
    formView.hidden = true;
    successView.hidden = false;
    successView.focus();
    window.scrollTo({ top: 0 });
  }

  document.getElementById('copyRef').addEventListener('click', async (e) => {
    const btn = e.currentTarget;
    try {
      await navigator.clipboard.writeText(document.getElementById('refNo').textContent);
      btn.querySelector('span').textContent = 'check';
      setTimeout(() => (btn.querySelector('span').textContent = 'content_copy'), 1500);
    } catch {
      /* clipboard blocked; the number is visible to copy by hand */
    }
  });

  document.getElementById('newConcern').addEventListener('click', () => {
    form.reset();
    document.getElementById('detailsCount').textContent = '0';
    document.getElementById('detailsOpt').textContent = '(optional)';
    successView.hidden = true;
    formView.hidden = false;
    form.elements.concern[0].focus();
  });
})();
