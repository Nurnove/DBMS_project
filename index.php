<?php
require_once 'db.php';
if (isLoggedIn()) { header('Location: dashboard.php'); exit; }
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>SoilSync — Smart Agricultural Platform</title>
  <link rel="stylesheet" href="style.css">
  <script>(function(){var t=localStorage.getItem('soilsync_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    /* ── LANDING SPECIFIC ── */
    body { cursor: none; }
 
    /* NAV */
    .land-nav {
      position: fixed; top: 0; left: 0; right: 0; z-index: 500;
      display: flex; align-items: center; justify-content: space-between;
      padding: 0 56px;
      height: 68px;
      background: rgba(var(--bg2-rgb, 255,255,255), 0.85);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      border-bottom: 1px solid var(--border);
      transition: all 0.3s;
    }
 
    .land-nav.scrolled { box-shadow: var(--shadow-md); }
 
    .nav-logo { display: flex; align-items: center; gap: 12px; }
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
      font-size: 20px; font-weight: 900;
      color: var(--text);
    }
 
    .nav-links { display: flex; align-items: center; gap: 6px; }
    .nav-links a {
      padding: 7px 14px;
      border-radius: 50px;
      font-size: 13.5px; font-weight: 500;
      color: var(--text2);
      transition: all var(--transition);
    }
    .nav-links a:hover { background: var(--surface2); color: var(--text); }
 
    .nav-actions { display: flex; align-items: center; gap: 10px; }
 
    /* HERO */
    .hero {
      min-height: 100vh;
      display: flex; align-items: center;
      padding: 100px 56px 80px;
      position: relative;
      overflow: hidden;
    }
 
    .hero-blob1 {
      position: absolute;
      top: -200px; right: -180px;
      width: 680px; height: 680px;
      background: radial-gradient(circle, var(--accent-light) 0%, transparent 65%);
      border-radius: 50%;
      pointer-events: none;
    }
 
    .hero-blob2 {
      position: absolute;
      bottom: -100px; left: -120px;
      width: 500px; height: 500px;
      background: radial-gradient(circle, var(--gold-light) 0%, transparent 65%);
      border-radius: 50%;
      pointer-events: none;
    }
 
    .hero-content { max-width: 640px; position: relative; z-index: 1; }
 
    .hero-eyebrow {
      display: inline-flex; align-items: center; gap: 8px;
      background: var(--accent-light);
      border: 1px solid var(--border);
      border-radius: 50px;
      padding: 6px 16px;
      font-size: 12px; font-weight: 700;
      color: var(--accent);
      font-family: var(--font-mono);
      letter-spacing: 1px;
      text-transform: uppercase;
      margin-bottom: 24px;
      animation: fadeUp 0.7s 0.1s both;
    }
 
    .hero-title {
      font-family: var(--font-display);
      font-size: clamp(42px, 5.5vw, 78px);
      font-weight: 900;
      line-height: 1.04;
      color: var(--text);
      margin-bottom: 22px;
      animation: fadeUp 0.7s 0.2s both;
    }
 
    .hero-title .hl { color: var(--accent); font-style: italic; }
 
    .hero-desc {
      font-size: 17px;
      color: var(--text2);
      line-height: 1.7;
      max-width: 500px;
      margin-bottom: 38px;
      animation: fadeUp 0.7s 0.3s both;
    }
 
    .hero-actions {
      display: flex; align-items: center; gap: 14px; flex-wrap: wrap;
      animation: fadeUp 0.7s 0.4s both;
    }
 
    .btn-hero-primary {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      color: #fff;
      padding: 15px 34px;
      border-radius: var(--radius-sm);
      font-family: var(--font-display);
      font-size: 15px; font-weight: 700;
      box-shadow: 0 6px 24px var(--accent-glow);
      transition: all var(--transition);
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-hero-primary:hover { transform: translateY(-2px); box-shadow: 0 10px 32px var(--accent-glow); color: #fff; }
 
    .btn-hero-outline {
      padding: 14px 28px;
      border-radius: var(--radius-sm);
      border: 1.5px solid var(--border2);
      font-family: var(--font-display);
      font-size: 15px; font-weight: 700;
      color: var(--text2);
      transition: all var(--transition);
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-hero-outline:hover { border-color: var(--accent); color: var(--accent); background: var(--accent-light); }
 
    /* Hero floating cards */
    .hero-right {
      position: absolute;
      right: 56px; top: 50%;
      transform: translateY(-50%);
      display: flex; flex-direction: column; gap: 16px;
      animation: fadeLeft 0.8s 0.5s both;
    }
 
    .float-card {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 18px 22px;
      box-shadow: var(--shadow-md);
      min-width: 200px;
      transition: transform 0.3s;
    }
 
    .float-card:hover { transform: translateX(-4px); }
 
    .float-card-label { font-size: 11px; color: var(--text3); font-family: var(--font-mono); text-transform: uppercase; letter-spacing: 1px; margin-bottom: 8px; }
    .float-card-val { font-family: var(--font-display); font-size: 26px; font-weight: 900; color: var(--text); line-height: 1; }
    .float-card-sub { font-size: 12px; color: var(--text3); margin-top: 4px; }
 
    /* Scroll indicator */
    .scroll-indicator {
      position: absolute;
      bottom: 36px; left: 56px;
      display: flex; align-items: center; gap: 12px;
      font-size: 11px; color: var(--text4);
      font-family: var(--font-mono); letter-spacing: 2px;
      animation: fadeUp 1s 0.8s both;
    }
 
    .scroll-line {
      width: 48px; height: 1px;
      background: var(--border);
      overflow: hidden;
      position: relative;
    }
 
    .scroll-line::after {
      content: '';
      position: absolute; left: -100%; top: 0;
      width: 100%; height: 100%;
      background: var(--accent);
      animation: scrollPulse 2s 1.5s infinite ease-in-out;
    }
 
    @keyframes scrollPulse { 0%{left:-100%} 100%{left:100%} }
 
    /* FEATURES STRIP */
    .feat-strip {
      background: var(--surface2);
      border-top: 1px solid var(--border);
      border-bottom: 1px solid var(--border);
      overflow: hidden;
    }
 
    .feat-strip-inner {
      display: flex;
      padding: 0;
      animation: stripScroll 28s linear infinite;
      width: max-content;
    }
 
    @keyframes stripScroll {
      from { transform: translateX(0); }
      to   { transform: translateX(-50%); }
    }
 
    .feat-strip-inner:hover { animation-play-state: paused; }
 
    .strip-pill {
      display: flex; align-items: center; gap: 8px;
      padding: 14px 32px;
      border-right: 1px solid var(--border);
      white-space: nowrap;
      font-size: 13px; font-weight: 600;
      color: var(--text2);
    }
 
    .strip-pill .si { font-size: 18px; }
 
    /* SECTION */
    .land-section { padding: 100px 56px; }
    .land-section-inner { max-width: 1100px; margin: 0 auto; }
 
    .section-eyebrow {
      font-size: 11px; font-weight: 700;
      letter-spacing: 3px; text-transform: uppercase;
      color: var(--accent);
      font-family: var(--font-mono);
      margin-bottom: 12px;
      display: block;
    }
 
    .section-heading {
      font-family: var(--font-display);
      font-size: clamp(28px, 3.5vw, 48px);
      font-weight: 900;
      color: var(--text);
      line-height: 1.1;
      margin-bottom: 12px;
    }
 
    .section-heading em { color: var(--accent); font-style: italic; }
 
    .section-body {
      font-size: 16px;
      color: var(--text2);
      max-width: 500px;
      line-height: 1.7;
      margin-bottom: 52px;
    }
 
    /* FEATURES GRID */
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
      gap: 18px;
    }
 
    .feat-card-land {
      background: var(--surface);
      border: 1px solid var(--border);
      border-radius: var(--radius);
      padding: 28px;
      position: relative;
      overflow: hidden;
      transition: all var(--transition);
    }
 
    .feat-card-land::after {
      content: '';
      position: absolute;
      left: 0; top: 0; bottom: 0;
      width: 3px;
      background: linear-gradient(to bottom, var(--accent), var(--accent2));
      transform: scaleY(0);
      transform-origin: bottom;
      transition: transform 0.35s cubic-bezier(0.4,0,0.2,1);
    }
 
    .feat-card-land:hover { transform: translateY(-5px); box-shadow: var(--shadow-lg); border-color: var(--accent); }
    .feat-card-land:hover::after { transform: scaleY(1); }
 
    .feat-num {
      font-size: 11px; font-family: var(--font-mono);
      color: var(--text4); letter-spacing: 1.5px;
      margin-bottom: 16px;
    }
 
    .feat-emoji { font-size: 36px; margin-bottom: 14px; display: block; line-height: 1; }
 
    .feat-title-land {
      font-family: var(--font-display);
      font-size: 18px; font-weight: 700;
      color: var(--text);
      margin-bottom: 8px;
    }
 
    .feat-desc-land { font-size: 13.5px; color: var(--text2); line-height: 1.65; }
 
    .feat-tag {
      margin-top: 16px;
      display: inline-block;
      font-size: 10px; font-family: var(--font-mono);
      letter-spacing: 1px; text-transform: uppercase;
      padding: 3px 10px; border-radius: 3px;
      background: var(--accent-light); color: var(--accent);
    }
 
    /* HOW IT WORKS */
    .how-grid {
      display: grid;
      grid-template-columns: repeat(4, 1fr);
      gap: 0;
      position: relative;
    }
 
    .how-grid::before {
      content: '';
      position: absolute;
      top: 28px; left: 8%; right: 8%;
      height: 1px;
      background: linear-gradient(90deg, transparent, var(--border), transparent);
    }
 
    .how-step { text-align: center; padding: 0 20px; }
 
    .how-num {
      width: 56px; height: 56px;
      background: var(--surface);
      border: 2px solid var(--border);
      border-radius: 50%;
      display: flex; align-items: center; justify-content: center;
      margin: 0 auto 20px;
      font-family: var(--font-display);
      font-size: 22px; font-weight: 900;
      color: var(--accent);
      position: relative; z-index: 1;
      transition: all var(--transition);
    }
 
    .how-step:hover .how-num {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      color: #fff;
      border-color: transparent;
      box-shadow: 0 6px 20px var(--accent-glow);
      transform: scale(1.1);
    }
 
    .how-title { font-family: var(--font-display); font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 8px; }
    .how-desc  { font-size: 13.5px; color: var(--text2); line-height: 1.6; }
 
    /* CTA */
    .cta-section {
      background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 60%, #7ad050 100%);
      padding: 100px 56px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }
 
    .cta-section::before {
      content: '';
      position: absolute; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
    }
 
    .cta-title {
      font-family: var(--font-display);
      font-size: clamp(32px, 4vw, 58px);
      font-weight: 900;
      color: #fff;
      margin-bottom: 16px;
      position: relative; z-index: 1;
    }
 
    .cta-desc {
      font-size: 17px;
      color: rgba(255,255,255,0.85);
      margin-bottom: 40px;
      position: relative; z-index: 1;
    }
 
    .cta-btns { display: flex; align-items: center; justify-content: center; gap: 14px; flex-wrap: wrap; position: relative; z-index: 1; }
 
    .btn-cta-white {
      background: #fff;
      color: var(--accent);
      padding: 16px 40px;
      border-radius: var(--radius-sm);
      font-family: var(--font-display);
      font-size: 15px; font-weight: 700;
      transition: all var(--transition);
      display: inline-flex; align-items: center; gap: 8px;
      box-shadow: 0 6px 24px rgba(0,0,0,0.12);
    }
    .btn-cta-white:hover { transform: translateY(-2px); box-shadow: 0 10px 32px rgba(0,0,0,0.18); color: var(--accent2); }
 
    .btn-cta-outline {
      padding: 15px 32px;
      border-radius: var(--radius-sm);
      border: 2px solid rgba(255,255,255,0.5);
      font-family: var(--font-display);
      font-size: 15px; font-weight: 700;
      color: rgba(255,255,255,0.9);
      transition: all var(--transition);
      display: inline-flex; align-items: center; gap: 8px;
    }
    .btn-cta-outline:hover { border-color: #fff; color: #fff; background: rgba(255,255,255,0.1); }
 
    /* FOOTER */
    .land-footer {
      background: var(--bg2);
      border-top: 1px solid var(--border);
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
 
    @keyframes fadeUp { from { opacity:0; transform:translateY(24px); } to { opacity:1; transform:translateY(0); } }
    @keyframes fadeLeft { from { opacity:0; transform:translateX(24px); } to { opacity:1; transform:translateX(0); } }
 
    @media (max-width: 1000px) {
      .hero-right { display: none; }
      .how-grid { grid-template-columns: repeat(2, 1fr); gap: 32px; }
      .how-grid::before { display: none; }
    }
 
    @media (max-width: 700px) {
      .land-nav { padding: 0 20px; }
      .nav-links { display: none; }
      .hero { padding: 90px 24px 60px; }
      .land-section { padding: 64px 24px; }
      .cta-section { padding: 64px 24px; }
      .land-footer { padding: 24px; }
      .scroll-indicator { display: none; }
    }
  </style>
</head>
<body>
 
<!-- Custom cursor -->
<div id="ss-cursor"></div>
<div id="ss-cursor-ring"></div>
 
<!-- ── NAV ── -->
<nav class="land-nav" id="landNav">
  <div class="nav-logo">
    <div class="nav-logo-badge">🌱</div>
    <div class="nav-logo-name">SoilSync</div>
  </div>
 
  <div class="nav-links">
    <a href="#features">Features</a>
    <a href="#how">How it works</a>
    <a href="#cta">Get started</a>
  </div>
 
  <div class="nav-actions">
    <button class="btn btn-ghost btn-sm theme-btn" id="themeToggle" onclick="toggleTheme()">
      <span>🌙</span> Dark Mode
    </button>
    <a href="auth.php" class="btn btn-outline btn-sm">Login</a>
    <a href="auth.php?tab=register" class="btn btn-primary btn-sm">Get Started →</a>
  </div>
</nav>
 
<!-- ── HERO ── -->
<section class="hero">
  <div class="hero-blob1"></div>
  <div class="hero-blob2"></div>
 
  <div class="hero-content">
    <div class="hero-eyebrow">🌾 Smart Agricultural Platform · Bangladesh</div>
 
    <h1 class="hero-title">
      Farm Smarter,<br>
      Earn <span class="hl">Better.</span>
    </h1>
 
    <p class="hero-desc">
      SoilSync gives farmers real-time pest alerts, disease solutions, smart irrigation advice, and live market prices — everything you need to grow more and earn more.
    </p>
 
    <div class="hero-actions">
      <a href="auth.php?tab=register" class="btn-hero-primary">
        Start Free Today <span>→</span>
      </a>
      <a href="#features" class="btn-hero-outline">
        <span>▾</span> Explore Features
      </a>
    </div>
  </div>
 
  <!-- Floating stat cards -->
  <div class="hero-right">
    <div class="float-card">
      <div class="float-card-label">🐛 Pest Alerts</div>
      <div class="float-card-val">Live</div>
      <div class="float-card-sub">Report & track outbreaks</div>
    </div>
    <div class="float-card">
      <div class="float-card-label">💧 Smart Irrigation</div>
      <div class="float-card-val">Auto</div>
      <div class="float-card-sub">Rain-based ON/OFF logic</div>
    </div>
    <div class="float-card">
      <div class="float-card-label">💰 Market Price</div>
      <div class="float-card-val">Daily</div>
      <div class="float-card-sub">Sell at the right time</div>
    </div>
  </div>
 
  <div class="scroll-indicator">
    <div class="scroll-line"></div>
    <span>Scroll to explore</span>
  </div>
</section>
 
<!-- ── FEATURES STRIP ── -->
<div class="feat-strip">
  <div class="feat-strip-inner" id="stripInner">
    <?php
    $strips = [
      ['🐛','Pest Surveillance'],['🦠','Disease Solutions'],['💧','Smart Irrigation'],
      ['🌰','Seed Finder'],['📅','Crop Tracking'],['💰','Market Prices'],
      ['📢','Daily Advisory'],['🌡️','Weather Data'],['🔔','Smart Alerts'],
      ['🗺️','Field Management'],['🌾','Harvest Planning'],['🏦','Loan Guide'],
    ];
    // Duplicate for seamless loop
    $all = array_merge($strips, $strips);
    foreach ($all as $s):
    ?>
    <div class="strip-pill">
      <span class="si"><?= $s[0] ?></span>
      <span><?= $s[1] ?></span>
    </div>
    <?php endforeach; ?>
  </div>
</div>
 
<!-- ── FEATURES ── -->
<section class="land-section" id="features">
  <div class="land-section-inner">
    <span class="section-eyebrow reveal">What we offer</span>
    <h2 class="section-heading reveal">Everything a modern<br><em>farmer needs.</em></h2>
    <p class="section-body reveal">From planting to harvest — SoilSync gives you data-driven tools to make smarter decisions every single day.</p>
 
    <div class="features-grid reveal-stagger">
      <?php
      $feats = [
        ['01','🗺️','Field Management','Track all your fields with soil type, area, and location. Organize your land for smarter planning.','Phase 1'],
        ['02','🌾','Crop Tracking','Log what you plant and when. Monitor growth status from planting all the way to harvest.','Phase 1'],
        ['03','🐛','Pest Surveillance','Report pest outbreaks instantly with location, crop type, and severity level in one tap.','Real-time'],
        ['04','🦠','Disease & Solutions','Select your crop, identify the disease, and get expert-recommended solutions and pesticides.','AI-Powered'],
        ['05','💧','Smart Irrigation','Weather-based irrigation decisions. Rain expected? Skip it. Dry forecast? Irrigate now.','Automated'],
        ['06','🌰','Seed Recommender','Find the best seed variety for your crop. Filter by yield potential and pest resistance.','Smart Filter'],
        ['07','📢','Daily Advisory','Weather alerts, pest warnings, and farming tips tailored for your region every day.','Live Feed'],
        ['08','💰','Market Prices','Check live crop prices from nearby markets. Know the best time to sell for maximum profit.','Live Data'],
      ];
      foreach ($feats as $f): ?>
      <div class="feat-card-land reveal-item">
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
 
<!-- ── HOW IT WORKS ── -->
<section class="land-section" id="how" style="background: var(--surface2); padding-top: 80px; padding-bottom: 80px;">
  <div class="land-section-inner">
    <span class="section-eyebrow reveal">Simple process</span>
    <h2 class="section-heading reveal">Up and running <em>in minutes.</em></h2>
 
    <div class="how-grid reveal-stagger" style="margin-top: 56px;">
      <?php
      $steps = [
        ['1','Register Free','Sign up with your phone number and select your district. No complex forms.'],
        ['2','Add Your Fields','Set up your farm fields with soil type and location for personalized recommendations.'],
        ['3','Plant & Track','Log your crops and get automatic activity schedules and growth tracking.'],
        ['4','Get Smart Advice','Receive pest alerts, disease solutions, irrigation tips, and market prices daily.'],
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
 
<!-- ── CTA ── -->
<section class="cta-section" id="cta">
  <h2 class="cta-title reveal">Ready to grow smarter? 🌾</h2>
  <p class="cta-desc reveal">Join SoilSync today. Free to register, powerful from day one.</p>
  <div class="cta-btns reveal">
    <a href="auth.php?tab=register" class="btn-cta-white">Create Free Account →</a>
    <a href="auth.php" class="btn-cta-outline">Already a member? Login</a>
  </div>
</section>
 
<!-- ── FOOTER ── -->
<footer class="land-footer">
  <div class="footer-logo">Soil<span>Sync</span></div>
  <div class="footer-copy">© 2026 SoilSync · Built for Bangladeshi Farmers 🇧🇩</div>
  <div class="footer-links">
    <a href="#features">Features</a>
    <a href="#how">How it works</a>
    <a href="auth.php">Login</a>
    <a href="auth.php?tab=register">Register</a>
  </div>
</footer>
 
<script src="theme.js"></script>
<script>
// Nav scroll effect
window.addEventListener('scroll', () => {
  const nav = document.getElementById('landNav');
  if (window.scrollY > 30) nav.classList.add('scrolled');
  else nav.classList.remove('scrolled');
});
 
// Smooth anchor scroll
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