<?php
require_once 'db.php';
requireLogin();

if ($_SESSION['user_role'] !== 'expert') {
    header("Location: dashboard.php");
    exit;
}

$user = currentUser($conn);
$uid = (int)$user['id'];

/* =========================
   STATS
========================= */
$advisoryCount = $conn->query("SELECT COUNT(*) as c FROM advisory_feed")->fetch_assoc()['c'];
$questionCount = $conn->query("SELECT COUNT(*) as c FROM questions")->fetch_assoc()['c'];
$pestCount     = $conn->query("SELECT COUNT(*) as c FROM pest_reports")->fetch_assoc()['c'];
$marketCount   = $conn->query("SELECT COUNT(*) as c FROM market_prices")->fetch_assoc()['c'];

$newPestCount = $conn->query("
    SELECT COUNT(*) as c FROM pest_reports pr
    WHERE pr.id NOT IN (SELECT report_id FROM pest_reviews)
")->fetch_assoc()['c'];

/* =========================
   UNANSWERED QUESTIONS ONLY
========================= */
$questions = $conn->query("
    SELECT q.*, u.name
    FROM questions q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN answers a ON q.id = a.question_id
    WHERE a.id IS NULL
    ORDER BY q.created_at DESC
    LIMIT 5
");

/* =========================
   NEW PEST REPORTS
========================= */
$newPests = $conn->query("
    SELECT pr.*, c.name AS crop_name, p.name AS pest_name
    FROM pest_reports pr
    JOIN crops c ON pr.crop_id = c.id
    JOIN pests p ON pr.pest_id = p.id
    WHERE pr.id NOT IN (SELECT report_id FROM pest_reviews)
    ORDER BY pr.created_at DESC
    LIMIT 10
");

/* =========================
   MARKET PRICES
========================= */
$prices = $conn->query("
    SELECT mp.*, c.name AS crop_name
    FROM market_prices mp
    JOIN crops c ON mp.crop_id = c.id
    ORDER BY mp.date DESC
    LIMIT 5
");

include 'layout.php';
?>

<div style="flex:1">

<!-- ================= STATS ================= -->
<div class="stats-grid">

  <div class="stat-card">
    <div class="stat-icon">📢</div>
    <div class="stat-val"><?= $advisoryCount ?></div>
    <div class="stat-label">Advisories</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">❓</div>
    <div class="stat-val"><?= $questionCount ?></div>
    <div class="stat-label">Questions</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">🐛</div>
    <div class="stat-val"><?= $pestCount ?></div>
    <div class="stat-label">All Pest Reports</div>
  </div>

  <div class="stat-card">
    <div class="stat-icon">🆕</div>
    <div class="stat-val"><?= $newPestCount ?></div>
    <div class="stat-label">New Reports</div>
  </div>

</div>

<!-- ================= QUICK ACTIONS ================= -->
<div class="card" style="margin-top:20px;">
  <div class="card-title">⚡ Expert Actions</div>

  <div style="display:flex;flex-wrap:wrap;gap:10px;">
    <a href="advisory_manage.php" class="btn btn-primary">+ Create Advisory</a>
    <a href="faq_manage.php" class="btn btn-outline">Answer Questions</a>
    <a href="pest_review.php" class="btn btn-outline">Review Pest</a>
    <a href="market_manage.php" class="btn btn-outline">Add Market Price</a>
  </div>
</div>

<!-- ================= UNANSWERED QUESTIONS ================= -->
<div class="card" style="margin-top:20px;">
  <div class="card-title">❓ Latest Unanswered Questions</div>

  <?php if ($questions->num_rows === 0): ?>
    <div style="padding:10px;color:gray">
      🎉 All questions are answered
    </div>
  <?php else: ?>

    <?php while($q = $questions->fetch_assoc()): ?>
      <div style="padding:10px;border-bottom:1px solid var(--border)">
        <b><?= htmlspecialchars($q['question']) ?></b><br>

        <small>
          👨‍🌾 <?= htmlspecialchars($q['name']) ?>
          | 📂 <?= $q['category'] ?>
        </small><br>

        <a href="faq_manage.php?id=<?= $q['id'] ?>" class="btn btn-primary btn-sm">
          Answer →
        </a>
      </div>
    <?php endwhile; ?>

  <?php endif; ?>
</div>

<!-- ================= NEW PEST REPORTS ================= -->
<div class="card" style="margin-top:20px;">
  <div class="card-title">🆕 New Pest Reports (Unreviewed)</div>

  <?php if ($newPests->num_rows === 0): ?>
    <div style="padding:10px;color:gray">
      🎉 No new pest reports
    </div>
  <?php else: ?>

    <?php while($p = $newPests->fetch_assoc()): ?>
      <div style="padding:10px;border-bottom:1px solid var(--border)">
        <b><?= htmlspecialchars($p['crop_name']) ?></b>
        → <?= htmlspecialchars($p['pest_name']) ?>

        <div style="font-size:12px;color:gray">
          📍 <?= htmlspecialchars($p['district'] ?? 'Unknown') ?> |
          📅 <?= date('d M Y', strtotime($p['created_at'])) ?>
        </div>

        <a href="pest_review.php?id=<?= $p['id'] ?>" class="btn btn-primary btn-sm">
          🧠 Review Now
        </a>
      </div>
    <?php endwhile; ?>

  <?php endif; ?>
</div>

<!-- ================= MARKET ================= -->
<div class="card" style="margin-top:20px;">
  <div class="card-title">💰 Latest Market Prices</div>

  <?php while($m = $prices->fetch_assoc()): ?>
    <div style="padding:10px;border-bottom:1px solid var(--border)">
      <?= htmlspecialchars($m['crop_name']) ?>
      → <b><?= $m['price'] ?></b> / <?= $m['unit'] ?>
    </div>
  <?php endwhile; ?>
</div>

</div>

<?php include 'layout_end.php'; ?>