<?php
require_once 'db.php';
requireLogin();

$pageTitle = "Advisory Feed";
$activeNav = "advisory";

$user = currentUser($conn);
$userLoc = (int)($user['location_id'] ?? 0);

$filterLoc = (int)($_GET['location_id'] ?? $userLoc);

/* ---------------- LOCATIONS ---------------- */
$locations = $conn->query("
SELECT id, division, district 
FROM locations
ORDER BY division, district
");

/* ---------------- URGENT ADVISORY (PINNED TOP) ---------------- */
$urgent = $conn->query("
SELECT a.*, l.district
FROM advisory_feed a
LEFT JOIN locations l ON a.location_id = l.id
WHERE a.is_urgent = 1
AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY a.created_at DESC
");

/* ---------------- MAIN ADVISORY (LAST 7 DAYS) ---------------- */
$sql = "
SELECT a.*, l.district
FROM advisory_feed a
LEFT JOIN locations l ON a.location_id = l.id
WHERE a.is_urgent = 0
AND a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
AND (a.location_id = $filterLoc OR a.location_id IS NULL)
ORDER BY a.created_at DESC
";

$advisories = $conn->query($sql);

include 'layout.php';
?>

<!-- HEADER -->
<div class="flex-between mb-20">
  <div>
    <h1>📢 Advisory Feed</h1>
    <p style="color:var(--text3)">
      Last 7 days fresh advisory updates
    </p>
  </div>
</div>

<!-- FILTER -->
<div class="card mb-20">
<form method="get">

<select name="location_id" onchange="this.form.submit()">
<option value="">🌍 All Locations</option>
<?php while($l=$locations->fetch_assoc()): ?>
<option value="<?= $l['id'] ?>" <?= $filterLoc==$l['id']?'selected':'' ?>>
<?= $l['division']." - ".$l['district'] ?>
</option>
<?php endwhile; ?>
</select>

</form>
</div>

<!-- 🔥 URGENT PINNED ADVISORY -->
<?php if ($urgent->num_rows > 0): ?>
<div class="card mb-20" style="border:2px solid #ff4d4d;">
  <div class="card-title">🚨 Urgent Advisory</div>

  <?php while($u=$urgent->fetch_assoc()): ?>
  <div style="padding:12px 0;border-bottom:1px solid var(--border)">
    
    <div style="display:flex;gap:8px;align-items:center">
      <span class="badge badge-danger">URGENT</span>
      <span style="font-size:12px;color:var(--text3)">
        📍 <?= $u['district'] ?? 'Global' ?>
      </span>
    </div>

    <div style="font-weight:800;margin-top:6px">
      <?= htmlspecialchars($u['title']) ?>
    </div>

    <div style="font-size:13px;color:var(--text2);margin-top:4px">
      <?= htmlspecialchars($u['content']) ?>
    </div>

    <div style="font-size:11px;color:var(--text3);margin-top:6px">
      <?= date('d M Y', strtotime($u['created_at'])) ?>
    </div>

  </div>
  <?php endwhile; ?>
</div>
<?php endif; ?>

<!-- 📢 NORMAL ADVISORY -->
<div class="card">

<?php if ($advisories->num_rows === 0): ?>
<div class="empty-state">
  <div class="empty-icon">📢</div>
  <p>No recent advisory found (last 7 days)</p>
</div>
<?php endif; ?>

<?php while($a=$advisories->fetch_assoc()): ?>

<div style="padding:14px 0;border-bottom:1px solid var(--border)">

  <div style="display:flex;gap:8px;align-items:center">
    <span class="badge badge-info">
      <?= ucfirst($a['category']) ?>
    </span>

    <span style="font-size:12px;color:var(--text3)">
      📍 <?= $a['district'] ?? 'Global' ?>
    </span>
  </div>

  <div style="font-weight:700;margin-top:6px">
    <?= htmlspecialchars($a['title']) ?>
  </div>

  <div style="font-size:13px;color:var(--text2);margin-top:4px">
    <?= htmlspecialchars($a['content']) ?>
  </div>

  <div style="font-size:11px;color:var(--text3);margin-top:6px">
    <?= date('d M Y', strtotime($a['created_at'])) ?>
  </div>

</div>

<?php endwhile; ?>

</div>

<?php include 'layout_end.php'; ?>