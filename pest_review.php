<?php
require_once 'db.php';
requireLogin();

if ($_SESSION['user_role'] !== 'expert') {
    header("Location: dashboard.php");
    exit;
}

$user = currentUser($conn);

/* =========================
   FILTER
========================= */
$location_id = isset($_GET['location_id']) ? (int)$_GET['location_id'] : 0;

/* =========================
   LOCATIONS (optional - safe fallback)
   যদি table না থাকে তাহলে error হবে না
========================= */
$locations = $conn->query("SHOW TABLES LIKE 'locations'");

/* =========================
   PEST REPORTS + REVIEW STATUS
========================= */
$sql = "
SELECT pr.*,
       c.name AS crop_name,
       p.name AS pest_name,
       pr.district,
       u.name AS farmer_name,
       r.id AS review_id,
       r.advice,
       (SELECT image_url FROM pest_images WHERE report_id=pr.id LIMIT 1) AS img

FROM pest_reports pr
JOIN crops c ON pr.crop_id = c.id
JOIN pests p ON pr.pest_id = p.id
JOIN users u ON pr.user_id = u.id
LEFT JOIN pest_reviews r ON pr.id = r.report_id
";

if ($location_id > 0) {
    $sql .= " WHERE pr.id IN (
        SELECT id FROM pest_reports WHERE id = $location_id
    ) ";
}

$sql .= " ORDER BY (r.id IS NOT NULL), pr.created_at DESC";

$reports = $conn->query($sql);

include 'layout.php';
?>

<div class="card">
  <div class="card-title">🐛 Pest Reports (Expert Review Panel)</div>

  <!-- FILTER -->
  <?php if ($locations && $locations->num_rows > 0): ?>
  <form method="GET" style="margin-bottom:15px">
    <select name="location_id" onchange="this.form.submit()">
      <option value="0">🌍 All Locations</option>
    </select>
  </form>
  <?php endif; ?>

  <!-- LIST -->
  <?php if ($reports->num_rows === 0): ?>
    <div class="empty-state">No pest reports found.</div>

  <?php else: ?>

    <div class="grid-3">

    <?php while($r = $reports->fetch_assoc()): ?>

      <div class="card">

        <!-- IMAGE -->
        <?php if ($r['img']): ?>
          <img src="<?= htmlspecialchars($r['img']) ?>"
               style="width:100%;height:140px;object-fit:cover;border-radius:10px;margin-bottom:10px">
        <?php endif; ?>

        <!-- BASIC INFO -->
        <div class="fw-700"><?= htmlspecialchars($r['crop_name']) ?></div>
        <div class="text-small">🐛 <?= htmlspecialchars($r['pest_name']) ?></div>

        <!-- LOCATION + FARMER -->
        <div class="text-small text-muted mt-8">
          👨‍🌾 <?= htmlspecialchars($r['farmer_name']) ?><br>
          📍 <?= htmlspecialchars($r['district'] ?? 'Unknown') ?><br>
          📅 <?= date('d M Y', strtotime($r['created_at'])) ?>
        </div>

        <!-- STATUS -->
        <div style="margin-top:8px">
          <?php if ($r['review_id']): ?>
              <span class="badge badge-success">✔ Reviewed</span>
          <?php else: ?>
              <span class="badge badge-warn">⏳ Pending</span>
          <?php endif; ?>
        </div>

        <!-- EXISTING ADVICE -->
        <?php if ($r['advice']): ?>
          <div style="margin-top:8px;font-size:12px;color:green">
            🧠 <?= htmlspecialchars($r['advice']) ?>
          </div>
        <?php endif; ?>

        <!-- ACTION -->
        <div class="mt-12">

          <?php if ($r['review_id']): ?>
            <a href="pest_review_action.php?id=<?= $r['id'] ?>"
               class="btn btn-outline btn-sm">
              ✏ Update Advice
            </a>
          <?php else: ?>
            <a href="pest_review_action.php?id=<?= $r['id'] ?>"
               class="btn btn-primary btn-sm">
              💡 Give Advice
            </a>
          <?php endif; ?>

        </div>

      </div>

    <?php endwhile; ?>

    </div>

  <?php endif; ?>

</div>

<?php include 'layout_end.php'; ?>