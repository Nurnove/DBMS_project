<?php
require_once 'db.php';
requireLogin();
$pageTitle = 'Disease & Solution';
$activeNav = 'disease';

$selectedCrop = (int)($_GET['crop_id'] ?? 0);
$selectedDisease = (int)($_GET['disease_id'] ?? 0);

$allCrops = $conn->query("SELECT * FROM crops ORDER BY name");

$diseases = null;
$solutions = null;
$cropName = '';

if ($selectedCrop > 0) {
    $cr = $conn->query("SELECT name FROM crops WHERE id=$selectedCrop")->fetch_assoc();
    $cropName = $cr['name'] ?? '';
    $diseases = $conn->query("SELECT * FROM crop_diseases WHERE crop_id=$selectedCrop ORDER BY name");
}

if ($selectedDisease > 0) {
    $solutions = $conn->query("SELECT * FROM solutions WHERE disease_id=$selectedDisease ORDER BY id");
    $dn = $conn->query("SELECT cd.name AS dname, c.name AS cname FROM crop_diseases cd JOIN crops c ON cd.crop_id=c.id WHERE cd.id=$selectedDisease")->fetch_assoc();
}

include 'layout.php';
?>

<div class="alert alert-info">🦠 Select a crop to see possible diseases, then choose a disease to get expert-recommended solutions.</div>

<!-- Step 1: Select Crop -->
<div class="card" style="margin-bottom:20px">
  <div class="card-title">Step 1 — Select Crop</div>
  <form method="get" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
    <div class="form-group" style="margin:0;flex:1;min-width:200px">
      <label>Choose your crop</label>
      <select name="crop_id" id="cropSelect">
        <option value="">— Select Crop —</option>
        <?php while ($c = $allCrops->fetch_assoc()): ?>
          <option value="<?= $c['id'] ?>" <?= $c['id'] == $selectedCrop ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['name']) ?> (<?= $c['season'] ?>)
          </option>
        <?php endwhile; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Show Diseases →</button>
  </form>
</div>

<!-- Step 2: Diseases -->
<?php if ($selectedCrop > 0 && $diseases): ?>
<div class="card" style="margin-bottom:20px">
  <div class="card-title">Step 2 — Diseases of <?= htmlspecialchars($cropName) ?></div>
  <?php if ($diseases->num_rows === 0): ?>
    <div class="empty-state">
      <div class="empty-icon">✅</div>
      <p>No diseases recorded for this crop yet.</p>
    </div>
  <?php else: ?>
    <div class="grid-3">
    <?php while ($d = $diseases->fetch_assoc()): ?>
      <a href="disease.php?crop_id=<?= $selectedCrop ?>&disease_id=<?= $d['id'] ?>" style="text-decoration:none">
        <div class="card <?= $d['id'] == $selectedDisease ? 'active' : '' ?>" style="padding:18px;border:2px solid <?= $d['id'] == $selectedDisease ? 'var(--accent)' : 'var(--border)' ?>;cursor:pointer">
          <div style="font-size:28px;margin-bottom:8px">🦠</div>
          <div style="font-family:'Syne',sans-serif;font-weight:700;margin-bottom:6px"><?= htmlspecialchars($d['name']) ?></div>
          <div style="font-size:13px;color:var(--text2)"><?= htmlspecialchars(substr($d['symptoms'] ?? '', 0, 80)) ?>...</div>
          <div style="margin-top:10px">
            <span class="btn btn-outline btn-sm">View Solutions →</span>
          </div>
        </div>
      </a>
    <?php endwhile; ?>
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<!-- Step 3: Solutions -->
<?php if ($selectedDisease > 0 && $solutions): ?>
<div class="card">
  <div class="card-title">💊 Solutions for <?= htmlspecialchars($dn['dname'] ?? '') ?></div>
  <?php if ($solutions->num_rows === 0): ?>
    <div class="empty-state"><div class="empty-icon">💊</div><p>No solutions recorded yet.</p></div>
  <?php else: ?>
    <?php while ($sol = $solutions->fetch_assoc()): ?>
      <div style="background:var(--surface2);border:1px solid var(--border);border-radius:var(--radius-sm);padding:18px;margin-bottom:14px">
        <div style="display:flex;align-items:flex-start;gap:14px">
          <div style="font-size:32px">💊</div>
          <div style="flex:1">
            <div style="font-size:14px;line-height:1.7;margin-bottom:10px"><?= htmlspecialchars($sol['solution_text'] ?? '') ?></div>
            <?php if ($sol['pesticide_name']): ?>
              <div style="display:flex;align-items:center;gap:8px">
                <span style="font-size:12px;color:var(--text3);font-weight:600">RECOMMENDED PESTICIDE:</span>
                <span class="badge badge-warn">🧪 <?= htmlspecialchars($sol['pesticide_name']) ?></span>
              </div>
            <?php else: ?>
              <span class="badge badge-success">🌿 Non-chemical solution</span>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
    <div class="alert alert-warn" style="margin-top:12px">
      ⚠️ Always follow pesticide label instructions. Consult a local agricultural officer before use.
    </div>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php include 'layout_end.php'; ?>