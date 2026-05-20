<?php
require_once 'db.php';
if (isLoggedIn()) {

  if ($_SESSION['user_role'] === 'expert') {
      header('Location: expert_dashboard.php');
  } elseif ($_SESSION['user_role'] === 'admin') {
      header('Location: admin_dashboard.php');
  } else {
      header('Location: dashboard.php');
  }

  exit;
}

$tab     = $_GET['tab'] ?? 'login';
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action'])) {

  // ── LOGIN ──
  if ($_POST['action'] === 'login') {
    $phone = clean($conn, $_POST['phone'] ?? '');
    $pass  = $_POST['password'] ?? '';

    if (!$phone || !$pass) {
      $error = 'Please fill in all fields.';
    } else {
      $res = $conn->query("SELECT * FROM users WHERE phone='$phone' LIMIT 1");
      $u   = $res ? $res->fetch_assoc() : null;
      if ($u && password_verify($pass, $u['password'])) {

    $_SESSION['user_id']   = $u['id'];
    $_SESSION['user_name'] = $u['name'];
    $_SESSION['user_role'] = $u['role'];

    if ($u['role'] === 'expert') {
        header('Location: expert_dashboard.php');
    } elseif ($u['role'] === 'admin') {
        header('Location: admin_dashboard.php');
    } else {
        header('Location: dashboard.php');
    }

    exit;

} else {
    $error = 'Invalid phone number or password.';
}
    }
    $tab = 'login';
  }

  // ── REGISTER ──
  if ($_POST['action'] === 'register') {
    $name   = clean($conn, $_POST['name']     ?? '');
    $phone  = clean($conn, $_POST['phone']    ?? '');
    $pass   = $_POST['password']  ?? '';
    $pass2  = $_POST['password2'] ?? '';
    $loc_id = (int)($_POST['location_id'] ?? 0);

    if (!$name || !$phone || !$pass) {
      $error = 'Please fill in all required fields.';
    } elseif (strlen($pass) < 6) {
      $error = 'Password must be at least 6 characters.';
    } elseif ($pass !== $pass2) {
      $error = 'Passwords do not match.';
    } elseif ($loc_id === 0) {
      $error = 'Please select your district.';
    } else {
      $chk = $conn->query("SELECT id FROM users WHERE phone='$phone' LIMIT 1");
      if ($chk && $chk->num_rows > 0) {
        $error = 'This phone number is already registered.';
      } else {
        $hash = password_hash($pass, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("INSERT INTO users (name, phone, password, role, location_id) VALUES (?,?,?,'farmer',?)");
        $stmt->bind_param('sssi', $name, $phone, $hash, $loc_id);
        if ($stmt->execute()) {
          $success = 'Account created! You can now login.';
          $tab = 'login';
        } else {
          $error = 'Registration failed. Please try again.';
        }
      }
    }
    if ($error) $tab = 'register';
  }
}

