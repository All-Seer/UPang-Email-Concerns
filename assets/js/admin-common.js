// Shared admin helpers: API calls, toasts, sidebar, keyboard shortcut.
(function () {
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';

  async function api(url, { method = 'GET', body } = {}) {
    const res = await fetch(url, {
      method,
      credentials: 'same-origin',
      headers: body ? { 'Content-Type': 'application/json', 'X-CSRF-Token': csrf } : {},
      body: body ? JSON.stringify(body) : undefined,
    });
    if (res.status === 401) {
      window.location.href = 'login.php';
      throw new Error('Signed out');
    }
    let data;
    try {
      data = await res.json();
    } catch {
      throw new Error(`Server error (${res.status})`);
    }
    if (!res.ok || !data.success) throw new Error(data.message || `Request failed (${res.status})`);
    return data;
  }

  function toast(message, type = 'ok') {
    const stack = document.getElementById('toasts');
    const el = document.createElement('div');
    el.className = `toast toast-${type}`;
    const icon = document.createElement('span');
    icon.className = 'material-symbols-outlined';
    icon.textContent = type === 'error' ? 'error' : 'check_circle';
    const text = document.createElement('span');
    text.textContent = message;
    el.append(icon, text);
    stack.appendChild(el);
    requestAnimationFrame(() => el.classList.add('show'));
    setTimeout(() => {
      el.classList.remove('show');
      setTimeout(() => el.remove(), 300);
    }, type === 'error' ? 5000 : 2800);
  }

  // Mobile sidebar
  const sidebar = document.getElementById('sidebar');
  const scrim = document.getElementById('scrim');
  const setMenu = (open) => {
    sidebar.classList.toggle('open', open);
    scrim.classList.toggle('show', open);
  };
  document.getElementById('menuBtn')?.addEventListener('click', () => setMenu(true));
  scrim?.addEventListener('click', () => setMenu(false));

  // "/" focuses search
  document.addEventListener('keydown', (e) => {
    if (e.key === '/' && !/INPUT|TEXTAREA|SELECT/.test(document.activeElement.tagName)) {
      e.preventDefault();
      document.querySelector('.global-search input')?.focus();
    }
  });

  window.Admin = { api, toast };
})();
