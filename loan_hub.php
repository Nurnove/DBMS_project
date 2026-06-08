<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Loan Hub';
$activeNav = 'loan_hub';

$user = currentUser($conn);
$uid  = (int)$user['id'];

/* ─── FILTER / SEARCH ─── */
$filterCat     = clean($conn, $_GET['cat']      ?? '');
$filterType    = clean($conn, $_GET['type']      ?? '');
$filterMaxRate = isset($_GET['max_rate']) && $_GET['max_rate'] !== '' ? (float)$_GET['max_rate'] : null;
$search        = clean($conn, $_GET['q']         ?? '');

/* ─── COMPARE IDS from session ─── */
if (!isset($_SESSION['compare_loans'])) $_SESSION['compare_loans'] = [];

/* ─── COMPARE ACTIONS ─── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';

    if ($act === 'add_compare') {
        $pid = (int)$_POST['product_id'];
        if (!in_array($pid, $_SESSION['compare_loans']) && count($_SESSION['compare_loans']) < 4) {
            $_SESSION['compare_loans'][] = $pid;
        }
        header('Location: loan_hub.php?' . http_build_query($_GET));
        exit;
    }

    if ($act === 'remove_compare') {
        $pid = (int)$_POST['product_id'];
        $_SESSION['compare_loans'] = array_values(array_filter($_SESSION['compare_loans'], fn($x) => $x !== $pid));
        header('Location: loan_hub.php?' . http_build_query($_GET));
        exit;
    }

    if ($act === 'clear_compare') {
        $_SESSION['compare_loans'] = [];
        header('Location: loan_hub.php');
        exit;
    }

    if ($act === 'save_application') {
        $prodId = (int)$_POST['product_id'];
        $amount = (float)$_POST['amount_needed'];
        $acres  = $_POST['land_acres'] !== '' ? (float)$_POST['land_acres'] : null;
        $crop   = clean($conn, $_POST['crop_type'] ?? '');
        $purpose= clean($conn, $_POST['purpose']   ?? '');

        $stmt = $conn->prepare("INSERT INTO loan_applications (user_id, product_id, amount_needed, land_acres, crop_type, purpose) VALUES (?,?,?,?,?,?)");
        $stmt->bind_param('iiddss', $uid, $prodId, $amount, $acres, $crop, $purpose);
        $stmt->execute();
        header('Location: loan_hub.php?saved=1');
        exit;
    }
}

/* ─── BUILD QUERY ─── */
$where = ['lp.is_active=1', 'prov.is_active=1'];
if ($filterCat)                  $where[] = "lp.category='$filterCat'";
if ($filterType)                 $where[] = "prov.type='$filterType'";
if ($filterMaxRate !== null)     $where[] = "lp.interest_rate <= $filterMaxRate";
if ($search)                     $where[] = "(lp.name LIKE '%$search%' OR prov.name LIKE '%$search%' OR lp.eligible_crops LIKE '%$search%')";
$whereSQL = implode(' AND ', $where);

