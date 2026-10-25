<?php
require_once __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/student_layout.php';

student_start('Track a concern', 'track');
?>
<section class="panel-inner">
    <header class="panel-head">
        <h2>Track a concern</h2>
        <p class="muted">Enter the reference number you got after submitting, plus your student ID.</p>
    </header>

    <form id="trackForm" class="track-form" novalidate>
        <label class="field">
            <span class="field-label">Reference number</span>
            <input type="text" name="reference" class="mono-input" placeholder="EC-251025-AB12" autocomplete="off" required value="<?= e($_GET['ref'] ?? '') ?>">
        </label>
        <label class="field">
            <span class="field-label">Student ID</span>
            <input type="text" name="studentId" placeholder="0000-0000-000000" required>
        </label>
        <button type="submit" class="btn btn-primary" id="trackBtn">
            <span class="material-symbols-outlined">search</span><span class="btn-text">Check status</span>
        </button>
    </form>

    <div class="alert alert-error" id="trackError" role="alert" hidden></div>

    <article class="result" id="result" hidden aria-live="polite">
        <header class="result-head">
            <div>
                <p class="eyebrow mono" id="rRef"></p>
                <h3 id="rType"></h3>
            </div>
            <span class="pill" id="rStatus"></span>
        </header>

        <ol class="timeline" id="rTimeline">
            <li data-step="Pending">
                <span class="dot"></span>
                <div><strong>Received</strong><span id="tSubmitted" class="muted"></span></div>
            </li>
            <li data-step="In Progress">
                <span class="dot"></span>
                <div><strong>Being worked on</strong><span class="muted">The admin office is handling your account</span></div>
            </li>
            <li data-step="Resolved">
                <span class="dot"></span>
                <div><strong>Resolved</strong><span id="tResolved" class="muted"></span></div>
            </li>
        </ol>

        <div class="response" id="rNoteWrap" hidden>
            <h4><span class="material-symbols-outlined">support_agent</span>Response from the admin office</h4>
            <p id="rNote"></p>
        </div>
    </article>
</section>
<?php student_end('assets/js/student-track.js'); ?>
