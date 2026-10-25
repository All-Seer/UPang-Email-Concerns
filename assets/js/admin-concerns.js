// Concerns list: filters, live search, pagination, selection, bulk actions, detail drawer.
(function () {
  const { api, toast } = window.Admin;
  const init = window.CONCERNS_INIT;
  const $ = (id) => document.getElementById(id);

  const state = {
    q: init.q,
    type: init.type,
    from: init.from,
    to: init.to,
    sort: init.sort,
    page: 1,
    rows: [],
    selected: new Set(),
    current: null,
  };

  const searchInput = document.querySelector('.global-search input');
  const searchForm = document.querySelector('.global-search');
  $('fType').value = state.type;
  $('fFrom').value = state.from;
  $('fTo').value = state.to;
  $('fSort').value = state.sort;

  /* ---------- formatting ---------- */

  const fmtDate = (s) =>
    s ? new Date(s.replace(' ', 'T')).toLocaleString('en-PH', { month: 'short', day: 'numeric', year: 'numeric', hour: 'numeric', minute: '2-digit' }) : '';

  function relative(s) {
    const diff = (Date.now() - new Date(s.replace(' ', 'T')).getTime()) / 1000;
    if (diff < 60) return 'just now';
    if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
    if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
    if (diff < 86400 * 7) return `${Math.floor(diff / 86400)}d ago`;
    return new Date(s.replace(' ', 'T')).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
  }

  const statusClass = (s) => 'status-' + s.toLowerCase().replace(/\s+/g, '-');

  function el(tag, props = {}, ...children) {
    const n = document.createElement(tag);
    Object.entries(props).forEach(([k, v]) => {
      if (k === 'class') n.className = v;
      else if (k === 'dataset') Object.assign(n.dataset, v);
      else if (k in n) n[k] = v;
      else n.setAttribute(k, v);
    });
    children.flat().forEach((c) => c != null && n.append(c));
    return n;
  }

  /* ---------- query ---------- */

  function params(forExport = false) {
    const p = new URLSearchParams();
    Object.entries(init.fixed).forEach(([k, v]) => p.set(k, v));
    ['q', 'type', 'from', 'to'].forEach((k) => state[k] && p.set(k, state[k]));
    if (state.sort !== 'newest') p.set('sort', state.sort);
    if (!forExport) p.set('page', state.page);
    return p;
  }

  function syncUrl() {
    const p = new URLSearchParams();
    if (init.view !== 'all') p.set('view', init.view);
    ['q', 'type', 'from', 'to'].forEach((k) => state[k] && p.set(k, state[k]));
    if (state.sort !== 'newest') p.set('sort', state.sort);
    history.replaceState(null, '', 'concerns.php' + (p.toString() ? '?' + p : ''));
    $('exportBtn').href = 'export.php?' + params(true);
    $('clearFilters').hidden = !(state.q || state.type || state.from || state.to || state.sort !== 'newest');
  }

  let loadToken = 0;
  async function load() {
    const token = ++loadToken;
    syncUrl();
    try {
      const res = await api('api/concerns.php?' + params());
      if (token !== loadToken) return;
      state.rows = res.data;
      state.page = res.page;
      render(res);
    } catch (e) {
      if (token !== loadToken) return;
      $('rows').replaceChildren(el('tr', {}, el('td', { colSpan: 7, class: 'empty' }, 'Could not load concerns. ' + e.message)));
    }
  }

  /* ---------- render ---------- */

  function render(res) {
    const tbody = $('rows');
    if (!state.rows.length) {
      const filtered = state.q || state.type || state.from || state.to;
      tbody.replaceChildren(
        el('tr', {}, el('td', { colSpan: 7 },
          el('div', { class: 'empty' },
            el('span', { class: 'material-symbols-outlined' }, filtered ? 'search_off' : 'inbox'),
            el('p', {}, filtered ? 'No concerns match these filters.' : 'Nothing here yet.')
          )
        ))
      );
    } else {
      tbody.replaceChildren(...state.rows.map(rowEl));
    }
    const start = (res.page - 1) * res.perPage + 1;
    $('summary').textContent = res.total ? `${start}–${start + state.rows.length - 1} of ${res.total}` : '0 results';
    $('pageInfo').textContent = `Page ${res.page} of ${res.pages}`;
    $('prevPage').disabled = res.page <= 1;
    $('nextPage').disabled = res.page >= res.pages;
    updateSelectionUi();

    if (init.open) {
      const r = state.rows.find((x) => x.id === init.open);
      if (r) openDrawer(r);
      init.open = 0;
    }
  }

  function rowEl(r) {
    const check = el('input', { type: 'checkbox', checked: state.selected.has(r.id), 'aria-label': `Select ${r.name}` });
    check.addEventListener('click', (e) => e.stopPropagation());
    check.addEventListener('change', () => {
      check.checked ? state.selected.add(r.id) : state.selected.delete(r.id);
      tr.classList.toggle('selected', check.checked);
      updateSelectionUi();
    });

    const tr = el('tr', { class: state.selected.has(r.id) ? 'selected' : '', tabIndex: 0, dataset: { id: r.id } },
      el('td', { class: 'col-check' }, check),
      el('td', {},
        el('div', { class: 'cell-main' }, r.name),
        el('div', { class: 'cell-sub mono' }, r.studentId)
      ),
      el('td', {},
        el('div', { class: 'cell-main' }, r.type),
        el('div', { class: 'cell-sub mono' }, r.reference)
      ),
      el('td', { class: 'hide-md cell-email' }, r.phinmaedEmail),
      el('td', { title: fmtDate(r.submittedAt) }, relative(r.submittedAt)),
      el('td', {}, el('span', { class: `pill ${statusClass(r.status)}` }, r.status)),
      el('td', { class: 'col-actions' },
        el('span', { class: 'material-symbols-outlined chevron' }, 'chevron_right')
      )
    );
    tr.addEventListener('click', () => openDrawer(r));
    tr.addEventListener('keydown', (e) => {
      if (e.key === 'Enter') openDrawer(r);
    });
    return tr;
  }

  function updateSelectionUi() {
    const n = state.selected.size;
    $('bulkBar').hidden = n === 0;
    $('bulkCount').textContent = n;
    const pageIds = state.rows.map((r) => r.id);
    const onPage = pageIds.filter((id) => state.selected.has(id)).length;
    $('checkAll').checked = pageIds.length > 0 && onPage === pageIds.length;
    $('checkAll').indeterminate = onPage > 0 && onPage < pageIds.length;
  }

  /* ---------- filters ---------- */

  let debounce;
  searchForm.addEventListener('submit', (e) => {
    e.preventDefault();
    clearTimeout(debounce);
    state.q = searchInput.value.trim();
    state.page = 1;
    load();
  });
  searchInput.addEventListener('input', () => {
    clearTimeout(debounce);
    debounce = setTimeout(() => {
      state.q = searchInput.value.trim();
      state.page = 1;
      load();
    }, 250);
  });

  [['fType', 'type'], ['fFrom', 'from'], ['fTo', 'to'], ['fSort', 'sort']].forEach(([id, key]) => {
    $(id).addEventListener('change', (e) => {
      state[key] = e.target.value;
      state.page = 1;
      load();
    });
  });

  $('clearFilters').addEventListener('click', () => {
    Object.assign(state, { q: '', type: '', from: '', to: '', sort: 'newest', page: 1 });
    searchInput.value = '';
    $('fType').value = '';
    $('fFrom').value = '';
    $('fTo').value = '';
    $('fSort').value = 'newest';
    load();
  });

  $('prevPage').addEventListener('click', () => { state.page--; load(); });
  $('nextPage').addEventListener('click', () => { state.page++; load(); });

  $('checkAll').addEventListener('change', (e) => {
    state.rows.forEach((r) => (e.target.checked ? state.selected.add(r.id) : state.selected.delete(r.id)));
    load();
  });

  /* ---------- bulk ---------- */

  $('bulkClear').addEventListener('click', () => {
    state.selected.clear();
    load();
  });

  document.querySelectorAll('[data-bulk]').forEach((btn) =>
    btn.addEventListener('click', async () => {
      const ids = [...state.selected];
      try {
        const res = await api('api/update.php', { method: 'POST', body: { ids, status: btn.dataset.bulk } });
        toast(`${res.updated} concern${res.updated === 1 ? '' : 's'} marked ${btn.dataset.bulk.toLowerCase()}`);
        state.selected.clear();
        load();
      } catch (e) {
        toast(e.message, 'error');
      }
    })
  );

  $('bulkDelete').addEventListener('click', async () => {
    const ids = [...state.selected];
    if (!(await confirmDialog(`Delete ${ids.length} concern${ids.length === 1 ? '' : 's'}?`, "This permanently removes them. It can't be undone."))) return;
    try {
      const res = await api('api/delete.php', { method: 'POST', body: { ids } });
      toast(`Deleted ${res.deleted}`);
      state.selected.clear();
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  });

  /* ---------- confirm dialog ---------- */

  function confirmDialog(title, text, okLabel = 'Delete') {
    const d = $('confirmDialog');
    $('confirmTitle').textContent = title;
    $('confirmText').textContent = text;
    $('confirmOk').textContent = okLabel;
    d.returnValue = '';
    d.showModal();
    return new Promise((resolve) => d.addEventListener('close', () => resolve(d.returnValue === 'ok'), { once: true }));
  }

  /* ---------- drawer ---------- */

  const drawer = $('drawer');
  let drawerStatus = null;
  let lastFocus = null;

  function setSeg(status) {
    drawerStatus = status;
    document.querySelectorAll('#dStatus button').forEach((b) => {
      const on = b.dataset.status === status;
      b.classList.toggle('on', on);
      b.setAttribute('aria-checked', on);
    });
  }

  function openDrawer(r) {
    state.current = r;
    lastFocus = document.activeElement;
    $('dRef').textContent = r.reference;
    $('dName').textContent = r.name;
    $('dType').textContent = r.type;
    $('dSid').textContent = r.studentId;
    $('dPhinmaed').textContent = r.phinmaedEmail;
    $('dPhinmaed').href = 'mailto:' + r.phinmaedEmail;
    $('dPersonal').textContent = r.personalEmail;
    $('dPersonal').href = 'mailto:' + r.personalEmail;
    $('dSubmitted').textContent = fmtDate(r.submittedAt);
    $('dResolvedRow').hidden = !r.resolvedAt;
    $('dResolved').textContent = fmtDate(r.resolvedAt);
    $('dHandledRow').hidden = !r.handledBy;
    $('dHandled').textContent = r.handledBy ? `${r.handledBy} · ${fmtDate(r.updatedAt)}` : '';
    $('dDetailsWrap').hidden = !r.details;
    $('dDetails').textContent = r.details || '';
    $('dNote').value = r.note || '';
    const subject = encodeURIComponent(`Re: ${r.type} concern (${r.reference})`);
    const greet = encodeURIComponent(`Hi ${r.firstName},\n\n`);
    $('dMail').href = `mailto:${r.personalEmail}?subject=${subject}&body=${greet}`;
    setSeg(r.status);
    drawer.classList.add('open');
    drawer.setAttribute('aria-hidden', 'false');
    $('scrim').classList.add('show', 'for-drawer');
    $('drawerClose').focus();
  }

  function closeDrawer() {
    drawer.classList.remove('open');
    drawer.setAttribute('aria-hidden', 'true');
    $('scrim').classList.remove('show', 'for-drawer');
    state.current = null;
    lastFocus?.focus?.();
  }

  $('drawerClose').addEventListener('click', closeDrawer);
  $('scrim').addEventListener('click', () => state.current && closeDrawer());
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && state.current && !$('confirmDialog').open) closeDrawer();
  });
  document.querySelectorAll('#dStatus button').forEach((b) => b.addEventListener('click', () => setSeg(b.dataset.status)));

  document.querySelectorAll('.copy-btn').forEach((b) =>
    b.addEventListener('click', async () => {
      try {
        await navigator.clipboard.writeText($(b.dataset.copy).textContent);
        toast('Copied');
      } catch {
        toast('Copy failed', 'error');
      }
    })
  );

  $('dSave').addEventListener('click', async () => {
    const r = state.current;
    const btn = $('dSave');
    btn.disabled = true;
    try {
      await api('api/update.php', { method: 'POST', body: { ids: [r.id], status: drawerStatus, note: $('dNote').value } });
      toast(drawerStatus !== r.status ? `Marked ${drawerStatus.toLowerCase()}` : 'Saved');
      closeDrawer();
      load();
    } catch (e) {
      toast(e.message, 'error');
    } finally {
      btn.disabled = false;
    }
  });

  $('dDelete').addEventListener('click', async () => {
    const r = state.current;
    if (!(await confirmDialog('Delete this concern?', `${r.reference} from ${r.name} will be permanently removed.`))) return;
    try {
      await api('api/delete.php', { method: 'POST', body: { ids: [r.id] } });
      toast('Concern deleted');
      state.selected.delete(r.id);
      closeDrawer();
      load();
    } catch (e) {
      toast(e.message, 'error');
    }
  });

  load();
})();
