<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();
require __DIR__ . '/partials/layout.php';

$views = [
    'all'      => ['All concerns', []],
    'pending'  => ['Pending', ['status' => 'Pending']],
    'progress' => ['In progress', ['status' => 'In Progress']],
    'resolved' => ['Resolved', ['status' => 'Resolved']],
    'week'     => ['This week', ['range' => 'week']],
];
$view = array_key_exists($_GET['view'] ?? '', $views) ? $_GET['view'] : 'all';
[$title, $fixed] = $views[$view];

$initial = [
    'view'  => $view,
    'fixed' => $fixed,
    'q'     => (string) ($_GET['q'] ?? ''),
    'type'  => (string) ($_GET['type'] ?? ''),
    'from'  => (string) ($_GET['from'] ?? ''),
    'to'    => (string) ($_GET['to'] ?? ''),
    'sort'  => ($_GET['sort'] ?? '') === 'oldest' ? 'oldest' : 'newest',
    'open'  => (int) ($_GET['open'] ?? 0),
    'statuses' => STATUSES,
];

layout_start($title, $view);
?>

<div class="toolbar">
    <div class="toolbar-filters">
        <label class="select-wrap">
            <span class="sr-only">Concern type</span>
            <select id="fType">
                <option value="">All types</option>
                <?php foreach (CONCERN_TYPES as $t => $_): ?>
                    <option value="<?= e($t) ?>"><?= e($t) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label class="date-wrap">
            <span>From</span>
            <input type="date" id="fFrom">
        </label>
        <label class="date-wrap">
            <span>To</span>
            <input type="date" id="fTo">
        </label>
        <label class="select-wrap">
            <span class="sr-only">Sort</span>
            <select id="fSort">
                <option value="newest">Newest first</option>
                <option value="oldest">Oldest first</option>
            </select>
        </label>
        <button class="btn btn-ghost btn-sm" id="clearFilters" hidden>
            <span class="material-symbols-outlined">filter_alt_off</span>Clear
        </button>
    </div>
    <a class="btn btn-outline btn-sm" id="exportBtn" href="export.php">
        <span class="material-symbols-outlined">download</span>Export CSV
    </a>
</div>

<div class="bulk-bar" id="bulkBar" hidden>
    <span><strong id="bulkCount">0</strong> selected</span>
    <div class="bulk-actions">
        <button class="btn btn-sm btn-tonal" data-bulk="In Progress"><span class="material-symbols-outlined">autorenew</span>In progress</button>
        <button class="btn btn-sm btn-tonal" data-bulk="Resolved"><span class="material-symbols-outlined">task_alt</span>Resolve</button>
        <button class="btn btn-sm btn-tonal" data-bulk="Pending"><span class="material-symbols-outlined">undo</span>Reopen</button>
        <button class="btn btn-sm btn-danger-soft" id="bulkDelete"><span class="material-symbols-outlined">delete</span>Delete</button>
    </div>
    <button class="icon-btn" id="bulkClear" aria-label="Clear selection"><span class="material-symbols-outlined">close</span></button>
</div>

<div class="card table-card">
    <div class="table-scroll">
        <table class="data-table" id="table">
            <thead>
                <tr>
                    <th class="col-check"><input type="checkbox" id="checkAll" aria-label="Select all on this page"></th>
                    <th>Student</th>
                    <th>Concern</th>
                    <th class="hide-md">PHINMAED email</th>
                    <th>Submitted</th>
                    <th>Status</th>
                    <th class="col-actions"><span class="sr-only">Actions</span></th>
                </tr>
            </thead>
            <tbody id="rows">
                <tr class="loading-row"><td colspan="7"><span class="spinner"></span> Loading…</td></tr>
            </tbody>
        </table>
    </div>
    <div class="table-foot">
        <span class="muted" id="summary"></span>
        <div class="pager">
            <button class="icon-btn" id="prevPage" aria-label="Previous page"><span class="material-symbols-outlined">chevron_left</span></button>
            <span id="pageInfo"></span>
            <button class="icon-btn" id="nextPage" aria-label="Next page"><span class="material-symbols-outlined">chevron_right</span></button>
        </div>
    </div>
</div>

<!-- Detail drawer -->
<aside class="drawer" id="drawer" aria-hidden="true" aria-labelledby="dName" role="dialog">
    <header class="drawer-head">
        <div>
            <p class="eyebrow mono" id="dRef"></p>
            <h2 id="dName"></h2>
        </div>
        <button class="icon-btn" id="drawerClose" aria-label="Close"><span class="material-symbols-outlined">close</span></button>
    </header>
    <div class="drawer-body">
        <div class="seg" id="dStatus" role="radiogroup" aria-label="Status">
            <?php foreach (STATUSES as $s): ?>
                <button type="button" role="radio" data-status="<?= e($s) ?>" class="<?= status_class($s) ?>"><?= e($s) ?></button>
            <?php endforeach; ?>
        </div>

        <dl class="detail-list">
            <div><dt>Concern</dt><dd id="dType"></dd></div>
            <div><dt>Student ID</dt><dd><span id="dSid" class="mono"></span><button class="copy-btn" data-copy="dSid" aria-label="Copy student ID"><span class="material-symbols-outlined">content_copy</span></button></dd></div>
            <div><dt>PHINMAED email</dt><dd><a id="dPhinmaed"></a><button class="copy-btn" data-copy="dPhinmaed" aria-label="Copy PHINMAED email"><span class="material-symbols-outlined">content_copy</span></button></dd></div>
            <div><dt>Personal email</dt><dd><a id="dPersonal"></a><button class="copy-btn" data-copy="dPersonal" aria-label="Copy personal email"><span class="material-symbols-outlined">content_copy</span></button></dd></div>
            <div><dt>Submitted</dt><dd id="dSubmitted"></dd></div>
            <div id="dResolvedRow"><dt>Resolved</dt><dd id="dResolved"></dd></div>
            <div id="dHandledRow"><dt>Last handled by</dt><dd id="dHandled"></dd></div>
        </dl>

        <div class="detail-block" id="dDetailsWrap">
            <h3>Student's message</h3>
            <p id="dDetails" class="message"></p>
        </div>

        <div class="detail-block">
            <label class="field">
                <span class="field-label">Response to student</span>
                <textarea id="dNote" rows="4" maxlength="2000" placeholder="e.g. Password reset. Temporary password sent to your personal email."></textarea>
                <span class="field-hint">Shown to the student when they track this concern.</span>
            </label>
        </div>
    </div>
    <footer class="drawer-foot">
        <button class="btn btn-danger-soft" id="dDelete"><span class="material-symbols-outlined">delete</span>Delete</button>
        <a class="btn btn-outline" id="dMail" target="_blank" rel="noopener"><span class="material-symbols-outlined">mail</span>Email student</a>
        <button class="btn btn-primary" id="dSave">Save</button>
    </footer>
</aside>

<dialog class="confirm" id="confirmDialog">
    <form method="dialog">
        <h2 id="confirmTitle">Delete concern?</h2>
        <p id="confirmText">This can't be undone.</p>
        <div class="confirm-actions">
            <button class="btn btn-ghost" value="cancel">Cancel</button>
            <button class="btn btn-danger" value="ok" id="confirmOk">Delete</button>
        </div>
    </form>
</dialog>

<script>window.CONCERNS_INIT = <?= json_encode($initial, JSON_HEX_TAG | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>;</script>
<?php layout_end(['../assets/js/admin-concerns.js']); ?>