$locs = $conn->query("SELECT * FROM locations ORDER BY division, district");
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login / Register — SoilSync</title>
  <link rel="stylesheet" href="style.css">
  <script>(function(){var t=localStorage.getItem('soilsync_theme')||'light';document.documentElement.setAttribute('data-theme',t);})();</script>
  <style>
    body { display:flex; min-height:100vh; align-items:stretch; }

    /* LEFT PANEL */
    .auth-left {
      flex: 1;
      background: linear-gradient(160deg, var(--accent) 0%, var(--accent2) 55%, #6abf48 100%);
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 60px 48px;
      text-align: center;
      position: relative;
      overflow: hidden;
    }

    .auth-left::before {
      content: '';
      position: absolute; inset: 0;
      background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.04'%3E%3Ccircle cx='40' cy='40' r='20'/%3E%3C/g%3E%3C/svg%3E");
    }

    .auth-left-content { position: relative; z-index: 1; }

    .auth-big-icon {
      width: 88px; height: 88px;
      background: rgba(255,255,255,0.2);
      border: 2px solid rgba(255,255,255,0.3);
      border-radius: 24px;
      display: flex; align-items: center; justify-content: center;
      font-size: 42px;
      margin: 0 auto 24px;
      backdrop-filter: blur(8px);
    }

    .auth-brand {
      font-family: var(--font-display);
      font-size: 2.6rem; font-weight: 900;
      color: #fff;
      margin-bottom: 12px;
      letter-spacing: -0.5px;
    }

    .auth-tagline {
      font-size: 15px;
      color: rgba(255,255,255,0.85);
      max-width: 340px;
      line-height: 1.7;
      margin-bottom: 40px;
    }

    .auth-perks { list-style: none; text-align: left; width: 100%; max-width: 320px; margin: 0 auto; }
    .auth-perks li {
      display: flex; align-items: center; gap: 12px;
      padding: 10px 0;
      border-bottom: 1px solid rgba(255,255,255,0.12);
      font-size: 14px;
      color: rgba(255,255,255,0.9);
    }
    .auth-perks li:last-child { border-bottom: none; }
    .perk-icon {
      width: 32px; height: 32px;
      background: rgba(255,255,255,0.18);
      border-radius: 8px;
      display: flex; align-items: center; justify-content: center;
      font-size: 16px; flex-shrink: 0;
    }

    /* RIGHT PANEL */
    .auth-right {
      width: 480px; min-width: 340px;
      display: flex; flex-direction: column;
      align-items: center; justify-content: center;
      padding: 48px 44px;
      background: var(--bg);
      overflow-y: auto;
    }

    .auth-logo-row {
      display: flex; align-items: center; gap: 10px;
      margin-bottom: 28px;
      text-decoration: none;
    }
    .auth-logo-badge {
      width: 38px; height: 38px;
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      border-radius: 10px;
      display: flex; align-items: center; justify-content: center;
      font-size: 20px;
    }
    .auth-logo-name {
      font-family: var(--font-display);
      font-size: 20px; font-weight: 900;
      color: var(--text);
    }

    /* Tabs */
    .auth-tabs {
      display: flex;
      width: 100%;
      background: var(--surface2);
      border: 1px solid var(--border);
      border-radius: var(--radius-sm);
      padding: 4px;
      gap: 4px;
      margin-bottom: 28px;
    }

    .auth-tab {
      flex: 1; text-align: center;
      padding: 10px;
      font-family: var(--font-display);
      font-size: 14px; font-weight: 700;
      border: none; background: transparent;
      color: var(--text3);
      border-radius: calc(var(--radius-sm) - 2px);
      transition: all var(--transition);
    }

    .auth-tab.active {
      background: linear-gradient(135deg, var(--accent), var(--accent2));
      color: #fff;
      box-shadow: 0 2px 8px var(--accent-glow);
    }

    .auth-form { width: 100%; }

    .auth-bottom {
      margin-top: 18px;
      text-align: center;
      font-size: 13px;
      color: var(--text3);
    }
    .auth-bottom a { color: var(--accent); font-weight: 600; }

    .pass-wrap { position: relative; }
    .pass-toggle {
      position: absolute; right: 12px; top: 50%;
      transform: translateY(-50%);
      background: none; border: none;
      font-size: 16px; color: var(--text3);
      padding: 4px;
      transition: color var(--transition);
    }
    .pass-toggle:hover { color: var(--accent); }

    .theme-row {
      margin-top: 20px; width: 100%;
      display: flex; justify-content: flex-end;
    }

    @media (max-width: 820px) {
      .auth-left { display: none; }
      .auth-right { width: 100%; padding: 32px 24px; }
    }
  </style>
</head>
<body>

<div id="ss-cursor"></div>
<div id="ss-cursor-ring"></div>

<!-- LEFT -->
<div class="auth-left">
  <div class="auth-left-content">
    <div class="auth-big-icon">🌱</div>
    <div class="auth-brand">SoilSync</div>
    <p class="auth-tagline">The smart agricultural platform built for Bangladeshi farmers — from soil to system.</p>

    <ul class="auth-perks">
      <li><div class="perk-icon">🐛</div> Pest surveillance & outbreak alerts</li>
      <li><div class="perk-icon">🦠</div> Disease identification & solutions</li>
      <li><div class="perk-icon">💧</div> Weather-based smart irrigation</li>
      <li><div class="perk-icon">🌱</div> High-yield seed recommendations</li>
      <li><div class="perk-icon">💰</div> Live market price tracker</li>
    </ul>
  </div>
</div>

<!-- RIGHT -->
<div class="auth-right">
  <a href="index.php" class="auth-logo-row">
    <div class="auth-logo-badge">🌱</div>
    <div class="auth-logo-name">SoilSync</div>
  </a>

  <?php if ($error): ?>
  <div class="alert alert-error" style="width:100%">⚠️ <?= htmlspecialchars($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
  <div class="alert alert-success" style="width:100%">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <div class="auth-tabs">
    <button class="auth-tab <?= $tab === 'login' ? 'active' : '' ?>" onclick="switchTab('login')">Login</button>
    <button class="auth-tab <?= $tab === 'register' ? 'active' : '' ?>" onclick="switchTab('register')">Register</button>
  </div>

  <!-- LOGIN -->
  <div id="loginForm" class="auth-form" <?= $tab !== 'login' ? 'style="display:none"' : '' ?>>
    <form method="post" autocomplete="on">
      <input type="hidden" name="action" value="login">

      <div class="form-group">
        <label>📱 Phone Number</label>
        <input type="tel" name="phone" placeholder="01XXXXXXXXX" autocomplete="tel" required>
      </div>

      <div class="form-group">
        <label>🔒 Password</label>
        <div class="pass-wrap">
          <input type="password" name="password" id="loginPass" placeholder="Your password" autocomplete="current-password" required>
          <button type="button" class="pass-toggle" onclick="togglePass('loginPass',this)">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:6px">
        Login to SoilSync →
      </button>
    </form>
    <div class="auth-bottom">Don't have an account? <a href="#" onclick="switchTab('register')">Register free</a></div>
  </div>

  <!-- REGISTER -->
  <div id="registerForm" class="auth-form" <?= $tab !== 'register' ? 'style="display:none"' : '' ?>>
    <form method="post" autocomplete="off">
      <input type="hidden" name="action" value="register">

      <div class="form-group">
        <label>👤 Full Name</label>
        <input type="text" name="name" placeholder="Your full name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>📱 Phone Number</label>
        <input type="tel" name="phone" placeholder="01XXXXXXXXX" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
      </div>

      <div class="form-group">
        <label>📍 Your District</label>
        <select name="location_id" required>
          <option value="">— Select your district —</option>
          <?php while ($loc = $locs->fetch_assoc()): ?>
          <option value="<?= $loc['id'] ?>" <?= (($_POST['location_id'] ?? '') == $loc['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($loc['division'] . ' — ' . $loc['district']) ?>
          </option>
          <?php endwhile; ?>
        </select>
      </div>

      <div class="form-group">
        <label>🔒 Password <span style="color:var(--text4);font-weight:400">(min 6 characters)</span></label>
        <div class="pass-wrap">
          <input type="password" name="password" id="regPass" placeholder="Create a password" required>
          <button type="button" class="pass-toggle" onclick="togglePass('regPass',this)">👁️</button>
        </div>
      </div>

      <div class="form-group">
        <label>🔒 Confirm Password</label>
        <div class="pass-wrap">
          <input type="password" name="password2" id="regPass2" placeholder="Repeat password" required>
          <button type="button" class="pass-toggle" onclick="togglePass('regPass2',this)">👁️</button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:6px">
        Create Free Account 🌱
      </button>
    </form>
    <div class="auth-bottom">Already have an account? <a href="#" onclick="switchTab('login')">Login</a></div>
  </div>

  <div class="theme-row">
    <button class="btn btn-ghost btn-sm theme-btn" id="themeToggle" onclick="toggleTheme()">
      <span>🌙</span> Dark Mode
    </button>
  </div>
</div>

<script src="theme.js"></script>
<script>
function switchTab(tab) {
  document.getElementById('loginForm').style.display    = tab === 'login'    ? '' : 'none';
  document.getElementById('registerForm').style.display = tab === 'register' ? '' : 'none';
  document.querySelectorAll('.auth-tab').forEach((t, i) => {
    t.classList.toggle('active', (i === 0 && tab === 'login') || (i === 1 && tab === 'register'));
  });
}

function togglePass(id, btn) {
  const f = document.getElementById(id);
  if (f.type === 'password') { f.type = 'text'; btn.textContent = '🙈'; }
  else                       { f.type = 'password'; btn.textContent = '👁️'; }
}
</script>
</body>
</html>
