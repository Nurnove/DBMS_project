<?php
require_once 'db.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }

/* ── LIVE STATS FROM DB ─────────────────────────────────── */
$totalFarmers  = $conn->query("SELECT COUNT(*) AS c FROM users WHERE role='farmer'")->fetch_assoc()['c'];
$totalFields   = $conn->query("SELECT COUNT(*) AS c FROM fields")->fetch_assoc()['c'];
$totalReports  = $conn->query("SELECT COUNT(*) AS c FROM pest_reports")->fetch_assoc()['c'];
$totalAdvisory = $conn->query("SELECT COUNT(*) AS c FROM advisory_feed")->fetch_assoc()['c'];
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SoilSync — Bangladesh's Smart Farming Platform</title>
  <link rel="stylesheet" href="style.css">
  <script>(function(){var t=localStorage.getItem('soilsync_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>

  <style>
    body { cursor: none; }

    /* ── NAV ─────────────────────────────────────────────── */
    .land-nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 500;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 56px; height: 68px;
      background: rgba(var(--bg2-rgb,255,255,255), 0.88);
      backdrop-filter: blur(18px);
      -webkit-backdrop-filter: blur(18px);
      border-bottom: 1px solid var(--border);
      transition: all 0.3s;
    }
    .land-nav.scrolled { box-shadow: var(--shadow-md); }
    .nav-logo { display: flex; align-items: center; gap: 12px; text-decoration: none; }
    .nav-logo-badge {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
      box-shadow: 0 4px 12px var(--accent-glow);
    }
    .nav-logo-name {
      font-family: var(--font-display);
      font-size: 20px; font-weight: 900; color: var(--text);
    }
    .nav-links { display: flex; align-items: center; gap: 4px; }
    .nav-links a {
      padding: 7px 14px; border-radius: 50px;
      font-size: 13.5px; font-weight: 500; color: var(--text2);
      transition: all var(--transition);
    }
    .nav-links a:hover { background: var(--surface2); color: var(--text); }
    .nav-actions { display: flex; align-items: center; gap: 10px; }

    /* ── HERO ────────────────────────────────────────────── */
    .hero {
      min-height: 100vh;
      display: flex; align-items: center;
      padding: 100px 56px 80px;
      position: relative; overflow: hidden;
    }
    .hero-blob1 {
      position: absolute; top: -200px; right: -180px;
      width: 680px; height: 680px;
      background: radial-gradient(circle, var(--accent-light) 0%, transparent 65%);
      border-radius: 50%; pointer-events: none;
    }
    .hero-blob2 {
      position: absolute; bottom: -100px; left: -120px;
      width: 500px; height: 500px;
      background: radial-gradient(circle, var(--gold-light) 0%, transparent 65%);
      border-radius: 50%; pointer-events: none;
    }
    .hero-content { max-width: 640px; position: relative; z-index: 1; }
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--accent-light);
      border: 1px solid var(--border); border-radius: 50px;
      padding: 6px 16px; font-size: 12px; font-weight: 700;
      color: var(--accent); font-family: var(--font-mono);
      letter-spacing: 1px; text-transform: uppercase;
      margin-bottom: 24px; animation: fadeUp 0.7s 0.1s both;
    }
    .hero-title {
      font-family: var(--font-display);
      font-size: clamp(38px, 5vw, 72px);
      font-weight: 900; line-height: 1.05;
      color: var(--text); margin-bottom: 22px;
      animation: fadeUp 0.7s 0.2s both;
    }
    .hero-title .hl { color: var(--accent); font-style: italic; }
    .hero-desc {
      font-size: 17px; color: var(--text2);
      line-height: 1.75; max-width: 520px;
      margin-bottom: 38px; animation: fadeUp 0.7s 0.3s both;
    }
    .hero-actions {
      display: flex; align-items: center; gap: 14px;
      flex-wrap: wrap; animation: fadeUp 0.7s 0.4s both;
    }
    .btn-hero-primary {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      color: #fff; padding: 15px 34px; border-radius: var(--radius-sm);
      font-family: var(--font-display); font-size: 15px; font-weight: 700;
      box-shadow: 0 6px 24px var(--accent-glow);
      transition: all var(--transition);
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 32px var(--accent-glow); color: #fff; }
    .btn-hero-outline {
      padding: 14px 28px; border-radius: var(--radius-sm);
      border: 1.5px solid var(--border2);
      font-family: var(--font-display); font-size: 15px; font-weight: 700;
      color: var(--text2); transition: all var(--transition);
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-hero-outline:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }

    /* Hero floating stat cards */
    .hero-right {
      position: absolute; right: 56px; top: 50%;
      transform: translateY(-50%);
      display: flex; flex-direction: column; gap: 14px;
      animation: fadeLeft 0.8s 0.5s both;
    }
    .float-card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 16px 20px;
      box-shadow: var(--shadow-md); min-width: 210px;
      transition: transform 0.3s;
    }
    .float-card:hover { transform: translateX(-4px); }
    .float-card-label { font-size: 11px; color: var(--text3); font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 6px; }
    .float-card-val { font-family: var(--font-display); font-size: 24px; font-weight: 900; color: var(--text); line-height: 1; }
    .float-card-sub { font-size: 12px; color: var(--text3); margin-top: 4px; }
    .float-card-live {
      display: inline-flex; align-items: center; gap: 5px;
      font-size: 10px; font-family: var(--font-mono);
      color: var(--success); margin-top: 6px; font-weight: 700;
    }
    .live-dot {
      width: 6px; height: 6px; border-radius: 50%;
      background: var(--success);
      animation: pulse 1.6s infinite;
    }
    @keyframes pulse { 0%,100%{opacity:1} 50%{opacity:0.3} }

    /* Scroll indicator */
    .scroll-indicator {
      position: absolute; bottom: 36px; left: 56px;
      display: flex; align-items: center; gap: 12px;
      font-size: 11px; color: var(--text4);
      font-family: var(--font-mono); letter-spacing: 2px;
      animation: fadeUp 1s 0.8s both;
    }
    .scroll-line {
      width: 48px; height: 1px; background: var(--border);
      overflow: hidden; position: relative;
    }
    .scroll-line::after {
      content: ''; position: absolute; left: -100%; top: 0;
      width: 100%; height: 100%; background: var(--accent);
      animation: scrollPulse 2s 1.5s infinite ease-in-out;
    }
    @keyframes scrollPulse { 0%{left:-100%} 100%{left:100%} }

    /* ── MARQUEE STRIP ────────────────────────────────────── */
    .feat-strip {
      background: var(--surface2);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      overflow: hidden;
    }
    .feat-strip-inner {
      display: flex; padding: 0;
      animation: stripScroll 32s linear infinite;
      width: max-content;
    }
    @keyframes stripScroll { from{transform:translateX(0)} to{transform:translateX(-50%)} }
    .feat-strip-inner:hover { animation-play-state: paused; }
    .strip-pill {
      display: flex; align-items: center; gap: 8px;
      padding: 14px 32px; border-right: 1px solid var(--border);
      white-space: nowrap; font-size: 13px; font-weight: 600; color: var(--text2);
    }
    .strip-pill .si { font-size: 18px; }

    /* ── SECTIONS ─────────────────────────────────────────── */
    .land-section { padding: 100px 56px; }
    .land-section-inner { max-width: 1100px; margin: 0 auto; }
    .section-eyebrow {
      font-size: 11px; font-weight: 700; letter-spacing: 3px;
      text-transform: uppercase; color: var(--accent);
      font-family: var(--font-mono); margin-bottom: 12px; display: block;
    }
    .section-heading {
      font-family: var(--font-display);
      font-size: clamp(28px, 3.5vw, 48px); font-weight: 900;
      color: var(--text); line-height: 1.1; margin-bottom: 12px;
    }
    .section-heading em { color: var(--accent); font-style: italic; }
    .section-body {
      font-size: 16px; color: var(--text2); max-width: 520px;
      line-height: 1.75; margin-bottom: 52px;
    }

    /* ── LIVE STATS ROW ───────────────────────────────────── */
    .stats-band {
      background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 100%);
      padding: 48px 56px;
    }
    .stats-band-inner {
      max-width: 1100px; margin: 0 auto;
      display: grid; grid-template-columns: repeat(4,1fr);
      gap: 0;
    }
    .stat-band-item {
      text-align: center; padding: 0 24px;
      border-right: 1px solid rgba(255,255,255,0.2);
    }
    .stat-band-item:last-child { border-right: none; }
    .stat-band-val {
      font-family: var(--font-display); font-size: 40px; font-weight: 900;
      color: #fff; line-height: 1;
    }
    .stat-band-label { font-size: 13px; color: rgba(255,255,255,0.8); margin-top: 6px; }

    /* ── FEATURES GRID ────────────────────────────────────── */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
      gap: 18px;
    }
    .feat-card-land {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 28px;
      position: relative; overflow: hidden;
      transition: all var(--transition);
    }
    .feat-card-land::after {
      content: ''; position: absolute; left: 0; top: 0; bottom: 0; width: 3px;
      background: linear-gradient(to bottom, var(--accent), var(--accent2));
      transform: scaleY(0); transform-origin: bottom;
      transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
    }
    .feat-card-land:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--accent); }
    .feat-card-land:hover::after { transform: scaleY(1); }
    .feat-card-land.new-feat { border-color: var(--gold); }
    .feat-card-land.new-feat::before {
      content: '✨ New'; position: absolute; top: 14px; right: 14px;
      font-size: 10px; font-family: var(--font-mono); font-weight: 700;
      background: var(--gold-light); color: var(--gold);
      padding: 3px 8px; border-radius: 20px; letter-spacing: 0.5px;
    }
    .feat-num { font-size: 11px; font-family: var(--font-mono); color: var(--text4); letter-spacing: 1.5px; margin-bottom: 16px; }
    .feat-emoji { font-size: 36px; margin-bottom: 14px; display: block; line-height: 1; }
    .feat-title-land { font-family: var(--font-display); font-size: 18px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .feat-desc-land { font-size: 13.5px; color: var(--text2); line-height: 1.65; }
    .feat-tag {
      margin-top: 16px; display: inline-block;
      font-size: 10px; font-family: var(--font-mono);
      letter-spacing: 1px; text-transform: uppercase;
      padding: 3px 10px; border-radius: 3px;
      background: var(--accent-light); color: var(--accent);
    }

    /* ── FARMERS CARD HIGHLIGHT ───────────────────────────── */
    .fc-banner {
      background: linear-gradient(135deg, #1a6090 0%, #1a7a38 100%);
      border-radius: var(--radius); padding: 48px 52px;
      display: flex; align-items: center; gap: 48px;
      flex-wrap: wrap; position: relative; overflow: hidden;
      margin-bottom: 0;
    }
    .fc-banner::before {
      content: '🪪'; position: absolute;
      right: -10px; top: -30px; font-size: 200px; opacity: 0.07;
    }
    .fc-banner-text { flex: 1; min-width: 260px; }
    .fc-banner-eyebrow {
      font-size: 11px; font-family: var(--font-mono); letter-spacing: 2px;
      text-transform: uppercase; color: rgba(255,255,255,0.7); margin-bottom: 10px;
    }
    .fc-banner-title {
      font-family: var(--font-display); font-size: 32px; font-weight: 900;
      color: #fff; line-height: 1.15; margin-bottom: 14px;
    }
    .fc-banner-desc { font-size: 15px; color: rgba(255,255,255,0.85); line-height: 1.7; }
    .fc-benefits {
      display: flex; flex-direction: column; gap: 10px;
      flex: 1; min-width: 260px;
    }
    .fc-benefit {
      display: flex; align-items: flex-start; gap: 12px;
      background: rgba(255,255,255,0.12); border: 1px solid rgba(255,255,255,0.2);
      border-radius: var(--radius-sm); padding: 12px 16px;
    }
    .fc-benefit-icon { font-size: 20px; flex-shrink: 0; }
    .fc-benefit-text { font-size: 13px; color: rgba(255,255,255,0.9); line-height: 1.5; }
    .fc-benefit-title { font-weight: 700; color: #fff; }

    /* ── HOW IT WORKS ─────────────────────────────────────── */
    .how-grid {
      display: grid; grid-template-columns: repeat(4,1fr);
      gap: 0; position: relative;
    }
    .how-grid::before {
      content: ''; position: absolute; top: 28px; left: 8%; right: 8%;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--border), transparent);
    }
    .how-step { text-align: center; padding: 0 20px; }
    .how-num {
      width: 56px; height: 56px; background: var(--surface);
      border: 2px solid var(--border); border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      font-family: var(--font-display); font-size: 22px; font-weight: 900;
      color: var(--accent); position: relative; z-index: 1;
      transition: all var(--transition);
    }
    .how-step:hover .how-num {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      color: #fff; border-color: transparent;
      box-shadow: 0 6px 20px var(--accent-glow); transform: scale(1.1);
    }
    .how-title { font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .how-desc  { font-size: 13.5px; color: var(--text2); line-height: 1.65; }

    /* ── TESTIMONIAL / FARMER QUOTE ───────────────────────── */
    .quotes-grid {
      display: grid; grid-template-columns: repeat(auto-fit, minmax(280px,1fr));
      gap: 18px; margin-top: 48px;
    }
    .quote-card {
      background: var(--surface); border: 1px solid var(--border);
      border-radius: var(--radius); padding: 28px;
      transition: all var(--transition);
    }
    .quote-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-md); }
    .quote-stars { font-size: 16px; margin-bottom: 14px; letter-spacing: 2px; }
    .quote-text { font-size: 14px; color: var(--text2); line-height: 1.75; margin-bottom: 18px; font-style: italic; }
    .quote-author { display: flex; align-items: center; gap: 10px; }
    .quote-av {
      width: 38px; height: 38px; border-radius: 50%;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; flex-shrink: 0;
    }
    .quote-name { font-family: var(--font-display); font-size: 13px; font-weight: 700; color: var(--text); }
    .quote-loc  { font-size: 11px; color: var(--text3); font-family: var(--font-mono); }

    /* ── CTA ──────────────────────────────────────────────── */
    .cta-section {
      background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 60%, #7ad050 100%);
      padding: 100px 56px; text-align: center;
      position: relative; overflow: hidden;
    }
    .cta-section::before {
      content: ''; position: absolute; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
    .cta-title { font-family: var(--font-display); font-size: clamp(30px,4vw,56px); font-weight: 900; color: #fff; margin-bottom: 16px; position: relative; z-index: 1; }
    .cta-desc  { font-size: 17px; color: rgba(255,255,255,0.85); margin-bottom: 40px; position: relative; z-index: 1; }
    .cta-btns  { display: flex; align-items: center; justify-content: center; gap: 14px; flex-wrap: wrap; position: relative; z-index: 1; }
    .btn-cta-white {
      background: #fff; color: var(--accent); padding: 16px 40px;
      border-radius: var(--radius-sm); font-family: var(--font-display);
      font-size: 15px; font-weight: 700; transition: all var(--transition);
      display: inline-flex; align-items: center; gap: 8px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.12);
    }
    .btn-cta-white:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(0,0,0,0.18); color: var(--accent2); }
    .btn-cta-outline {
      padding: 15px 32px; border-radius: var(--radius-sm);
      border: 2px solid rgba(255,255,255,0.5);
      font-family: var(--font-display); font-size: 15px; font-weight: 700;
      color: rgba(255,255,255,0.9); transition: all var(--transition);
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-cta-outline:hover { border-color: #fff; color: #fff; background: rgba(255,255,255,0.1); }

    /* ── FOOTER ───────────────────────────────────────────── */
    .land-footer {
      background: var(--bg2); border-top: 1px solid var(--border);
      padding: 32px 56px;
      display: flex; align-items: center; justify-content: space-between;
      flex-wrap: wrap; gap: 16px;
    }
    .footer-logo { font-family: var(--font-display); font-size: 18px; font-weight: 900; color: var(--text); }
    .footer-logo span { color: var(--accent); }
    .footer-copy { font-size: 12.5px; color: var(--text3); font-family: var(--font-mono); }
    .footer-links { display: flex; gap: 20px; }
    .footer-links a { font-size: 13px; color: var(--text3); transition: color var(--transition); }
    .footer-links a:hover { color: var(--accent); }

    /* ── ANIMATIONS ───────────────────────────────────────── */
    @keyframes fadeUp  { from{opacity:0;transform:translateY(24px)} to{opacity:1;transform:translateY(0)} }
    @keyframes fadeLeft{ from{opacity:0;transform:translateX(24px)} to{opacity:1;transform:translateX(0)} }

    /* ── RESPONSIVE ───────────────────────────────────────── */
    @media (max-width: 1000px) {
      .hero-right { display: none; }
      .how-grid { grid-template-columns: repeat(2,1fr); gap: 32px; }
      .how-grid::before { display: none; }
      .stats-band-inner { grid-template-columns: repeat(2,1fr); gap: 24px; }
      .stat-band-item { border-right: none; border-bottom: 1px solid rgba(255,255,255,0.2); padding-bottom: 20px; }
    }
    @media (max-width: 700px) {
      .land-nav { padding: 0 20px; }
      .nav-links { display: none; }
      .hero { padding: 90px 24px 60px; }
      .land-section { padding: 64px 24px; }
      .stats-band { padding: 40px 24px; }
      .cta-section { padding: 64px 24px; }
      .land-footer { padding: 24px; }
      .scroll-indicator { display: none; }
      .fc-banner { padding: 32px 24px; gap: 28px; }
    }
  </style>
</head>
<body>

<div id="ss-cursor"></div>
<div id="ss-cursor-ring"></div>

<nav class="land-nav" id="landNav">
  <a href="index.php" class="nav-logo">
    <div class="nav-logo-badge">🌱</div>
    <div class="nav-logo-name">SoilSync</div>
  </a>

  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#farmers-card">Farmers Card</a>
    <a href="#how">How It Works</a>
    <a href="#cta">Get Started</a>
  </div>

  <div class="nav-actions">
    <button class="btn btn-ghost btn-sm theme-btn" id="themeToggle" onclick="toggleTheme()">
      <span>🌙</span> Dark Mode
    </button>
    <a href="auth.php" class="btn btn-outline btn-sm">Login</a>
    <a href="auth.php?tab=register" class="btn btn-primary btn-sm">SignUp</a>
  </div>
</nav>


<section class="hero">
  <div class="hero-blob1"></div>
  <div class="hero-blob2"></div>

  <div class="hero-content">
    <div class="hero-eyebrow">🌾 Bangladesh's Smart Farming Platform · 2026</div>

    <h1 class="hero-title">
      Farm<br>
      Smartly,<br>
      Earn <span class="hl">More.</span>
    </h1>

    <p class="hero-desc">
      SoilSync is built for the farmers of Bangladesh — real-time pest alerts, disease solutions, smart irrigation advice, market prices, and government Farmers Card benefits all in one place.
    </p>

    <div class="hero-actions">
      <a href="auth.php?tab=register" class="btn-hero-primary">
        Start Now — It's Free <span>→</span>
      </a>
      <a href="#features" class="btn-hero-outline">
        <span>▾</span> Explore Features
      </a>
    </div>
  </div>

  <div class="hero-right">
    <div class="float-card">
      <div class="float-card-label">👨‍🌾 Registered Farmers</div>
      <div class="float-card-val"><?= number_format($totalFarmers) ?>+</div>
      <div class="float-card-sub">Active Users</div>
      <div class="float-card-live"><div class="live-dot"></div> Live Data</div>
    </div>
    <div class="float-card">
      <div class="float-card-label">🐛 Pest Reports</div>
      <div class="float-card-val"><?= number_format($totalReports) ?>+</div>
      <div class="float-card-sub">From all over Bangladesh</div>
      <div class="float-card-live"><div class="live-dot"></div> Real-time</div>
    </div>
    <div class="float-card">
      <div class="float-card-label">🌦️ Weather Updates</div>
      <div class="float-card-val">64</div>
      <div class="float-card-sub">Districts Data Coverage</div>
      <div class="float-card-live"><div class="live-dot"></div> Daily Updates</div>
    </div>
  </div>

  <div class="scroll-indicator">
    <div class="scroll-line"></div>
    <span>Scroll Down</span>
  </div>
</section>


<div class="feat-strip">
  <div class="feat-strip-inner">
    <?php
    $strips = [
      ['🐛','Pest Monitoring'],['🦠','Disease Solutions'],['💧','Smart Irrigation'],
      ['🌱','Seed Finder'],['📅','Crop Tracking'],['💰','Market Prices'],
      ['📢','Daily Advisory'],['🌡️','Weather Info'],['🔔','Smart Alerts'],
      ['🗺️','Land Management'],['🔄','Crop Rotation'],['🏦','Loan Info'],
      ['🪪','Farmers Card'],['🌍','Outbreak Map'],['❓','Expert Q&A'],
    ];
    $all = array_merge($strips, $strips);
    foreach ($all as $s): ?>
    <div class="strip-pill">
      <span class="si"><?= $s[0] ?></span>
      <span><?= $s[1] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>


<div class="stats-band">
  <div class="stats-band-inner">
    <div class="stat-band-item">
      <div class="stat-band-val"><?= number_format($totalFarmers) ?>+</div>
      <div class="stat-band-label">👨‍🌾 Registered Farmers</div>
    </div>
    <div class="stat-band-item">
      <div class="stat-band-val"><?= number_format($totalFields) ?>+</div>
      <div class="stat-band-label">🗺️ Registered Fields</div>
    </div>
    <div class="stat-band-item">
      <div class="stat-band-val"><?= number_format($totalReports) ?>+</div>
      <div class="stat-band-label">🐛 Pest Reports</div>
    </div>
    <div class="stat-band-item">
      <div class="stat-band-val">64</div>
      <div class="stat-band-label">📍 District Coverage</div>
    </div>
  </div>
</div>


<section class="land-section" id="features">
  <div class="land-section-inner">
    <span class="section-eyebrow reveal">Our Services</span>
    <h2 class="section-heading reveal">
      Modern Farmer's<br>
      <em>Everything in One Place.</em>
    </h2>
    <p class="section-body reveal">
      From sowing seeds to harvesting — SoilSync provides you with data-driven advice for every decision.
    </p>

    <div class="features-grid reveal-stagger">
      <?php
      $feats = [
        ['01','🗺️','Land Management','Track all your fields including soil type, area, and location. Organize land for smart planning.','Phase 1',false],
        ['02','🌾','Crop Tracking','Record what you planted and when. Monitor growth from sowing to harvesting.','Phase 1',false],
        ['03','🐛','Pest Monitoring','Instantly report pest outbreaks — including location, crop type, and severity.','Real-time',false],
        ['04','🦠','Disease & Solutions','Select crop to identify diseases and get expert-recommended solutions and pesticides.','AI-Powered',false],
        ['05','💧','Smart Irrigation','Weather-based irrigation advice. Rain in the forecast? Skip it. Dry forecast? Irrigate now.','Automated',false],
        ['06','🌱','Seed Finder','Find the best seed varieties for your crops. Filter by yield potential and pest resistance.','Smart Filter',false],
        ['07','🔄','Crop Rotation Advisor','Next crop recommendations based on previous crop and soil type. Protect soil health.','Data-driven',true],
        ['08','🗺️','Outbreak Map','View live map of pest attacks in your district. Be forewarned.','Live Map',false],
        ['09','💰','Market Prices','Check live crop prices at nearby markets. Sell at the right time for maximum profit.','Live Data',false],
        ['10','📢','Daily Advisory','Weather alerts, pest warnings, and daily agricultural advice for your region.','Live Feed',false],
        ['11','🏦','Loan Info Hub','Compare agricultural loans, get personalized recommendations, and find nearby bank offices.','Smart Compare',true],
        ['12','❓','Expert Q&A','Ask an expert directly about any agricultural problem and get quick answers.','Live Support',false],
      ];
      foreach ($feats as $f): ?>
      <div class="feat-card-land reveal-item <?= $f[5] ? 'new-feat' : '' ?>">
        <div class="feat-num"><?= $f[0] ?></div>
        <span class="feat-emoji"><?= $f[1] ?></span>
        <h3 class="feat-title-land"><?= $f[2] ?></h3>
        <p class="feat-desc-land"><?= $f[3] ?></p>
        <span class="feat-tag"><?= $f[4] ?></span>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="land-section" id="farmers-card"
         style="background:var(--surface2);padding-top:80px;padding-bottom:80px">
  <div class="land-section-inner">
    <span class="section-eyebrow reveal">Government Benefits</span>
    <h2 class="section-heading reveal">
      Farmers Card's<br>
      <em>10 Benefits in One Place.</em>
    </h2>
    <p class="section-body reveal">
      The Government of Bangladesh launched the Farmers Card on April 14, 2026. SoilSync helps you understand and use all the benefits of the card.
    </p>

    <div class="fc-banner reveal">
      <div class="fc-banner-text">
        <div class="fc-banner-eyebrow">🇧🇩 Govt Initiative · Visa + Sonali Bank</div>
        <div class="fc-banner-title">🪪 Farmers Card Hub</div>
        <div class="fc-banner-desc">
          Enter your card number — SoilSync will show which benefits you are getting, which you haven't received yet, and the total value of benefits per year. Free for 27.5 million farmers.
        </div>
        <a href="auth.php?tab=register"
           style="display:inline-flex;align-items:center;gap:8px;
                  background:#fff;color:var(--accent);padding:13px 28px;
                  border-radius:var(--radius-sm);font-family:var(--font-display);
                  font-size:14px;font-weight:700;margin-top:24px;
                  transition:all var(--transition);text-decoration:none;"
           onmouseover="this.style.transform='translateY(-2px)'"
           onmouseout="this.style.transform=''">
          🪪 Link Card →
        </a>
      </div>

      <div class="fc-benefits">
        <?php
        $fcBenefits = [
          ['🌾', 'Subsidized Agricultural Inputs', 'Fertilizer and seeds cheaper than market price'],
          ['🏦', 'Easy Agricultural Loans', 'Only 6% interest at Sonali Bank'],
          ['💸', 'Direct Cash Subsidy', '2,500 BDT directly to bank every year'],
          ['🛡️', 'Crop Insurance Benefits', 'Protection against flood, drought, and pest damage'],
        ];
        foreach ($fcBenefits as $b): ?>
        <div class="fc-benefit">
          <div class="fc-benefit-icon"><?= $b[0] ?></div>
          <div class="fc-benefit-text">
            <div class="fc-benefit-title"><?= $b[1] ?></div>
            <div><?= $b[2] ?></div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </div>
</section>


<section class="land-section" id="how">
  <div class="land-section-inner">
    <span class="section-eyebrow reveal">Simple Process</span>
    <h2 class="section-heading reveal">
      Get Started in<br>
      <em>Just a Few Minutes.</em>
    </h2>

    <div class="how-grid reveal-stagger" style="margin-top:56px">
      <?php
      $steps = [
        ['1','Register for Free','Sign up with your phone number and choose your district. No complex forms.'],
        ['2','Add Your Fields','Set up your fields with soil type and location.'],
        ['3','Plant & Track Crops','Record crops and get an automated activity schedule.'],
        ['4','Get Smart Advisory','Receive daily pest alerts, disease solutions, irrigation advice, and market prices.'],
      ];
      foreach ($steps as $s): ?>
      <div class="how-step reveal-item">
        <div class="how-num"><?= $s[0] ?></div>
        <h3 class="how-title"><?= $s[1] ?></h3>
        <p class="how-desc"><?= $s[2] ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="land-section" style="background:var(--surface2);padding-top:80px;padding-bottom:80px">
  <div class="land-section-inner">
    <span class="section-eyebrow reveal">What Farmers Say</span>
    <h2 class="section-heading reveal">
      Their Stories.
    </h2>

    <div class="quotes-grid">
      <?php
      $quotes = [
        ['With SoilSync I knew in advance that brown plant hoppers were attacking my district. I took timely action and saved my crop.','👨‍🌾','Rahim Mia','Sylhet District'],
        ['From the Farmers Card Hub I learned I am eligible for over 8,000 BDT in government benefits annually. I didn\'t even know before.','👩‍🌾','Sufia Begum','Rajshahi District'],
        ['The Crop Rotation Advisor suggested planting mustard after paddy. The soil fertility has been much better this year.','👨‍🌾','Karim Sheikh','Barishal District'],
      ];
      foreach ($quotes as $q): ?>
      <div class="quote-card reveal-item">
        <div class="quote-stars">⭐⭐⭐⭐⭐</div>
        <p class="quote-text">"<?= $q[0] ?>"</p>
        <div class="quote-author">
          <div class="quote-av"><?= $q[1] ?></div>
          <div>
            <div class="quote-name"><?= $q[2] ?></div>
            <div class="quote-loc">📍 <?= $q[3] ?></div>
          </div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>


<section class="cta-section" id="cta">
  <h2 class="cta-title reveal">Start Smart Farming Today 🌾</h2>
  <p class="cta-desc reveal">
    Join SoilSync — registration is free, powerful from day one.<br>
    We stand by the 27.5 million farmers of Bangladesh.
  </p>
  <div class="cta-btns reveal">
    <a href="auth.php?tab=register" class="btn-cta-white">Create a Free Account →</a>
    <a href="auth.php" class="btn-cta-outline">Already a member? Login</a>
  </div>
</section>


<footer class="land-footer">
  <div>
    <div class="footer-logo">Soil<span>Sync</span></div>
    <div class="footer-copy" style="margin-top:6px">
      © 2026 SoilSync · Built for the farmers of Bangladesh 🇧🇩
    </div>
  </div>

  <div class="footer-links">
    <a href="#features">Features</a>
    <a href="#farmers-card">Farmers Card</a>
    <a href="#how">How It Works</a>
    <a href="auth.php">Login</a>
    <a href="auth.php?tab=register">Register</a>
  </div>
</footer>

<script src="theme.js"></script>
<script>
/* Nav scroll effect */
window.addEventListener('scroll', () => {
  const nav = document.getElementById('landNav');
  if (window.scrollY > 30) nav.classList.add('scrolled');
  else nav.classList.remove('scrolled');
});

/* Smooth anchor scroll */
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const id = a.getAttribute('href').slice(1);
    const el = document.getElementById(id);
    if (el) { e.preventDefault(); el.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
  });
});
</script>
</body>
</html>