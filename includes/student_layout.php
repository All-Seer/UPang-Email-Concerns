<?php
// Shared shell for the public student pages.

function student_start(string $title, string $active): void
{
    ?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="<?= e(csrf_token()) ?>">
    <meta name="description" content="Submit and track PHINMAED email account concerns for PHINMA University of Pangasinan, Urdaneta.">
    <title><?= e($title) ?> · PHINMA UPang Email Concerns</title>
    <link rel="icon" href="public/upangnavlogo.svg" type="image/svg+xml">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0..1,0&display=block" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/base.css">
    <link rel="stylesheet" href="assets/css/student.css">
</head>

<body class="student">
    <aside class="hero">
        <div class="hero-inner">
            <a href="index.php" class="hero-brand">
                <img src="public/upanglogoo-1.png" alt="PHINMA University of Pangasinan" width="64" height="64">
                <span>PHINMA UPang<br><small>Urdaneta</small></span>
            </a>
            <div class="hero-copy">
                <h1>Email Concern Desk</h1>
                <p>Locked out of your PHINMAED account? Tell us what's wrong and the admin office will sort it out.</p>
            </div>
            <ol class="hero-steps">
                <li><span>1</span>Fill in your details and concern</li>
                <li><span>2</span>Save the reference number we give you</li>
                <li><span>3</span>Track progress anytime with that number</li>
            </ol>
            <nav class="tabs" aria-label="Student pages">
                <a href="index.php" class="<?= $active === 'submit' ? 'on' : '' ?>"><span class="material-symbols-outlined">edit_square</span>Submit a concern</a>
                <a href="track.php" class="<?= $active === 'track' ? 'on' : '' ?>"><span class="material-symbols-outlined">travel_explore</span>Track a concern</a>
            </nav>
        </div>
        <p class="hero-foot">#0587 MacArthur Highway, Brgy. Nancayasan, Urdaneta City, Pangasinan</p>
    </aside>

    <main class="panel">
<?php
}

function student_end(string $script): void
{
    ?>
        <footer class="panel-foot">© <?= date('Y') ?> Three Inch Geng · PHINMA University of Pangasinan</footer>
    </main>
    <script src="<?= e($script) ?>"></script>
</body>

</html>
<?php
}
