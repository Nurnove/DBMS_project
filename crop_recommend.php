<?php
require_once 'db.php';
requireLogin();

$pageTitle = "Season Wise Crop Recommendation";

$season = $_GET['season'] ?? 'Monsoon';

$validSeasons = ['Monsoon', 'Winter', 'Summer', 'Spring', 'Year-round'];
if (!in_array($season, $validSeasons)) {
    $season = 'Monsoon';
}

/* =========================================
   MAIN QUERY (ONLY crop_calendar BASED)
========================================= */
$sql = "
SELECT 
    c.id,
    c.name AS crop_name,
    cc.season,
    cc.suitability_score,
    cc.reason
FROM crop_calendar cc
JOIN crops c ON cc.crop_id = c.id
WHERE cc.season = '$season'
   OR cc.season = 'Year-round'
ORDER BY cc.suitability_score DESC
";

$result = $conn->query($sql);

include 'layout.php';
?>

<div class="card">
  <div class="card-title">🌾 Season Wise Crop Recommendation</div>

  <!-- FILTER -->
  <form method="GET" style="display:flex;gap:10px;margin-bottom:15px">
    <select name="season">
      <?php foreach ($validSeasons as $s): ?>
        <option value="<?= $s ?>" <?= $season == $s ? 'selected' : '' ?>>
          <?= $s ?>
        </option>
      <?php endforeach; ?>
    </select>

    <button class="btn btn-primary">🌱 Suggest</button>
  </form>

  <!-- RESULTS -->
  <?php if ($result->num_rows == 0): ?>
    <div class="empty-state">No recommendation found</div>
  <?php else: ?>

    <?php while($row = $result->fetch_assoc()): ?>

      <?php
        $score = (int)$row['suitability_score'];

        if ($score >= 90) {
            $color = "green";
            $label = "Best Choice";
        } elseif ($score >= 70) {
            $color = "orange";
            $label = "Good Choice";
        } else {
            $color = "red";
            $label = "Not Suitable";
        }
      ?>

      <div style="
        padding:15px;
        border:1px solid var(--border);
        border-radius:12px;
        margin-bottom:12px;
        background:var(--surface);
      ">

        <div style="display:flex;justify-content:space-between;align-items:center">
          <div style="font-size:18px;font-weight:700">
            🌾 <?= htmlspecialchars($row['crop_name']) ?>
          </div>

          <span style="
            background:<?= $color ?>;
            color:#fff;
            padding:4px 10px;
            border-radius:20px;
            font-size:12px;
          ">
            <?= $label ?>
          </span>
        </div>

        <div style="margin-top:8px">
          📅 Season: <b><?= htmlspecialchars($row['season']) ?></b>
        </div>

        

        <?php if (!empty($row['reason'])): ?>
        <div style="font-size:13px;color:var(--text2);margin-top:6px">
          <?= htmlspecialchars($row['reason']) ?>
        </div>
        <?php endif; ?>

      </div>

    <?php endwhile; ?>

  <?php endif; ?>
</div>

<?php include 'layout_end.php'; ?>