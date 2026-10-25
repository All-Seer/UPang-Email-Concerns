<?php
require_once __DIR__ . '/includes/bootstrap.php';
require __DIR__ . '/includes/student_layout.php';

student_start('Submit a concern', 'submit');
?>
<section class="panel-inner" id="formView">
    <header class="panel-head">
        <h2>Submit a concern</h2>
        <p class="muted">All fields are required unless marked optional.</p>
    </header>

    <form id="concernForm" novalidate>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">

        <fieldset>
            <legend>What do you need help with?</legend>
            <div class="type-grid" role="radiogroup" data-field="concern">
                <?php foreach (CONCERN_TYPES as $type => $meta): ?>
                    <label class="type-card">
                        <input type="radio" name="concern" value="<?= e($type) ?>" required>
                        <span class="material-symbols-outlined"><?= e($meta['icon']) ?></span>
                        <span class="type-title"><?= e($type === '2FA' ? '2-step verification' : $type) ?></span>
                        <span class="type-hint"><?= e($meta['hint']) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <p class="field-error" data-error="concern" hidden></p>

            <label class="field" data-field="details">
                <span class="field-label">Describe the problem <span class="opt" id="detailsOpt">(optional)</span></span>
                <textarea name="details" rows="3" maxlength="1000" placeholder="e.g. I get an 'account disabled' message when signing in"></textarea>
                <span class="field-row">
                    <span class="field-error" data-error="details" hidden></span>
                    <span class="field-hint counter"><span id="detailsCount">0</span>/1000</span>
                </span>
            </label>
        </fieldset>

        <fieldset>
            <legend>About you</legend>
            <div class="grid-3">
                <label class="field" data-field="firstName">
                    <span class="field-label">First name</span>
                    <input type="text" name="firstName" autocomplete="given-name" maxlength="50" required>
                    <span class="field-error" data-error="firstName" hidden></span>
                </label>
                <label class="field" data-field="middleName">
                    <span class="field-label">Middle name <span class="opt">(optional)</span></span>
                    <input type="text" name="middleName" autocomplete="additional-name" maxlength="50">
                </label>
                <label class="field" data-field="lastName">
                    <span class="field-label">Last name</span>
                    <input type="text" name="lastName" autocomplete="family-name" maxlength="50" required>
                    <span class="field-error" data-error="lastName" hidden></span>
                </label>
            </div>
            <label class="field half" data-field="studentId">
                <span class="field-label">Student ID</span>
                <input type="text" name="studentId" inputmode="numeric" placeholder="0000-0000-000000" maxlength="20" pattern="[0-9A-Za-z\-]{6,20}" required>
                <span class="field-error" data-error="studentId" hidden></span>
            </label>
        </fieldset>

        <fieldset>
            <legend>Email accounts</legend>
            <div class="grid-2">
                <label class="field" data-field="phinmaedEmail">
                    <span class="field-label">PHINMAED email</span>
                    <input type="email" name="phinmaedEmail" placeholder="juan.delacruz.up@phinmaed.com" autocomplete="off" required>
                    <span class="field-hint">The school account you need help with</span>
                    <span class="field-error" data-error="phinmaedEmail" hidden></span>
                </label>
                <label class="field" data-field="personalEmail">
                    <span class="field-label">Personal email</span>
                    <input type="email" name="personalEmail" placeholder="you@gmail.com" autocomplete="email" required>
                    <span class="field-hint">Where we'll send updates</span>
                    <span class="field-error" data-error="personalEmail" hidden></span>
                </label>
            </div>
        </fieldset>

        <div class="alert alert-error" id="formError" role="alert" hidden></div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary" id="submitBtn">
                <span class="btn-text">Submit concern</span>
                <span class="btn-icon material-symbols-outlined">send</span>
            </button>
        </div>
    </form>
</section>

<section class="panel-inner success" id="successView" hidden tabindex="-1">
    <img src="public/check.gif" alt="" class="success-img" width="120" height="116">
    <h2>Concern submitted</h2>
    <p class="muted">Keep this reference number. You'll need it with your student ID to check the status.</p>
    <div class="ref-box">
        <span class="mono" id="refNo"></span>
        <button type="button" class="icon-btn" id="copyRef" aria-label="Copy reference number">
            <span class="material-symbols-outlined">content_copy</span>
        </button>
    </div>
    <p class="muted small">The admin office may contact you through your personal email.</p>
    <div class="success-actions">
        <a class="btn btn-primary" id="trackLink" href="track.php"><span class="material-symbols-outlined">travel_explore</span>Track this concern</a>
        <button class="btn btn-outline" id="newConcern" type="button">Submit another</button>
    </div>
</section>
<?php student_end('assets/js/student-submit.js'); ?>
