<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// If already logged in, skip the landing page.
if (isLoggedIn()) {
    header('Location: ' . BASE_URL . '/' . (currentRole() === 'admin' ? 'admin/dashboard.php' : 'student/dashboard.php'));
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>TimeSync · Your timetable, simplified</title>
<link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
    .landing-nav { position: sticky; top: 0; background: rgba(255,255,255,.85); backdrop-filter: blur(10px); border-bottom: 1px solid var(--border); z-index: 200; }
    .landing-nav .container { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; }
    .landing-nav .nav-links { display: flex; gap: 30px; align-items: center; }
    .landing-nav .nav-links a { font-weight: 500; font-size: 0.9rem; color: var(--muted); }
    .landing-nav .nav-links a:hover { color: var(--primary); }
    .logo-mark { display: flex; align-items: center; gap: 9px; font-family: var(--font-display); font-weight: 700; font-size: 1.2rem; }
    .logo-mark .logo-icon { width: 32px; height: 32px; border-radius: 9px; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-size: .85rem; }

    .hero { padding: 90px 24px 60px; }
    .hero .container { display: grid; grid-template-columns: 1fr 1fr; gap: 60px; align-items: center; }
    .hero-eyebrow { display: inline-flex; align-items: center; gap: 8px; background: var(--primary-light); color: var(--primary); font-size: 0.8rem; font-weight: 600; padding: 6px 14px; border-radius: 999px; margin-bottom: 20px; }
    .hero h1 { font-size: 3.1rem; line-height: 1.08; margin-bottom: 20px; }
    .hero h1 span { color: var(--primary); }
    .hero p.lead { font-size: 1.1rem; color: var(--muted); margin-bottom: 30px; max-width: 480px; }
    .hero-actions { display: flex; gap: 14px; }

    .hero-visual { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; box-shadow: var(--shadow-lg); padding: 20px; }
    .hero-visual .mini-grid { display: grid; grid-template-columns: 50px repeat(5,1fr); gap: 4px; }
    .hero-visual .mh { font-size: 0.65rem; text-align:center; color: var(--muted); font-weight: 700; padding: 6px 2px; }
    .hero-visual .mc { background: #FAFAFC; border-radius: 6px; min-height: 34px; }
    .hero-visual .mc.filled { background: var(--primary-light); border-left: 3px solid var(--primary); }
    .hero-visual .mc.filled.alt { background: #E6FBF8; border-left-color: var(--accent); }

    .stats-band { background: var(--text); color: #fff; padding: 50px 24px; }
    .stats-band .container { display: grid; grid-template-columns: repeat(3,1fr); text-align: center; gap: 20px; }
    .stats-band .stat-num { font-family: var(--font-display); font-size: 2.6rem; font-weight: 700; color: #fff; }
    .stats-band .stat-lab { color: #9CA3AF; font-size: 0.9rem; margin-top: 4px; }

    .section { padding: 80px 24px; }
    .section-head { text-align: center; max-width: 620px; margin: 0 auto 46px; }
    .section-head .eyebrow { color: var(--primary); font-weight: 700; font-size: 0.8rem; text-transform: uppercase; letter-spacing: .06em; }
    .section-head h2 { font-size: 2.1rem; margin: 10px 0 12px; }

    .feature-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 22px; }
    .feature-card { padding: 26px; }
    .feature-card .f-icon { width: 46px; height: 46px; border-radius: 12px; background: var(--primary-light); color: var(--primary); display: flex; align-items: center; justify-content: center; font-size: 1.1rem; margin-bottom: 16px; }
    .feature-card h3 { font-size: 1.05rem; margin-bottom: 8px; }
    .feature-card p { font-size: 0.9rem; margin: 0; }

    .steps-wrap { display: grid; grid-template-columns: repeat(4,1fr); gap: 22px; }
    .step-card { text-align: center; padding: 10px; }
    .step-num { width: 44px; height: 44px; border-radius: 50%; background: var(--primary); color: #fff; display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; margin: 0 auto 16px; }
    .step-card h4 { font-size: 1rem; margin-bottom: 6px; }
    .step-card p { font-size: 0.85rem; margin: 0; }

    .final-cta { background: var(--primary); border-radius: 24px; padding: 60px 40px; text-align: center; margin: 0 24px; color: #fff; }
    .final-cta h2 { color: #fff; font-size: 2rem; margin-bottom: 12px; }
    .final-cta p { color: #E0E1FF; margin-bottom: 26px; }
    .final-cta .btn-outline { background: transparent; color: #fff; border-color: rgba(255,255,255,.4); }
    .final-cta .btn-outline:hover { background: rgba(255,255,255,.12); border-color: #fff; }
    .final-cta .btn-primary { background: #fff; color: var(--primary); }
    .final-cta .btn-primary:hover { background: #F0F0FF; }

    footer.site-footer { padding: 40px 24px; border-top: 1px solid var(--border); margin-top: 60px; }
    footer.site-footer .container { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; }
    footer.site-footer p { margin: 0; font-size: 0.85rem; }

    @media (max-width: 900px) {
        .hero .container { grid-template-columns: 1fr; }
        .feature-grid, .steps-wrap { grid-template-columns: repeat(2,1fr); }
        .stats-band .container { grid-template-columns: 1fr; gap: 30px; }
        .hero h1 { font-size: 2.3rem; }
        .landing-nav .nav-links { display: none; }
    }
    @media (max-width: 560px) {
        .feature-grid, .steps-wrap { grid-template-columns: 1fr; }
    }
</style>
</head>
<body>

<nav class="landing-nav">
    <div class="container">
        <div class="logo-mark"><span class="logo-icon"><i class="fa-solid fa-calendar-days"></i></span>TimeSync</div>
        <div class="nav-links">
            <a href="#home">Home</a>
            <a href="#features">Features</a>
            <a href="#how-it-works">How It Works</a>
            <a href="#about">About</a>
            <a href="<?= BASE_URL ?>/login.php">Login</a>
        </div>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Get Started</a>
    </div>
</nav>

<section class="hero" id="home">
    <div class="container">
        <div>
            <span class="hero-eyebrow"><i class="fa-solid fa-sparkles"></i> Built for college timetables</span>
            <h1>Your timetable.<br><span>Simplified.</span></h1>
            <p class="lead">Turn dozens of college spreadsheets into one personalized timetable — automatically imported, conflict-checked, and always up to date.</p>
            <div class="hero-actions">
                <a href="#how-it-works" class="btn btn-outline">View Demo</a>
                <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Get Started</a>
            </div>
        </div>
        <div class="hero-visual">
            <div class="flex-between mb-16">
                <strong style="font-family:var(--font-display);font-size:.95rem;">CSE · Sem 5 · Section A</strong>
                <span class="badge badge-success"><i class="fa-solid fa-circle-check"></i> No conflicts</span>
            </div>
            <div class="mini-grid">
                <div></div><div class="mh">Mon</div><div class="mh">Tue</div><div class="mh">Wed</div><div class="mh">Thu</div><div class="mh">Fri</div>
                <div class="mh" style="text-align:right;padding-right:6px;">9</div>
                <div class="mc filled"></div><div class="mc"></div><div class="mc filled"></div><div class="mc"></div><div class="mc filled alt"></div>
                <div class="mh" style="text-align:right;padding-right:6px;">10</div>
                <div class="mc"></div><div class="mc filled alt"></div><div class="mc"></div><div class="mc filled"></div><div class="mc"></div>
                <div class="mh" style="text-align:right;padding-right:6px;">11</div>
                <div class="mc filled alt"></div><div class="mc"></div><div class="mc filled"></div><div class="mc"></div><div class="mc filled"></div>
                <div class="mh" style="text-align:right;padding-right:6px;">12</div>
                <div class="mc"></div><div class="mc filled"></div><div class="mc"></div><div class="mc"></div><div class="mc"></div>
            </div>
        </div>
    </div>
</section>

<section class="stats-band">
    <div class="container">
        <div><div class="stat-num">40+</div><div class="stat-lab">Timetable Files</div></div>
        <div><div class="stat-num">1</div><div class="stat-lab">Personalized Schedule</div></div>
        <div><div class="stat-num">0</div><div class="stat-lab">Manual Searching</div></div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <div class="eyebrow">The Problem</div>
        <h2>40 spreadsheets shouldn't mean 40 searches.</h2>
        <p>Every semester, departments publish dozens of separate Excel timetables. Students waste time hunting through files that were never meant to be searched by hand.</p>
    </div>
</section>

<section class="section" id="features" style="background:var(--surface); border-top:1px solid var(--border); border-bottom:1px solid var(--border);">
    <div class="section-head">
        <div class="eyebrow">Features</div>
        <h2>Everything the registrar's office needs</h2>
    </div>
    <div class="container">
        <div class="feature-grid">
            <div class="card feature-card"><div class="f-icon"><i class="fa-solid fa-file-excel"></i></div><h3>Smart Excel Import</h3><p>Upload spreadsheets in whatever format your department already uses — headers are recognized automatically.</p></div>
            <div class="card feature-card"><div class="f-icon"><i class="fa-solid fa-user-check"></i></div><h3>Personalized Timetables</h3><p>Students are mapped to department, semester and section, and see only the classes that apply to them.</p></div>
            <div class="card feature-card"><div class="f-icon"><i class="fa-solid fa-triangle-exclamation"></i></div><h3>Conflict Detection</h3><p>Overlapping section, faculty and room bookings are flagged automatically with a severity rating.</p></div>
            <div class="card feature-card"><div class="f-icon"><i class="fa-solid fa-layer-group"></i></div><h3>Section Mapping</h3><p>Departments, semesters and sections stay properly normalized instead of duplicated across sheets.</p></div>
            <div class="card feature-card"><div class="f-icon"><i class="fa-solid fa-door-open"></i></div><h3>Faculty & Room Tracking</h3><p>See which faculty and rooms are busiest, and catch double-bookings before they cause problems.</p></div>
            <div class="card feature-card"><div class="f-icon"><i class="fa-solid fa-magnifying-glass"></i></div><h3>Instant Search</h3><p>Find any subject, faculty member or room across the entire timetable in seconds.</p></div>
        </div>
    </div>
</section>

<section class="section" id="how-it-works">
    <div class="section-head">
        <div class="eyebrow">How It Works</div>
        <h2>From spreadsheet to schedule in four steps</h2>
    </div>
    <div class="container">
        <div class="steps-wrap">
            <div class="step-card"><div class="step-num">1</div><h4>Upload</h4><p>Admin uploads timetable spreadsheets for each section.</p></div>
            <div class="step-card"><div class="step-num">2</div><h4>Process</h4><p>The system reads, validates and normalizes every row.</p></div>
            <div class="step-card"><div class="step-num">3</div><h4>Detect</h4><p>Section, faculty and room conflicts are flagged instantly.</p></div>
            <div class="step-card"><div class="step-num">4</div><h4>Personalize</h4><p>Every student automatically gets their own weekly timetable.</p></div>
        </div>
    </div>
</section>

<section class="section" id="about">
    <div class="final-cta">
        <h2>Stop searching. Start scheduling.</h2>
        <p>Join your college's smart timetable system today.</p>
        <div class="hero-actions" style="justify-content:center;">
            <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary">Get Started</a>
            <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline">Login</a>
        </div>
    </div>
</section>

<footer class="site-footer">
    <div class="container">
        <div class="logo-mark"><span class="logo-icon"><i class="fa-solid fa-calendar-days"></i></span>TimeSync</div>
        <p class="text-muted">&copy; <?= date('Y') ?> TimeSync · Smart Timetable Management & Personalization System</p>
    </div>
</footer>

</body>
</html>