$products = $conn->query("
    SELECT lp.*, prov.name AS prov_name, prov.type AS prov_type, prov.logo_emoji, prov.phone AS prov_phone, prov.website AS prov_website
    FROM loan_products lp
    JOIN loan_providers prov ON lp.provider_id = prov.id
    WHERE $whereSQL
    ORDER BY lp.is_featured DESC, lp.interest_rate ASC
");

/* ─── COMPARE DATA ─── */
$compareData = [];
if (!empty($_SESSION['compare_loans'])) {
    $ids = implode(',', array_map('intval', $_SESSION['compare_loans']));
    $cRes = $conn->query("
        SELECT lp.*, prov.name AS prov_name, prov.logo_emoji, prov.type AS prov_type
        FROM loan_products lp
        JOIN loan_providers prov ON lp.provider_id = prov.id
        WHERE lp.id IN ($ids)
        ORDER BY lp.interest_rate ASC
    ");
    while ($row = $cRes->fetch_assoc()) $compareData[] = $row;
}

/* ─── PERSONALISED SUGGESTIONS ─── */
// Get user's fields & crops for smart suggestions
$userFields = $conn->query("
    SELECT COALESCE(SUM(area), 0) AS total 
    FROM fields 
    WHERE user_id = $uid
")->fetch_assoc()['total'];
$userCrops  = $conn->query("SELECT c.name FROM farmer_crops fc JOIN crops c ON fc.crop_id=c.id WHERE fc.user_id=$uid AND fc.status='growing' LIMIT 5");
$cropNames  = [];
while ($cr = $userCrops->fetch_assoc()) $cropNames[] = $cr['name'];

$suggestions = [];
if (!empty($cropNames) || $userFields > 0) {
    $cropSearch = implode('|', $cropNames);
    $suggestions = [];
    $sugRes = $conn->query("
        SELECT lp.*, prov.name AS prov_name, prov.logo_emoji, prov.type AS prov_type
        FROM loan_products lp
        JOIN loan_providers prov ON lp.provider_id = prov.id
        WHERE lp.is_active=1 AND prov.is_active=1
          AND lp.category='crop'
          AND (lp.min_land_acres IS NULL OR lp.min_land_acres <= " . max((float)$userFields, 0.1) . ")
        ORDER BY lp.interest_rate ASC
        LIMIT 3
    ");
    while ($s = $sugRes->fetch_assoc()) $suggestions[] = $s;
}

/* ─── STATS ─── */
$totalProviders = $conn->query("SELECT COUNT(*) AS c FROM loan_providers WHERE is_active=1")->fetch_assoc()['c'];
$totalProducts  = $conn->query("SELECT COUNT(*) AS c FROM loan_products WHERE is_active=1")->fetch_assoc()['c'];
$lowestRate     = $conn->query("SELECT MIN(interest_rate) AS r FROM loan_products WHERE is_active=1")->fetch_assoc()['r'];
$mySavedApps    = $conn->query("SELECT COUNT(*) AS c FROM loan_applications WHERE user_id=$uid")->fetch_assoc()['c'];

include 'layout.php';
?>

<?php if (!empty($_GET['saved'])): ?>
<div class="alert alert-success">✅ Application details saved! Review them in My Applications.</div>
<?php endif; ?>

<!-- ══ HERO BANNER ══ -->
<div class="loan-hero">
  <div class="loan-hero-content">
    <div class="loan-hero-badge">🏦 Agricultural Finance</div>
    <h1 class="loan-hero-title">Loan Hub</h1>
    <p class="loan-hero-sub">Find the right agricultural loan for your farm — compare rates, eligibility & documents from <?= $totalProviders ?> trusted institutions.</p>
  </div>
  <div class="loan-hero-stats">
    <div class="lh-stat"><div class="lh-stat-val"><?= $totalProviders ?></div><div class="lh-stat-label">Institutions</div></div>
    <div class="lh-stat"><div class="lh-stat-val"><?= $totalProducts ?></div><div class="lh-stat-label">Loan Products</div></div>
    <div class="lh-stat"><div class="lh-stat-val"><?= $lowestRate ?>%</div><div class="lh-stat-label">Lowest Rate</div></div>
    <div class="lh-stat"><div class="lh-stat-val"><?= $mySavedApps ?></div><div class="lh-stat-label">My Applications</div></div>
  </div>
</div>

<!-- ══ PERSONALISED SUGGESTIONS (if farmer has data) ══ -->
<?php if (!empty($suggestions)): ?>
<div class="card loan-suggest-card reveal">
  <div class="suggest-header">
    <div>
      <div class="card-title">🎯 Suggested for You</div>
      <p style="font-size:13px;color:var(--text3);margin-top:2px">
        Based on your <?= round((float)$userFields, 2) ?> acres and crops:
        <?= implode(', ', array_map('htmlspecialchars', $cropNames)) ?: 'registered fields' ?>
      </p>
    </div>
  </div>
  <div class="suggest-grid">
    <?php foreach ($suggestions as $s):
        $inCompare = in_array($s['id'], $_SESSION['compare_loans']);
    ?>
    <div class="suggest-item">
      <div class="suggest-bank"><?= $s['logo_emoji'] ?> <?= htmlspecialchars($s['prov_name']) ?></div>
      <div class="suggest-name"><?= htmlspecialchars($s['name']) ?></div>
      <div class="suggest-rate">
        <span class="rate-big"><?= $s['interest_rate'] ?>%</span>
        <span class="rate-label">/ year (<?= $s['interest_type'] ?>)</span>
      </div>
      <div class="suggest-range">৳<?= number_format($s['min_amount']) ?> – ৳<?= number_format($s['max_amount']) ?></div>
      <div class="suggest-actions">
        <button class="btn btn-primary btn-sm" onclick="openApply(<?= $s['id'] ?>, '<?= htmlspecialchars(addslashes($s['name'])) ?>', <?= $s['max_amount'] ?>)">Apply Now</button>
        <form method="post" style="display:inline">
          <input type="hidden" name="action" value="<?= $inCompare ? 'remove_compare' : 'add_compare' ?>">
          <input type="hidden" name="product_id" value="<?= $s['id'] ?>">
          <button type="submit" class="btn <?= $inCompare ? 'btn-danger' : 'btn-outline' ?> btn-sm">
            <?= $inCompare ? '✕ Remove' : '⚖️ Compare' ?>
          </button>
        </form>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>
<?php endif; ?>

<!-- ══ COMPARE BAR ══ -->
<?php if (!empty($_SESSION['compare_loans'])): ?>
<div class="compare-bar" id="compareBar">
  <div class="compare-bar-inner">
    <span class="compare-bar-title">⚖️ Comparing <?= count($_SESSION['compare_loans']) ?> loan<?= count($_SESSION['compare_loans']) > 1 ? 's' : '' ?></span>
    <div class="compare-bar-items">
      <?php foreach ($compareData as $cd): ?>
        <span class="compare-chip">
          <?= $cd['logo_emoji'] ?> <?= htmlspecialchars(substr($cd['name'], 0, 28)) ?>
          <form method="post" style="display:inline">
            <input type="hidden" name="action" value="remove_compare">
            <input type="hidden" name="product_id" value="<?= $cd['id'] ?>">
            <button type="submit" class="compare-chip-remove">×</button>
          </form>
        </span>
      <?php endforeach; ?>
    </div>
    <div style="display:flex;gap:8px;flex-shrink:0">
      <button class="btn btn-primary btn-sm" onclick="document.getElementById('compareModal').classList.add('open')">View Comparison →</button>
      <form method="post" style="display:inline">
        <input type="hidden" name="action" value="clear_compare">
        <button type="submit" class="btn btn-outline btn-sm">Clear</button>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>
<div style="display:flex;justify-content:flex-end;margin-bottom:16px;">
    <a href="my_applications.php" class="btn btn-primary">
        📄 My Applications (<?= $mySavedApps ?>)
    </a>
</div>

<!-- ══ FILTERS ══ -->
<div class="card loan-filter-card">
  <form method="get" class="loan-filter-form">
    <div class="filter-group">
      <label>🔍 Search</label>
      <input type="text" name="q" placeholder="Loan name, bank, or crop..." value="<?= htmlspecialchars($search) ?>">
    </div>
    <div class="filter-group">
      <label>📂 Category</label>
      <select name="cat">
        <option value="">All Categories</option>
        <?php foreach (['crop'=>'🌾 Crop Loan','livestock'=>'🐄 Livestock','irrigation'=>'💧 Irrigation','equipment'=>'🚜 Equipment','general'=>'📋 General','emergency'=>'🚨 Emergency'] as $val => $lbl): ?>
        <option value="<?= $val ?>" <?= $filterCat === $val ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label>🏛️ Institution Type</label>
      <select name="type">
        <option value="">All Types</option>
        <?php foreach (['bank'=>'🏦 Bank','ngo'=>'🤝 NGO','mfi'=>'💼 MFI','government'=>'🏢 Government'] as $val => $lbl): ?>
        <option value="<?= $val ?>" <?= $filterType === $val ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="filter-group">
      <label>📉 Max Interest Rate (%)</label>
      <input type="number" name="max_rate" step="0.5" min="0" max="40" placeholder="e.g. 10" value="<?= htmlspecialchars($_GET['max_rate'] ?? '') ?>">
    </div>
    <div class="filter-group filter-btns">
      <button type="submit" class="btn btn-primary">🔎 Filter</button>
      <a href="loan_hub.php" class="btn btn-outline">Reset</a>
    </div>
  </form>
</div>

<!-- ══ RESULTS COUNT ══ -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:8px">
  <div style="font-size:14px;color:var(--text3)">
    Found <strong style="color:var(--text)"><?= $products->num_rows ?></strong> loan product<?= $products->num_rows != 1 ? 's' : '' ?>
    <?php if ($search): ?> for "<em><?= htmlspecialchars($search) ?></em>"<?php endif; ?>
  </div>
  <div style="font-size:13px;color:var(--text4)">Sorted by: Interest Rate (lowest first) · Featured first</div>
</div>

<!-- ══ LOAN CARDS GRID ══ -->
<?php if ($products->num_rows === 0): ?>
<div class="empty-state">
  <div class="empty-icon">🏦</div>
  <p>No loan products match your filters.</p>
  <a href="loan_hub.php" class="btn btn-primary btn-sm">Clear Filters</a>
</div>
<?php else: ?>
<div class="loan-grid">
<?php while ($prod = $products->fetch_assoc()):
    $inCompare = in_array($prod['id'], $_SESSION['compare_loans']);

    $catColors = [
        'crop'      => ['badge-success', '🌾'],
        'livestock' => ['badge-warn', '🐄'],
        'irrigation'=> ['badge-info', '💧'],
        'equipment' => ['badge-gray', '🚜'],
        'general'   => ['badge-gray', '📋'],
        'emergency' => ['badge-danger', '🚨'],
    ];
    [$catBadge, $catIcon] = $catColors[$prod['category']] ?? ['badge-gray', '📋'];

    $typeColors = [
        'bank'       => 'badge-info',
        'ngo'        => 'badge-success',
        'mfi'        => 'badge-warn',
        'government' => 'badge-gray',
    ];
    $typeBadge = $typeColors[$prod['prov_type']] ?? 'badge-gray';

    // Rate colour
    $rate = (float)$prod['interest_rate'];
    $rateClass = $rate <= 5 ? 'rate-low' : ($rate <= 12 ? 'rate-mid' : 'rate-high');

    // Duration label
    $durLabel = $prod['duration_min_months'] === $prod['duration_max_months']
        ? $prod['duration_min_months'] . ' months'
        : $prod['duration_min_months'] . '–' . $prod['duration_max_months'] . ' months';
?>
<div class="loan-card reveal <?= $prod['is_featured'] ? 'featured' : '' ?>">

  <?php if ($prod['is_featured']): ?>
  <div class="featured-ribbon">⭐ Featured</div>
  <?php endif; ?>

  <!-- Card Header -->
  <div class="loan-card-header">
    <div class="loan-card-bank">
      <span class="bank-emoji"><?= $prod['logo_emoji'] ?></span>
      <div>
        <div class="bank-name"><?= htmlspecialchars($prod['prov_name']) ?></div>
        <span class="badge <?= $typeBadge ?>" style="font-size:10px"><?= ucfirst($prod['prov_type']) ?></span>
      </div>
    </div>
    <div class="loan-rate-badge <?= $rateClass ?>">
      <div class="lr-num"><?= $prod['interest_rate'] ?>%</div>
      <div class="lr-label"><?= $prod['interest_type'] ?></div>
    </div>
  </div>

  <!-- Product Name -->
  <div class="loan-card-name"><?= htmlspecialchars($prod['name']) ?></div>

  

  <!-- Category badge -->
  <span class="badge <?= $catBadge ?>" style="margin-bottom:12px"><?= $catIcon ?> <?= ucfirst($prod['category']) ?></span>

  <!-- Key Info -->
  <div class="loan-meta">
    <div class="lm-row">
      <span class="lm-label">💰 Loan Amount</span>
      <span class="lm-val">৳<?= number_format($prod['min_amount']) ?> – ৳<?= number_format($prod['max_amount']) ?></span>
    </div>
    <div class="lm-row">
      <span class="lm-label">📅 Duration</span>
      <span class="lm-val"><?= $durLabel ?></span>
    </div>
    <div class="lm-row">
      <span class="lm-label">💳 Repayment</span>
      <span class="lm-val"><?= ucfirst($prod['repayment_type']) ?></span>
    </div>
    <?php if ($prod['min_land_acres']): ?>
    <div class="lm-row">
      <span class="lm-label">🌾 Min. Land</span>
      <span class="lm-val"><?= $prod['min_land_acres'] ?> acres</span>
    </div>
    <?php endif; ?>
  </div>

  <!-- Documents required -->
  <div class="docs-row">
    <div class="docs-title">📄 Documents</div>
    <div class="docs-tags">
      <?php if ($prod['nid_required']): ?><span class="doc-tag">NID</span><?php endif; ?>
      <?php if ($prod['land_deed_required']): ?><span class="doc-tag">Land Deed</span><?php endif; ?>
      <?php if ($prod['photo_required']): ?><span class="doc-tag">Photo</span><?php endif; ?>
      <?php if ($prod['collateral_required']): ?><span class="doc-tag doc-tag-warn">Collateral</span><?php endif; ?>
      <?php if ($prod['guarantor_required']): ?><span class="doc-tag doc-tag-warn">Guarantor</span><?php endif; ?>
      <?php if ($prod['bank_statement_required']): ?><span class="doc-tag">Bank Statement</span><?php endif; ?>
      <?php if ($prod['farmers_card_required']): ?><span class="doc-tag doc-tag-green">Farmers Card</span><?php endif; ?>
    </div>
  </div>

  <!-- Eligible Crops -->
  <?php if ($prod['eligible_crops']): ?>
  <div style="margin-top:10px;font-size:12px;color:var(--text3)">
    <strong>Crops:</strong> <?= htmlspecialchars($prod['eligible_crops']) ?>
  </div>
  <?php endif; ?>

  <!-- Actions -->
  <div class="loan-card-actions">
    <button class="btn btn-primary btn-sm"
            onclick="openApply(<?= $prod['id'] ?>, '<?= htmlspecialchars(addslashes($prod['name'])) ?>', <?= $prod['max_amount'] ?>)">
      📋 Apply / Save
    </button>

    <button class="btn btn-outline btn-sm"
            onclick="openDetails(<?= $prod['id'] ?>)">
      ℹ️ Details
    </button>

    <form method="post" style="display:inline">
      <input type="hidden" name="action" value="<?= $inCompare ? 'remove_compare' : 'add_compare' ?>">
      <input type="hidden" name="product_id" value="<?= $prod['id'] ?>">
      <?php foreach ($_GET as $k => $v): ?>
        <input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars($v) ?>">
      <?php endforeach; ?>
      <button type="submit" class="btn <?= $inCompare ? 'btn-danger' : 'btn-ghost' ?> btn-sm"
              <?= count($_SESSION['compare_loans']) >= 4 && !$inCompare ? 'disabled title="Max 4 loans"' : '' ?>>
        <?= $inCompare ? '✕ Remove' : '⚖️ Compare' ?>
      </button>
    </form>
  </div>

  <!-- Phone link if available -->
  <?php if ($prod['prov_phone']): ?>
  <div style="margin-top:8px;text-align:center">
    <a href="tel:<?= $prod['prov_phone'] ?>" class="btn btn-ghost btn-sm" style="font-size:12px">
      📞 <?= htmlspecialchars($prod['prov_phone']) ?>
    </a>
  </div>
  <?php endif; ?>

</div>
<?php endwhile; ?>
</div>
<?php endif; ?>


<!-- ══ COMPARE MODAL ══ -->
<div class="modal-overlay" id="compareModal">
  <div class="modal-box" style="max-width:900px;width:95vw;max-height:90vh;overflow-y:auto">
    <div class="modal-header">
      <h3>⚖️ Loan Comparison</h3>
      <button class="modal-close" onclick="document.getElementById('compareModal').classList.remove('open')">×</button>
    </div>

    <?php if (count($compareData) < 2): ?>
    <p style="padding:24px;color:var(--text3);text-align:center">Add at least 2 loans to compare.</p>
    <?php else: ?>
    <div class="compare-table-wrap">
      <table class="compare-table">
        <thead>
          <tr>
            <th class="compare-row-label"></th>
            <?php foreach ($compareData as $c): ?>
            <th>
              <div><?= $c['logo_emoji'] ?> <?= htmlspecialchars($c['prov_name']) ?></div>
                          </th>
            <?php endforeach; ?>
          </tr>
        </thead>
        <tbody>
          <?php
          // Find best values for highlighting
          $rates      = array_column($compareData, 'interest_rate');
          $maxAmounts = array_column($compareData, 'max_amount');
          $minRate    = min($rates);
          $maxAmt     = max($maxAmounts);

          $rows = [
            ['label' => '📊 Interest Rate',      'key' => 'interest_rate',        'fmt' => fn($v) => $v . '%', 'best' => 'min'],
            ['label' => '📅 Duration',            'key' => null,                   'fmt' => fn($r) => $r['duration_min_months'] . '–' . $r['duration_max_months'] . ' mo', 'best' => null],
            ['label' => '💰 Max Loan Amount',     'key' => 'max_amount',           'fmt' => fn($v) => '৳' . number_format($v), 'best' => 'max'],
            ['label' => '💳 Repayment',           'key' => 'repayment_type',       'fmt' => fn($v) => ucfirst($v), 'best' => null],
            ['label' => '🏷️ Interest Type',       'key' => 'interest_type',        'fmt' => fn($v) => ucfirst($v), 'best' => null],
            ['label' => '🌾 Min. Land',           'key' => 'min_land_acres',       'fmt' => fn($v) => $v ? $v . ' acres' : 'None', 'best' => 'min'],
            ['label' => '🔒 Collateral',          'key' => 'collateral_required',  'fmt' => fn($v) => $v ? '⚠️ Yes' : '✅ No', 'best' => 'none'],
            ['label' => '👥 Guarantor',           'key' => 'guarantor_required',   'fmt' => fn($v) => $v ? '⚠️ Yes' : '✅ No', 'best' => 'none'],
            ['label' => '🪪 Farmers Card',        'key' => 'farmers_card_required','fmt' => fn($v) => $v ? '✅ Required' : 'Not needed', 'best' => null],
          ];

          foreach ($rows as $row):
          ?>
          <tr>
            <td class="compare-row-label"><?= $row['label'] ?></td>
            <?php foreach ($compareData as $idx => $c):
              if ($row['key'] === null) {
                  $display = ($row['fmt'])($c);
              } else {
                  $display = ($row['fmt'])($c[$row['key']]);
              }
              $isBest = false;
              if ($row['best'] === 'min' && $row['key']) $isBest = ($c[$row['key']] == min(array_column($compareData, $row['key'])));
              if ($row['best'] === 'max' && $row['key']) $isBest = ($c[$row['key']] == max(array_column($compareData, $row['key'])));
            ?>
            <td class="<?= $isBest ? 'best-cell' : '' ?>"><?= $display ?></td>
            <?php endforeach; ?>
          </tr>
          <?php endforeach; ?>

          <!-- Best overall recommendation -->
          <tr class="recommend-row">
            <td class="compare-row-label">🏆 Verdict</td>
            <?php
            // Simple scoring: lower rate = +3, no collateral = +2, no guarantor = +1
            $scores = [];
            foreach ($compareData as $c) {
                $score = 0;
                if ($c['interest_rate'] == min(array_column($compareData, 'interest_rate'))) $score += 3;
                if (!$c['collateral_required']) $score += 2;
                if (!$c['guarantor_required']) $score += 1;
                if ($c['max_amount'] == max(array_column($compareData, 'max_amount'))) $score += 1;
                $scores[] = $score;
            }
            $maxScore = max($scores);
            foreach ($compareData as $idx => $c):
            ?>
            <td class="<?= $scores[$idx] == $maxScore ? 'best-cell' : '' ?>">
              <?= $scores[$idx] == $maxScore ? '🏆 Best Choice' : '—' ?>
            </td>
            <?php endforeach; ?>
          </tr>

        </tbody>
      </table>
    </div>
    <div style="padding:16px;text-align:center">
      <form method="post" style="display:inline">
        <input type="hidden" name="action" value="clear_compare">
        <button type="submit" class="btn btn-outline btn-sm">Clear All</button>
      </form>
    </div>
    <?php endif; ?>
  </div>
</div>


<!-- ══ APPLY MODAL ══ -->
<div class="modal-overlay" id="applyModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3>📋 Save Application Details</h3>
      <button class="modal-close" onclick="document.getElementById('applyModal').classList.remove('open')">×</button>
    </div>
    <div style="padding:20px 24px 24px">
      <p style="font-size:13px;color:var(--text3);margin-bottom:18px">
        Fill in your requirements. This saves your interest for this loan so you can review it later.
        You'll need to visit the institution directly to formally apply.
      </p>
      <form method="post">
        <input type="hidden" name="action" value="save_application">
        <input type="hidden" name="product_id" id="applyProductId">

        <div class="form-group">
          <label>Selected Loan</label>
          <input type="text" id="applyProductName" readonly style="background:var(--surface2)">
        </div>

        <div class="form-group">
          <label>💰 Amount Needed (BDT)</label>
          <input type="number" name="amount_needed" id="applyAmount" min="1000" step="1000" required>
          <small id="applyAmountHint" style="color:var(--text3)"></small>
        </div>

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px">
          <div class="form-group">
            <label>🌾 Land Size (acres)</label>
            <input type="number" name="land_acres" step="0.01" min="0" placeholder="Optional">
          </div>
          <div class="form-group">
            <label>🌱 Main Crop</label>
            <input type="text" name="crop_type" placeholder="e.g. Rice, Potato">
          </div>
        </div>

        <div class="form-group">
          <label>📝 Purpose</label>
          <textarea name="purpose" rows="3" placeholder="Briefly describe how you'll use this loan..."></textarea>
        </div>

        <button type="submit" class="btn btn-primary btn-block">💾 Save Application</button>
      </form>
    </div>
  </div>
</div>


<!-- ══ DETAILS MODAL ══ -->
<div class="modal-overlay" id="detailsModal">
  <div class="modal-box">
    <div class="modal-header">
      <h3 id="detailsTitle">Loan Details</h3>
      <button class="modal-close" onclick="document.getElementById('detailsModal').classList.remove('open')">×</button>
    </div>
    <div id="detailsBody" style="padding:20px 24px 24px;font-size:14px;color:var(--text2)">Loading...</div>
  </div>
</div>


<!-- ══ LOAN DETAILS DATA (hidden JSON) ══ -->
<script>
const LOAN_DATA = <?php
  $allProds = $conn->query("SELECT lp.*, prov.name AS prov_name, prov.logo_emoji, prov.phone AS prov_phone, prov.website AS prov_website FROM loan_products lp JOIN loan_providers prov ON lp.provider_id=prov.id WHERE lp.is_active=1");
  $loanMap = [];
  while ($r = $allProds->fetch_assoc()) $loanMap[$r['id']] = $r;
  echo json_encode($loanMap);
?>;

function openApply(id, name, maxAmt) {
  document.getElementById('applyProductId').value   = id;
  document.getElementById('applyProductName').value = name;
  document.getElementById('applyAmount').max         = maxAmt;
  document.getElementById('applyAmountHint').textContent = 'Max: ৳' + maxAmt.toLocaleString('en-BD');
  document.getElementById('applyModal').classList.add('open');
}

function openDetails(id) {
  const d = LOAN_DATA[id];
  if (!d) return;

  document.getElementById('detailsTitle').textContent = d.logo_emoji + ' ' + d.name;

  const dur = d.duration_min_months === d.duration_max_months
    ? d.duration_min_months + ' months'
    : d.duration_min_months + '–' + d.duration_max_months + ' months';

  const docs = [
    d.nid_required            ? '✅ NID Card' : '',
    d.land_deed_required      ? '✅ Land Deed / Porcha' : '',
    d.photo_required          ? '✅ Passport Photo' : '',
    d.collateral_required     ? '⚠️ Collateral Asset' : '',
    d.guarantor_required      ? '⚠️ Guarantor' : '',
    d.bank_statement_required ? '✅ Bank Statement' : '',
    d.farmers_card_required   ? '✅ Farmers Card (Krishok Card)' : '',
    d.other_documents         ? '📎 ' + d.other_documents : '',
  ].filter(Boolean);

  document.getElementById('detailsBody').innerHTML = `
    <div style="display:grid;gap:14px">
      <div class="lm-row"><span class="lm-label">🏦 Provider</span><span class="lm-val">${d.prov_name}</span></div>
      <div class="lm-row"><span class="lm-label">📊 Interest Rate</span><span class="lm-val">${d.interest_rate}% per annum (${d.interest_type})</span></div>
      <div class="lm-row"><span class="lm-label">💰 Amount Range</span><span class="lm-val">৳${Number(d.min_amount).toLocaleString()} – ৳${Number(d.max_amount).toLocaleString()}</span></div>
      <div class="lm-row"><span class="lm-label">📅 Duration</span><span class="lm-val">${dur}</span></div>
      <div class="lm-row"><span class="lm-label">💳 Repayment</span><span class="lm-val">${d.repayment_type.charAt(0).toUpperCase() + d.repayment_type.slice(1)}</span></div>
      ${d.eligible_crops ? `<div class="lm-row"><span class="lm-label">🌾 Eligible Crops</span><span class="lm-val">${d.eligible_crops}</span></div>` : ''}
      ${d.min_land_acres ? `<div class="lm-row"><span class="lm-label">🗺️ Min. Land</span><span class="lm-val">${d.min_land_acres} acres</span></div>` : ''}
      <hr style="border:none;border-top:1px solid var(--border)">
      <div>
        <div style="font-weight:700;margin-bottom:8px;font-family:var(--font-display)">📄 Required Documents</div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">${docs.map(doc => `<span class="doc-tag">${doc}</span>`).join('')}</div>
      </div>
      ${d.eligibility_notes ? `
      <div>
        <div style="font-weight:700;margin-bottom:6px;font-family:var(--font-display)">✅ Eligibility</div>
        <p>${d.eligibility_notes}</p>
      </div>` : ''}
      ${d.description ? `
      <div>
        <div style="font-weight:700;margin-bottom:6px;font-family:var(--font-display)">ℹ️ About This Loan</div>
        <p>${d.description}</p>
      </div>` : ''}
      ${d.prov_phone || d.prov_website ? `
      <hr style="border:none;border-top:1px solid var(--border)">
      <div style="display:flex;gap:10px;flex-wrap:wrap">
        ${d.prov_phone ? `<a href="tel:${d.prov_phone}" class="btn btn-outline btn-sm">📞 ${d.prov_phone}</a>` : ''}
        ${d.prov_website ? `<a href="${d.prov_website}" target="_blank" class="btn btn-outline btn-sm">🌐 Visit Website</a>` : ''}
      </div>` : ''}
    </div>
  `;
  document.getElementById('detailsModal').classList.add('open');
}

// Close modals on overlay click
document.querySelectorAll('.modal-overlay').forEach(m => {
  m.addEventListener('click', e => {
    if (e.target === m) m.classList.remove('open');
  });
});
</script>

<style>
/* ═══════════════════════════════════════
   LOAN HUB STYLES
═══════════════════════════════════════ */

/* Hero */
.loan-hero {
  background: linear-gradient(135deg, var(--accent) 0%, var(--accent2) 55%, var(--accent3) 100%);
  border-radius: var(--radius);
  padding: 32px 36px;
  margin-bottom: 24px;
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: space-between;
  flex-wrap: wrap;
  gap: 20px;
  position: relative;
  overflow: hidden;
}
.loan-hero::before {
  content: '';
  position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%23ffffff' fill-opacity='0.05'%3E%3Ccircle cx='30' cy='30' r='20'/%3E%3C/g%3E%3C/svg%3E");
  pointer-events: none;
}
.loan-hero-badge {
  display: inline-block;
  background: rgba(255,255,255,0.2);
  border: 1px solid rgba(255,255,255,0.3);
  border-radius: 20px;
  padding: 4px 14px;
  font-size: 12px;
  font-weight: 600;
  margin-bottom: 10px;
}
.loan-hero-title {
  font-family: var(--font-display);
  font-size: 2.2rem;
  font-weight: 900;
  color: #fff;
  margin-bottom: 8px;
}
.loan-hero-sub { font-size: 14px; opacity: .85; max-width: 480px; }
.loan-hero-stats { display: flex; gap: 24px; flex-wrap: wrap; }
.lh-stat { text-align: center; }
.lh-stat-val {
  font-family: var(--font-display);
  font-size: 1.8rem;
  font-weight: 900;
  color: #fff;
  line-height: 1;
}
.lh-stat-label { font-size: 12px; opacity: .8; margin-top: 4px; }

/* Suggestions */
.loan-suggest-card { margin-bottom: 20px; background: linear-gradient(135deg, var(--accent-light), var(--surface)); }
.suggest-header { display: flex; align-items: flex-start; justify-content: space-between; margin-bottom: 16px; }
.suggest-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 14px; }
.suggest-item {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius-sm);
  padding: 16px;
}
.suggest-bank { font-size: 12px; color: var(--text3); margin-bottom: 4px; }
.suggest-name { font-family: var(--font-display); font-weight: 700; font-size: 14px; margin-bottom: 8px; }
.suggest-rate { display: flex; align-items: baseline; gap: 4px; margin-bottom: 4px; }
.rate-big { font-family: var(--font-display); font-size: 1.5rem; font-weight: 900; color: var(--accent); }
.rate-label { font-size: 12px; color: var(--text3); }
.suggest-range { font-size: 12px; color: var(--text3); margin-bottom: 12px; }
.suggest-actions { display: flex; gap: 6px; flex-wrap: wrap; }

/* Compare bar */
.compare-bar {
  position: sticky; top: var(--topbar-h);
  z-index: 100;
  background: var(--surface);
  border: 1px solid var(--accent);
  border-radius: var(--radius-sm);
  padding: 12px 16px;
  margin-bottom: 20px;
  box-shadow: var(--shadow-md);
}
.compare-bar-inner { display: flex; align-items: center; gap: 14px; flex-wrap: wrap; }
.compare-bar-title { font-weight: 700; font-size: 14px; white-space: nowrap; }
.compare-bar-items { display: flex; gap: 8px; flex-wrap: wrap; flex: 1; }
.compare-chip {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--accent-light);
  border: 1px solid var(--accent);
  border-radius: 20px;
  padding: 4px 10px;
  font-size: 12px;
  color: var(--accent2);
}
.compare-chip-remove {
  background: none; border: none;
  color: var(--text3); font-size: 14px;
  line-height: 1; padding: 0;
  transition: color var(--transition);
}
.compare-chip-remove:hover { color: var(--danger); }

/* Filter card */
.loan-filter-card { margin-bottom: 20px; }
.loan-filter-form { display: flex; flex-wrap: wrap; gap: 14px; align-items: flex-end; }
.filter-group { display: flex; flex-direction: column; gap: 6px; min-width: 160px; flex: 1; }
.filter-group label { font-size: 12px; font-weight: 600; color: var(--text3); }
.filter-btns { flex-direction: row; align-items: flex-end; min-width: auto; }

/* Loan grid */
.loan-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
  gap: 20px;
  margin-bottom: 32px;
}
.loan-card {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 20px;
  transition: box-shadow var(--transition), transform var(--transition), border-color var(--transition);
  position: relative;
  overflow: hidden;
}
.loan-card:hover { box-shadow: var(--shadow-md); transform: translateY(-2px); border-color: var(--accent); }
.loan-card.featured { border-color: var(--gold); }
.loan-card.featured:hover { border-color: var(--gold); box-shadow: 0 4px 24px rgba(212,168,32,0.18); }

