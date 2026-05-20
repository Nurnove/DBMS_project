<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

$user = currentUser($conn);
$uid = (int)$user['id'];
$locId = (int)($user['location_id'] ?? 0);

/* ---------------- STATS ---------------- */

$fieldCount = $conn->query("
SELECT COUNT(*) AS c
FROM fields
WHERE user_id=$uid
")->fetch_assoc()['c'];

$cropCount = $conn->query("
SELECT COUNT(*) AS c
FROM farmer_crops
WHERE user_id=$uid
AND status='growing'
")->fetch_assoc()['c'];

$pestCount = $conn->query("
SELECT COUNT(*) AS c
FROM pest_reports
WHERE user_id=$uid
")->fetch_assoc()['c'];

$notifCount = $conn->query("
SELECT COUNT(*) AS c
FROM notifications
WHERE user_id=$uid
AND is_read=0
")->fetch_assoc()['c'];


/* ---------------- RECENT CROPS ---------------- */

$recentCrops = $conn->query("
SELECT
    fc.*,
    c.name AS crop_name
FROM farmer_crops fc
JOIN crops c ON fc.crop_id = c.id
WHERE fc.user_id = $uid
ORDER BY fc.created_at DESC
LIMIT 5
");


/* ---------------- LOCATION + GLOBAL ADVISORY ---------------- */

$advisories = $conn->query("
SELECT *
FROM advisory_feed
WHERE
(
    location_id = $locId
    OR location_id IS NULL
)
AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)

ORDER BY
is_urgent DESC,
created_at DESC

LIMIT 4
");


/* ---------------- WEATHER ---------------- */

$weather = $conn->query("
SELECT *
FROM weather_data
WHERE location_id = $locId
ORDER BY recorded_at DESC
LIMIT 1
")->fetch_assoc();


/* ---------------- MARKET ---------------- */

$prices = $conn->query("
SELECT
    mp.*,
    c.name AS crop_name
FROM market_prices mp
JOIN crops c ON mp.crop_id = c.id
ORDER BY mp.date DESC, mp.id DESC
LIMIT 5
");

include 'layout.php';
?>



<!-- WELCOME -->
<div class="welcome-banner">
  <div class="welcome-left">

    <div class="welcome-greet">
      Good day 👋
    </div>

    <h2 class="welcome-title">
      Welcome back, <?= htmlspecialchars(explode(' ', $user['name'])[0]) ?>!
    </h2>

    <div class="welcome-sub">
      <?= date('l, d F Y') ?> — 
      <?= htmlspecialchars($user['division'] ?? '') ?>,
      <?= htmlspecialchars($user['district'] ?? '') ?>
    </div>

  </div>

  <?php if ($weather): ?>
  <div class="weather-card">

    <div class="weather-icon">
      <?= $weather['rain_probability'] > 60
        ? '🌧️'
        : ($weather['rain_probability'] > 30 ? '⛅' : '☀️') ?>
    </div>

    <div class="weather-temp">
      <?= $weather['temperature'] ?>°C
    </div>

    <div class="weather-info">
      Rain: <?= $weather['rain_probability'] ?>% ·
      Humidity: <?= $weather['humidity'] ?>%
    </div>

  </div>
  <?php endif; ?>

</div>


<!-- STATS -->
<div class="stats-grid">

  <a href="fields.php" style="text-decoration:none">
    <div class="stat-card">
      <div class="stat-icon">🗺️</div>
      <div class="stat-val"><?= $fieldCount ?></div>
      <div class="stat-label">My Fields</div>
    </div>
  </a>

  <a href="crops.php" style="text-decoration:none">
    <div class="stat-card">
      <div class="stat-icon">🌾</div>
      <div class="stat-val"><?= $cropCount ?></div>
      <div class="stat-label">Growing Crops</div>
    </div>
  </a>

  <a href="pest_report.php" style="text-decoration:none">
    <div class="stat-card">
      <div class="stat-icon">🐛</div>
      <div class="stat-val"><?= $pestCount ?></div>
      <div class="stat-label">Pest Reports</div>
    </div>
  </a>

  <a href="notifications.php" style="text-decoration:none">
    <div class="stat-card">
      <div class="stat-icon">🔔</div>
      <div class="stat-val"><?= $notifCount ?></div>
      <div class="stat-label">Unread Alerts</div>
    </div>
  </a>

</div>



<div class="grid-2">

  <!-- ACTIVE CROPS -->
  <div class="card">

    <div class="card-title">
      🌾 Active Crops
    </div>

    <?php if ($recentCrops->num_rows === 0): ?>

      <div class="empty-state">

        <div class="empty-icon">🌱</div>

        <p>No crops tracked yet</p>

        <a href="crops.php" class="btn btn-primary btn-sm">
          + Add Crop
        </a>

      </div>

    <?php else: ?>

      <div class="table-wrap">

      <table>

        <thead>
          <tr>
            <th>Crop</th>
            <th>Planted</th>
            <th>Status</th>
          </tr>
        </thead>

        <tbody>

        <?php while ($cr = $recentCrops->fetch_assoc()): ?>

          <tr>

            <td>
              <?= htmlspecialchars($cr['crop_name']) ?>
            </td>

            <td>
              <?= $cr['planted_date'] ?>
            </td>

            <td>

              <?php
              $s = $cr['status'];

              $cls =
              $s === 'growing'
              ? 'badge-success'
              : ($s === 'harvested'
                ? 'badge-info'
                : 'badge-danger');
              ?>

              <span class="badge <?= $cls ?>">
                <?= ucfirst($s) ?>
              </span>

            </td>

          </tr>

        <?php endwhile; ?>

        </tbody>

      </table>

      </div>

      <a href="crops.php"
         class="btn btn-outline btn-sm"
         style="margin-top:14px">
         View All →
      </a>

    <?php endif; ?>

  </div>



  <!-- ADVISORY -->
  <div class="card">

    <div class="card-title">
      📢 Latest Advisories
    </div>

    <?php if ($advisories->num_rows === 0): ?>

      <div class="empty-state">

        <div class="empty-icon">📢</div>

        <p>No advisories available</p>

      </div>

    <?php else: ?>

      <?php while ($adv = $advisories->fetch_assoc()): ?>

      <?php
      $cat = $adv['category'];

      $catCls =
      $cat === 'weather'
      ? 'badge-info'
      : ($cat === 'pest'
        ? 'badge-danger'
        : ($cat === 'market'
          ? 'badge-success'
          : 'badge-gray'));

      $catIcon =
      $cat === 'weather'
      ? '🌦️'
      : ($cat === 'pest'
        ? '🐛'
        : ($cat === 'market'
          ? '💰'
          : '📌'));
      ?>

      <div style="
      padding:12px 0;
      border-bottom:1px solid var(--border)
      ">

        <div style="
        display:flex;
        align-items:center;
        gap:8px;
        margin-bottom:6px;
        flex-wrap:wrap;
        ">

          <span class="badge <?= $catCls ?>">
            <?= $catIcon ?>
            <?= ucfirst($cat) ?>
          </span>

          <?php if ($adv['is_urgent']): ?>
            <span class="badge badge-danger">
              🔥 Urgent
            </span>
          <?php endif; ?>

          <span style="
          font-size:12px;
          color:var(--text3)
          ">
            <?= date('d M', strtotime($adv['created_at'])) ?>
          </span>

        </div>

        <div style="
        font-family:'Syne',sans-serif;
        font-weight:700;
        font-size:14px;
        margin-bottom:4px
        ">
          <?= htmlspecialchars($adv['title']) ?>
        </div>

        <div style="
        font-size:13px;
        color:var(--text2)
        ">
          <?= htmlspecialchars(substr($adv['content'],0,100)) ?>...
        </div>

      </div>

      <?php endwhile; ?>

    <?php endif; ?>

    <a href="advisory.php"
       class="btn btn-outline btn-sm"
       style="margin-top:14px">
       View All →
    </a>

  </div>

</div>



<!-- QUICK ACTIONS -->
<div class="card" style="margin-top:20px">

  <div class="card-title">
    ⚡ Quick Actions
  </div>

  <div style="
  display:flex;
  flex-wrap:wrap;
  gap:12px
  ">

    <a href="fields.php?action=add"
       class="btn btn-outline">
       🗺️ Add Field
    </a>

    <a href="crops.php?action=add"
       class="btn btn-outline">
       🌾 Plant Crop
    </a>

    <a href="pest_report.php?action=add"
       class="btn btn-outline">
       🐛 Report Pest
    </a>

    <a href="disease.php"
       class="btn btn-outline">
       🦠 Check Disease
    </a>

    <a href="irrigation.php"
       class="btn btn-outline">
       💧 Irrigation Log
    </a>

    <a href="market.php"
       class="btn btn-outline">
       💰 Market Prices
    </a>

  </div>

</div>

<?php include 'layout_end.php'; ?>