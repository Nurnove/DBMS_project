<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Market Prices';
$activeNav = 'market';

$user = currentUser($conn);


$filterCrop = (int)($_GET['crop_id'] ?? 0);
$filterLoc  = (int)($_GET['location_id'] ?? 0);


$isGlobal   = (!$filterCrop && !$filterLoc);
$isLocOnly  = (!$filterCrop && $filterLoc);
$isCropOnly = ($filterCrop && !$filterLoc);
$isFull     = ($filterCrop && $filterLoc);


$crops = $conn->query("SELECT id, name FROM crops ORDER BY name");

$locations = $conn->query("
SELECT id, division, district
FROM locations
ORDER BY division, district
");


$todayCount = $conn->query("
SELECT COUNT(*) AS c FROM market_prices WHERE date = CURDATE()
")->fetch_assoc()['c'];

$totalCrops = $conn->query("
SELECT COUNT(DISTINCT crop_id) AS c FROM market_prices
")->fetch_assoc()['c'];

$highPrice = $conn->query("
SELECT mp.price, c.name AS crop_name
FROM market_prices mp
JOIN crops c ON mp.crop_id = c.id
WHERE mp.date = CURDATE()
ORDER BY mp.price DESC
LIMIT 1
")->fetch_assoc();


$avg = 0;
$avgLabel = "—";

if ($isCropOnly) {

$res = $conn->query("
SELECT AVG(price) AS avg_price
FROM market_prices
WHERE crop_id = $filterCrop
");

$avg = $res->fetch_assoc()['avg_price'];
$avgLabel = "🌾 7-Day Bangladesh Avg";

} elseif ($isFull) {

$res = $conn->query("
SELECT AVG(price) AS avg_price
FROM market_prices
WHERE crop_id = $filterCrop
AND location_id = $filterLoc
");

$avg = $res->fetch_assoc()['avg_price'];
$avgLabel = "📍 7-Day Local Avg";

} else {

$avg = 0;
$avgLabel = "No Average (Overview Mode)";

}


$showAI = false;
$prediction = null;
$aiText = '';

if ($isCropOnly || $isFull) {

$showAI = true;

$res = $conn->query("
SELECT AVG(price) AS avg_price
FROM market_prices
WHERE crop_id = $filterCrop
" . ($filterLoc ? "AND location_id = $filterLoc" : "") . "
AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
");

$avgVal = $res->fetch_assoc()['avg_price'] ?? 0;

$prediction = $avgVal * 1.02;

$cropName = $conn->query("
SELECT name FROM crops WHERE id=$filterCrop
")->fetch_assoc()['name'];

$aiText = "🤖 Tomorrow ".$cropName." price forecast:";
}


$labels = [];
$data = [];

if ($isGlobal) {

/* 🌍 GLOBAL */
$chart = $conn->query("
SELECT c.name AS crop_name, AVG(mp.price) AS avg_price
FROM market_prices mp
JOIN crops c ON mp.crop_id = c.id
WHERE mp.date = CURDATE()
GROUP BY mp.crop_id
");

while ($r = $chart->fetch_assoc()) {
$labels[] = $r['crop_name'];
$data[] = $r['avg_price'];
}

} elseif ($isLocOnly) {


$chart = $conn->query("
SELECT c.name AS crop_name, AVG(mp.price) AS avg_price
FROM market_prices mp
JOIN crops c ON mp.crop_id = c.id
WHERE mp.location_id = $filterLoc
AND mp.date = CURDATE()
GROUP BY mp.crop_id
");

while ($r = $chart->fetch_assoc()) {
$labels[] = $r['crop_name'];
$data[] = $r['avg_price'];
}

} elseif ($isCropOnly) {


$chart = $conn->query("
SELECT date, AVG(price) AS avg_price
FROM market_prices
WHERE crop_id = $filterCrop
AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY date
ORDER BY date ASC
");

while ($r = $chart->fetch_assoc()) {
$labels[] = date('d M', strtotime($r['date']));
$data[] = $r['avg_price'];
}

} else {


$chart = $conn->query("
SELECT date, AVG(price) AS avg_price
FROM market_prices
WHERE crop_id = $filterCrop
AND location_id = $filterLoc
AND date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
GROUP BY date
ORDER BY date ASC
");

while ($r = $chart->fetch_assoc()) {
$labels[] = date('d M', strtotime($r['date']));
$data[] = $r['avg_price'];
}

}

include 'layout.php';
?>

<!-- HEADER -->
<div class="flex-between mb-24">
  <div>
    <h1 style="font-size:1.9rem;font-weight:900;">💰 Market Prices</h1>
    <p style="color:var(--text3)">
      <?= $isGlobal ? "🌍 Bangladesh Market Overview" : "Filtered Market Data" ?>
    </p>
  </div>
</div>

<!-- STATS -->
<div class="stats-grid">

  <div class="stat-card">
    <div class="stat-val"><?= $todayCount ?></div>
    <div class="stat-label">Today's Entries</div>
  </div>

  <div class="stat-card">
    <div class="stat-val"><?= $totalCrops ?></div>
    <div class="stat-label">Crops</div>
  </div>

  <div class="stat-card">
    <div class="stat-val">
      <?= $highPrice['crop_name'] ?? '—' ?><br>
      ৳<?= $highPrice['price'] ?? 0 ?>
    </div>
    <div class="stat-label">🏆 Highest Today</div>
  </div>

  <!-- <div class="stat-card">
    <div class="stat-val">
      <?= $avgLabel ?><br>
      ৳<?= number_format($avg,2) ?>
    </div>
    <div class="stat-label">Average</div>
  </div> -->

</div>

<!-- AI PREDICTION -->
<?php if($showAI): ?>
<div class="card mb-20">
  <div class="card-title"><?= $aiText ?></div>
  <div style="font-size:22px;font-weight:800;color:var(--accent)">
    ৳<?= number_format($prediction,2) ?>
  </div>
</div>
<?php endif; ?>

<!-- TODAY AVERAGE PRICE -->
<?php if($isGlobal): ?>

<div class="card mb-20">
  <div class="card-title">
    🌍 Today Average Price in Bangladesh
  </div>

  <table style="width:100%">
    <tr>
      <th align="left">Crop</th>
      <th align="right">Average Price</th>
    </tr>

<?php
$todayAvg = $conn->query("
SELECT c.name AS crop_name,
AVG(mp.price) AS avg_price
FROM market_prices mp
JOIN crops c ON mp.crop_id = c.id
WHERE mp.date = CURDATE()
GROUP BY mp.crop_id
ORDER BY avg_price DESC
");

while($row = $todayAvg->fetch_assoc()):
?>

<tr>
  <td><?= $row['crop_name'] ?></td>

  <td align="right">
    ৳<?= number_format($row['avg_price'],2) ?>
  </td>
</tr>

<?php endwhile; ?>

  </table>
</div>

<?php endif; ?>

<!-- CHART -->
<div class="card mb-20">
  <div class="card-title">
    <?= $isGlobal ? "🌍 Today Crop-wise Price" : "📈 Market Trend" ?>
  </div>

  <canvas id="chart"></canvas>
</div>

<!-- FILTERS -->
<div class="card mb-20">
<form method="get">

<select name="crop_id" onchange="this.form.submit()">
<option value="">All Crops</option>
<?php while($c=$crops->fetch_assoc()): ?>
<option value="<?= $c['id'] ?>" <?= $filterCrop==$c['id']?'selected':'' ?>>
<?= $c['name'] ?>
</option>
<?php endwhile; ?>
</select>

<select name="location_id" onchange="this.form.submit()">
<option value="">All Locations</option>
<?php while($l=$locations->fetch_assoc()): ?>
<option value="<?= $l['id'] ?>" <?= $filterLoc==$l['id']?'selected':'' ?>>
<?= $l['division'].' - '.$l['district'] ?>
</option>
<?php endwhile; ?>
</select>

<a href="market.php">Clear</a>

</form>
</div>

<!-- CHART JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
new Chart(document.getElementById('chart'), {
type: 'line',
data: {
labels: <?= json_encode($labels) ?>,
datasets: [{
label: "Market Data",
data: <?= json_encode($data) ?>,
tension: 0.4,
fill: true
}]
}
});
</script>

<?php include 'layout_end.php'; ?>