.featured-ribbon {
  position: absolute; top: 0; right: 0;
  background: linear-gradient(135deg, var(--gold), #f0c030);
  color: #fff;
  font-size: 11px;
  font-weight: 700;
  padding: 4px 14px 4px 20px;
  border-bottom-left-radius: 12px;
}

.loan-card-header {
  display: flex; align-items: flex-start;
  justify-content: space-between;
  margin-bottom: 12px;
  gap: 12px;
}
.loan-card-bank { display: flex; align-items: center; gap: 10px; }
.bank-emoji { font-size: 28px; }
.bank-name { font-weight: 700; font-size: 14px; line-height: 1.2; }

/* Rate badge */
.loan-rate-badge {
  text-align: center;
  padding: 8px 14px;
  border-radius: var(--radius-sm);
  flex-shrink: 0;
}
.loan-rate-badge.rate-low { background: var(--success-light); border: 1px solid var(--success); }
.loan-rate-badge.rate-mid { background: var(--warn-light); border: 1px solid var(--warn); }
.loan-rate-badge.rate-high { background: var(--danger-light); border: 1px solid var(--danger); }
.lr-num {
  font-family: var(--font-display);
  font-size: 1.3rem;
  font-weight: 900;
  line-height: 1;
}
.rate-low .lr-num { color: var(--success); }
.rate-mid .lr-num { color: var(--warn); }
.rate-high .lr-num { color: var(--danger); }
.lr-label { font-size: 10px; color: var(--text3); margin-top: 2px; }

.loan-card-name {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 15px;
  margin-bottom: 4px;
  line-height: 1.3;
}

/* Meta rows */
.loan-meta { display: flex; flex-direction: column; gap: 6px; margin-bottom: 14px; }
.lm-row {
  display: flex; justify-content: space-between;
  align-items: center; font-size: 13px;
  padding: 5px 0;
  border-bottom: 1px solid var(--border);
}
.lm-row:last-child { border-bottom: none; }
.lm-label { color: var(--text3); }
.lm-val { font-weight: 600; color: var(--text); }

/* Documents */
.docs-row { margin-bottom: 10px; }
.docs-title { font-size: 12px; font-weight: 600; color: var(--text3); margin-bottom: 6px; }
.docs-tags { display: flex; flex-wrap: wrap; gap: 5px; }
.doc-tag {
  display: inline-block;
  background: var(--surface2);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 2px 9px;
  font-size: 11px;
  color: var(--text2);
}
.doc-tag-warn { background: var(--warn-light); border-color: var(--warn); color: var(--warn); }
.doc-tag-green { background: var(--success-light); border-color: var(--success); color: var(--success); }

.loan-card-actions { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--border); }

