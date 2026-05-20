<?php
// layout.php — shared wrapper for all authenticated pages

$user        = currentUser($conn);
$uid         = $user ? (int)$user['id'] : 0;
$userName    = $user['name'] ?? 'User';
$userInitial = strtoupper(substr($userName, 0, 1));
$userRole    = $user['role'] ?? 'farmer';
$notifCount  = $uid ? unreadCount($conn, $uid) : 0;

/* =========================
   ROLE BASED NAV SYSTEM
========================= */
$nav = [];

switch ($userRole) {

  /* ================= FARMER ================= */
 case 'farmer':
$nav = [

  'main' => [
    ['href'=>'dashboard.php','icon'=>'🏠','label'=>'Dashboard','key'=>'dashboard'],
    ['href'=>'fields.php','icon'=>'🗺️','label'=>'My Fields','key'=>'fields'],
    ['href'=>'crops.php','icon'=>'🌾','label'=>'My Crops','key'=>'crops'],
  ],

  'monitor' => [
    ['href'=>'pest_report.php','icon'=>'🐛','label'=>'Pest Reports','key'=>'pest'],
    ['href'=>'outbreak_map.php','icon'=>'🌍','label'=>'Outbreak Map','key'=>'outbreak'],
    ['href'=>'disease.php','icon'=>'🦠','label'=>'Disease & Solutions','key'=>'disease'],
    ['href'=>'irrigation.php','icon'=>'💧','label'=>'Irrigation','key'=>'irrigation'],
  ],

  'resources' => [
    ['href'=>'crop_recommend.php','icon'=>'🌱','label'=>'Crop Recommendation','key'=>'crop_recommend'],
    ['href'=>'seeds.php','icon'=>'🌱','label'=>'Seed Finder','key'=>'seeds'],
    ['href'=>'market.php','icon'=>'💰','label'=>'Market Prices','key'=>'market'],
    ['href'=>'notifications.php','icon'=>'🔔','label'=>'Notifications','key'=>'notifications','badge'=>$notifCount],
  ],

  /* ✅ NEW SECTION */
  'support' => [
    ['href'=>'faq.php','icon'=>'❓','label'=>'Ask Expert','key'=>'faq'],
    ['href'=>'my_questions.php','icon'=>'📝','label'=>'My Questions','key'=>'myq'],
    ['href'=>'compliance_checker.php','icon'=>'⚖️','label'=>'Compliance Checker','key'=>'compliance'],
  ],

];
break;

  /* ================= EXPERT ================= */
  case 'expert':
    $nav = [
      'expert' => [
        ['href'=>'expert_dashboard.php','icon'=>'📊','label'=>'Dashboard','key'=>'dashboard'],
        ['href'=>'advisory_manage.php','icon'=>'📢','label'=>'Advisory Management','key'=>'advisory'],
        ['href'=>'faq_manage.php','icon'=>'💬','label'=>'Answer Questions','key'=>'qa'],
        ['href'=>'pest_review.php','icon'=>'🐛','label'=>'Pest Review','key'=>'pest'],
        ['href'=>'market_manage.php','icon'=>'💰','label'=>'Market Price Input','key'=>'market'],
      ],
    ];
    break;

  /* ================= ADMIN ================= */
  case 'admin':
    $nav = [
      'admin' => [
        ['href'=>'admin.php','icon'=>'⚙️','label'=>'Admin Panel','key'=>'admin'],
        ['href'=>'users.php','icon'=>'👥','label'=>'User Management','key'=>'users'],
        ['href'=>'system.php','icon'=>'🧠','label'=>'System Settings','key'=>'system'],
      ],
    ];
    break;
}

?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pageTitle ?? 'SoilSync') ?> — SoilSync</title>

  <link rel="stylesheet" href="style.css">

  <script>
    (function(){
      var t = localStorage.getItem('soilsync_theme') || 'light';
      document.documentElement.setAttribute('data-theme', t);
    })();
  </script>
</head>

<body>

<div id="ss-cursor"></div>
<div id="ss-cursor-ring"></div>
<div id="sidebar-overlay"></div>

<div class="app-layout">

  <!-- ================= SIDEBAR ================= -->
  <aside class="sidebar" id="sidebar">

    <div class="sidebar-logo">
      <div class="logo-badge">🌱</div>
      <div class="logo-text-wrap">
        <div class="logo-name">SoilSync</div>
        <div class="logo-tag">Smart Farming</div>
      </div>
    </div>

    <nav class="sidebar-nav">

      <?php
      $sectionLabels = [
        'main'=>'Navigation',
        'monitor'=>'Monitor',
        'resources'=>'Resources',
        'expert'=>'Expert Tools',
        'admin'=>'Administration'
      ];

      foreach ($nav as $section => $items):
      ?>
        <div class="nav-section-label">
          <?= $sectionLabels[$section] ?? ucfirst($section) ?>
        </div>

        <?php foreach ($items as $item):
          $isActive = ($activeNav ?? '') === $item['key'];
        ?>
          <a href="<?= $item['href'] ?>" class="nav-item <?= $isActive ? 'active' : '' ?>">
            <span class="nav-ic"><?= $item['icon'] ?></span>
            <span><?= $item['label'] ?></span>

            <?php if (!empty($item['badge']) && $item['badge'] > 0): ?>
              <span class="nav-badge"><?= $item['badge'] ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>

      <?php endforeach; ?>

      <div class="nav-section-label">Account</div>
      <a href="logout.php" class="nav-item">
        <span class="nav-ic">🚪</span>
        <span>Logout</span>
      </a>

    </nav>

    <!-- ================= FOOTER ================= -->
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="u-av"><?= $userInitial ?></div>
        <div>
          <div class="u-name"><?= htmlspecialchars($userName) ?></div>
          <div class="u-role"><?= ucfirst($userRole) ?></div>
        </div>
      </div>

      <button class="theme-btn" onclick="toggleTheme()">
        🌙 Dark Mode
      </button>
    </div>

  </aside>

  <!-- ================= MAIN ================= -->
  <div class="main-wrap">

    <!-- TOPBAR -->
    <header class="topbar">
      <div class="topbar-left">
        <button class="mobile-menu-btn btn" id="mobileMenuBtn">☰</button>
        <span class="topbar-title"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
      </div>

      <div class="topbar-right">
        <a href="notifications.php" class="topbar-notif-btn">
          🔔
          <?php if ($notifCount > 0): ?>
            <span class="notif-dot"></span>
          <?php endif; ?>
        </a>

        <div class="topbar-user">
          <div class="t-av"><?= $userInitial ?></div>
          <span><?= htmlspecialchars(explode(' ', $userName)[0]) ?></span>

          <?php if ($userRole !== 'farmer'): ?>
            <span class="badge badge-info"><?= ucfirst($userRole) ?></span>
          <?php endif; ?>
        </div>
      </div>
    </header>

    <!-- PAGE BODY -->
    <div class="page-body">