/* Compare table */
.compare-table-wrap { overflow-x: auto; padding: 0 16px; }
.compare-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.compare-table th, .compare-table td {
  padding: 11px 14px;
  border-bottom: 1px solid var(--border);
  text-align: center;
}
.compare-table th {
  background: var(--surface2);
  font-family: var(--font-display);
  font-size: 13px;
}
.compare-table th:first-child,
.compare-table td:first-child { text-align: left; font-weight: 600; }
.compare-row-label { color: var(--text3); white-space: nowrap; }
.best-cell { background: var(--success-light); color: var(--success); font-weight: 700; }
.recommend-row td { background: var(--accent-light); font-weight: 700; }

/* Modals */
.modal-overlay {
  position: fixed; inset: 0;
  background: rgba(0,0,0,0.5);
  z-index: 1000;
  display: flex; align-items: center; justify-content: center;
  opacity: 0; pointer-events: none;
  transition: opacity 0.25s;
  padding: 16px;
}
.modal-overlay.open { opacity: 1; pointer-events: all; }
.modal-box {
  background: var(--surface);
  border-radius: var(--radius);
  box-shadow: var(--shadow-xl);
  width: 100%; max-width: 560px;
  transform: scale(0.96);
  transition: transform 0.25s;
  overflow: hidden;
}
.modal-overlay.open .modal-box { transform: scale(1); }
.modal-header {
  display: flex; align-items: center;
  justify-content: space-between;
  padding: 18px 24px;
  border-bottom: 1px solid var(--border);
}
.modal-header h3 { font-family: var(--font-display); font-size: 1.1rem; }
.modal-close {
  background: none; border: none;
  font-size: 22px; color: var(--text3);
  line-height: 1; padding: 4px 8px;
  transition: color var(--transition);
}
.modal-close:hover { color: var(--danger); }

@media (max-width: 680px) {
  .loan-hero { padding: 24px 20px; }
  .loan-hero-title { font-size: 1.6rem; }
  .loan-filter-form { flex-direction: column; }
  .filter-group { min-width: auto; }
  .loan-grid { grid-template-columns: 1fr; }
  .compare-bar-inner { flex-direction: column; align-items: flex-start; }
}
</style>

<?php include 'layout_end.php'; ?>
