<?php
/* ============================================================
   farmers_card.php — Bangladesh Farmers Card Hub
   Requires: db.php (provides $conn, requireLogin, currentUser)
   New DB columns added automatically on first load (ALTER TABLE)
   ============================================================ */
require_once 'db.php';
requireLogin();

$pageTitle = "Farmers Card";
$activeNav = 'farmers_card';

$user   = currentUser($conn);
$uid    = (int)$user['id'];
$locId  = (int)($user['location_id'] ?? 0);

/* ── AUTO-MIGRATE: add farmers card columns if missing ─────── */
$conn->query("ALTER TABLE users
    ADD COLUMN IF NOT EXISTS fc_card_number    varchar(20)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_category       enum('landless','marginal','small','medium','large') DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_land_size      decimal(8,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_bank_account   varchar(30)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_registered_at  datetime     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_phase          enum('pre_pilot','pilot','national') DEFAULT NULL
");

/* ── RE-FETCH user with new columns ────────────────────────── */
$userRow = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();

/* ── STATS for eligibility & benefit calculation ───────────── */
// Total field area (acres)
$areaRow = $conn->query("
    SELECT COALESCE(SUM(area),0) AS total_area
    FROM fields WHERE user_id=$uid
")->fetch_assoc();
$totalArea = (float)$areaRow['total_area'];

// Growing crop count
$cropCount = (int)$conn->query("
    SELECT COUNT(*) AS c FROM farmer_crops
    WHERE user_id=$uid AND status='growing'
")->fetch_assoc()['c'];

// Pest reports count
$pestCount = (int)$conn->query("
    SELECT COUNT(*) AS c FROM pest_reports WHERE user_id=$uid
")->fetch_assoc()['c'];

// Questions asked
$qCount = (int)$conn->query("
    SELECT COUNT(*) AS c FROM questions WHERE user_id=$uid
")->fetch_assoc()['c'];

// Field count
$fieldCount = (int)$conn->query("
    SELECT COUNT(*) AS c FROM fields WHERE user_id=$uid
")->fetch_assoc()['c'];

// Harvested crops (for subsidy calc)
$harvestedCount = (int)$conn->query("
    SELECT COUNT(*) AS c FROM farmer_crops
    WHERE user_id=$uid AND status='harvested'
")->fetch_assoc()['c'];

/* ── DETERMINE CATEGORY from land size ─────────────────────── */
function getCategoryFromArea(float $area): string {
    if ($area == 0)   return 'landless';
    if ($area < 0.5)  return 'landless';
    if ($area < 2.5)  return 'marginal';
    if ($area < 7.5)  return 'small';
    if ($area < 25.0) return 'medium';
    return 'large';
}

$autoCategory = getCategoryFromArea($totalArea);
$savedCategory = $userRow['fc_category'] ?? null;
$cardNumber    = $userRow['fc_card_number'] ?? '';
$bankAccount   = $userRow['fc_bank_account'] ?? '';
$fcPhase       = $userRow['fc_phase'] ?? '';
$fcLandSize    = (float)($userRow['fc_land_size'] ?? $totalArea);
$isRegistered  = !empty($cardNumber);

/* ── SUBSIDY CALCULATION (in Taka) ─────────────────────────── */
// Based on government scheme rates (approximate)
$directCash        = 2500;   // Tk per year — official amount
$fertilizerSubsidy = round($totalArea * 1200);  // ~Tk 1200/acre
$seedSubsidy       = round($cropCount * 450);   // ~Tk 450/crop
$irrigationSubsidy = round($totalArea * 800);   // ~Tk 800/acre
$totalSubsidy      = $directCash + $fertilizerSubsidy + $seedSubsidy + $irrigationSubsidy;

/* ── PHASE DETERMINATION ────────────────────────────────────── */
// Pre-pilot upazilas (official list from government announcement)
$prePilotDistricts = [
    'Tangail','Bogura','Panchagarh','Jamalpur',
    'Jhenaidah','Pirojpur','Moulvibazar','Comilla',
    'Rajbari','Cox\'s Bazar'
];

$userDistrict = '';
if ($locId) {
    $locRow = $conn->query("SELECT district FROM locations WHERE id=$locId")->fetch_assoc();
    $userDistrict = $locRow['district'] ?? '';
}

$inPrePilot = in_array($userDistrict, $prePilotDistricts);
$suggestedPhase = $inPrePilot ? 'pre_pilot' : 'pilot';

/* ── HANDLE FORM SAVE ───────────────────────────────────────── */
$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_card'])) {

    $newCardNum  = trim($conn->real_escape_string($_POST['card_number'] ?? ''));
    $newCategory = $conn->real_escape_string($_POST['category'] ?? $autoCategory);
    $newLand     = (float)($_POST['land_size'] ?? $totalArea);
    $newBank     = trim($conn->real_escape_string($_POST['bank_account'] ?? ''));
    $newPhase    = $conn->real_escape_string($_POST['phase'] ?? $suggestedPhase);

    // Basic validation
    if (empty($newCardNum)) {
        $errorMsg = 'Please enter your Farmers Card number.';
    } elseif (!preg_match('/^[A-Za-z0-9\-]{6,20}$/', $newCardNum)) {
        $errorMsg = 'Card number should be 6–20 characters (letters, numbers, hyphens only).';
    } else {
        $regTime = $isRegistered
            ? "'" . $conn->real_escape_string($userRow['fc_registered_at']) . "'"
            : 'NOW()';

        $conn->query("
            UPDATE users SET
                fc_card_number   = '$newCardNum',
                fc_category      = '$newCategory',
                fc_land_size     = $newLand,
                fc_bank_account  = '$newBank',
                fc_phase         = '$newPhase',
                fc_registered_at = $regTime
            WHERE id = $uid
        ");

        // Notification
        $conn->query("
            INSERT INTO notifications (user_id, title, message, type, is_read)
            VALUES ($uid,
                'Farmers Card Linked',
                'Your Bangladesh Farmers Card has been linked to SoilSync. You can now track all 10 government benefits.',
                'advisory', 0)
        ");

        $successMsg  = 'Your Farmers Card has been linked successfully! All benefits are now active.';
        $cardNumber  = $newCardNum;
        $savedCategory = $newCategory;
        $fcPhase     = $newPhase;
        $bankAccount = $newBank;
        $fcLandSize  = $newLand;
        $isRegistered = true;
    }
}

/* ── 10 BENEFITS DATA ───────────────────────────────────────── */
// status: 'active' | 'partial' | 'pending' | 'external'
$benefits = [
    [
        'num'     => 1,
        'icon'    => '🌾',
        'title'   => 'Agricultural inputs at fair prices',
        'desc'    => 'Subsidised fertiliser and certified seeds at government-controlled prices, cutting out middlemen.',
        'status'  => $fieldCount > 0 ? 'active' : 'partial',
        'soilsync'=> 'SoilSync Seed Finder shows govt-price seeds for your soil type.',
        'link'    => 'seeds.php',
        'link_label' => 'Open Seed Finder',
        'how'     => 'Show your Farmers Card at your nearest DAE office or agri-input dealer.',
    ],
    [
        'num'     => 2,
        'icon'    => '🏦',
        'title'   => 'Agricultural loans on easy terms',
        'desc'    => 'Low-interest loans through Sonali Bank linked to your card — no collateral needed for marginal farmers.',
        'status'  => $isRegistered ? 'active' : 'pending',
        'soilsync'=> 'SoilSync crop records can serve as proof of farming activity for your loan application.',
        'link'    => 'loan_hub.php',
        'link_label' => 'View Loan Hub',
        'how'     => 'Visit your nearest Sonali Bank branch with your Farmers Card and NID.',
    ],
    [
        'num'     => 3,
        'icon'    => '🛡️',
        'title'   => 'Crop insurance access',
        'desc'    => 'Government-backed crop insurance against flood, drought, and pest outbreak losses.',
        'status'  => $cropCount > 0 ? 'active' : 'partial',
        'soilsync'=> 'SoilSync pest reports and crop records document losses — essential for insurance claims.',
        'link'    => 'pest_report.php',
        'link_label' => 'File pest report',
        'how'     => 'Register your growing crops with the local DAE office to activate insurance coverage.',
    ],
    [
        'num'     => 4,
        'icon'    => '💧',
        'title'   => 'Irrigation facilities at fair prices',
        'desc'    => 'Subsidised irrigation water rates — up to 50% below market price for card holders.',
        'status'  => 'active',
        'soilsync'=> 'SoilSync Irrigation log tracks your water usage and recommends optimal irrigation timing.',
        'link'    => 'irrigation.php',
        'link_label' => 'Open irrigation log',
        'how'     => 'Show your card at the local irrigation water point or BADC pump station.',
    ],
    [
        'num'     => 5,
        'icon'    => '🚜',
        'title'   => 'Agricultural machinery at affordable rates',
        'desc'    => 'Access to tractors, harvesters, and other machinery at subsidised hire rates.',
        'status'  => 'external',
        'soilsync'=> 'Coming soon: SoilSync machinery request board to find equipment near your field.',
        'link'    => null,
        'link_label' => null,
        'how'     => 'Contact your local BADC office or DAE upazila office with your card.',
    ],
    [
        'num'     => 6,
        'icon'    => '💰',
        'title'   => 'Fair price for selling produce',
        'desc'    => 'Access to government-monitored fair price markets and buying stations — no middlemen.',
        'status'  => 'active',
        'soilsync'=> 'SoilSync Market Prices page tracks live crop prices so you know when to sell.',
        'link'    => 'market.php',
        'link_label' => 'Check market prices',
        'how'     => 'Take your produce to government fair-price centres in your upazila.',
    ],
    [
        'num'     => 7,
        'icon'    => '📚',
        'title'   => 'Agricultural training access',
        'desc'    => 'Free training sessions on modern farming, pest control, and climate-smart agriculture.',
        'status'  => $isRegistered ? 'active' : 'pending',
        'soilsync'=> 'SoilSync advisories and expert Q&A supplement formal training anytime, anywhere.',
        'link'    => 'faq.php',
        'link_label' => 'Ask an expert',
        'how'     => 'Your local DAE office will notify card holders of upcoming training dates.',
    ],
    [
        'num'     => 8,
        'icon'    => '🌦️',
        'title'   => 'Weather & market info via digital platforms',
        'desc'    => 'Real-time weather forecasts and market price information through government digital channels.',
        'status'  => 'active',
        'soilsync'=> '✅ SoilSync already delivers this benefit — live weather data for your exact district.',
        'link'    => 'dashboard.php',
        'link_label' => 'View weather data',
        'how'     => 'SoilSync is your digital platform for this benefit. No extra steps needed.',
    ],
    [
        'num'     => 9,
        'icon'    => '🦠',
        'title'   => 'Crop disease & pest control guidance',
        'desc'    => 'Expert advice on identifying and treating crop diseases and pest outbreaks.',
        'status'  => 'active',
        'soilsync'=> '✅ SoilSync already delivers this — disease library, pest reports, and expert answers.',
        'link'    => 'disease.php',
        'link_label' => 'Check diseases',
        'how'     => 'SoilSync is your digital platform for this benefit. Use the disease checker now.',
    ],
    [
        'num'     => 10,
        'icon'    => '💸',
        'title'   => 'Direct cash subsidy (Tk 2,500/year)',
        'desc'    => 'Annual cash transfer of Tk 2,500 directly to your Sonali Bank account linked to the card.',
        'status'  => $isRegistered && !empty($bankAccount) ? 'active' : 'pending',
        'soilsync'=> 'SoilSync notifies you when your subsidy cycle is due for renewal.',
        'link'    => null,
        'link_label' => null,
        'how'     => 'Funds are transferred automatically once your card is registered and bank account linked.',
    ],
];

/* count active benefits */
$activeCount  = count(array_filter($benefits, fn($b) => $b['status'] === 'active'));
$partialCount = count(array_filter($benefits, fn($b) => $b['status'] === 'partial'));
$pendingCount = count(array_filter($benefits, fn($b) => $b['status'] === 'pending' || $b['status'] === 'external'));

$categoryLabels = [
    'landless' => 'Landless Farmer',
    'marginal' => 'Marginal Farmer',
    'small'    => 'Small Farmer',
    'medium'   => 'Medium Farmer',
    'large'    => 'Large Farmer',
];
$categoryBadge = [
    'landless' => 'badge-danger',
    'marginal' => 'badge-warn',
    'small'    => 'badge-success',
    'medium'   => 'badge-info',
    'large'    => 'badge-neutral',
];
$phaseLabels = [
    'pre_pilot' => 'Pre-Pilot (Active now)',
    'pilot'     => 'Pilot Phase (Aug 2026)',
    'national'  => 'Nationwide Rollout',
];

include 'layout.php';
?>

<!-- ═══════════════════════════════════════════════════════════
     HERO BANNER
════════════════════════════════════════════════════════════ -->
<div style="
    background: linear-gradient(135deg, #1a6090 0%, #1a7a38 100%);
    border-radius: var(--radius);
    padding: 28px 32px;
    margin-bottom: 24px;
    color: #fff;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 16px;
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 32px rgba(26,96,144,0.25);
">
    <div style="position:absolute;right:-10px;top:-20px;font-size:140px;opacity:0.07">🃏</div>

    <div>
        <div style="font-size:12px;font-family:var(--font-mono);letter-spacing:2px;
                    color:rgba(255,255,255,0.7);text-transform:uppercase;margin-bottom:6px">
            Government of Bangladesh · Ministry of Agriculture
        </div>
        <h2 style="font-family:var(--font-display);font-size:1.8rem;font-weight:900;
                   color:#fff;margin-bottom:6px;line-height:1.15">
            🪪 Farmers Card Hub
        </h2>
        <div style="font-size:13px;color:rgba(255,255,255,0.8)">
            Launched Pahela Baishakh, 14 April 2026 &nbsp;·&nbsp;
            Visa + Sonali Bank &nbsp;·&nbsp;
            2.75 crore farmers nationwide
        </div>
    </div>

    <?php if ($isRegistered): ?>
    <div style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);
                border-radius:var(--radius-sm);padding:16px 22px;text-align:center;
                backdrop-filter:blur(8px);min-width:170px">
        <div style="font-size:10px;font-family:var(--font-mono);color:rgba(255,255,255,0.7);
                    letter-spacing:1.5px;text-transform:uppercase;margin-bottom:4px">Card Number</div>
        <div style="font-family:var(--font-mono);font-size:16px;font-weight:700;
                    color:#fff;letter-spacing:2px"><?= htmlspecialchars($cardNumber) ?></div>
        <div style="margin-top:8px">
            <span style="background:rgba(255,255,255,0.2);color:#fff;border-radius:20px;
                         padding:3px 10px;font-size:11px;font-weight:600;font-family:var(--font-mono)">
                ✅ REGISTERED
            </span>
        </div>
    </div>
    <?php else: ?>
    <div style="background:rgba(255,255,255,0.15);border:1px solid rgba(255,255,255,0.3);
                border-radius:var(--radius-sm);padding:16px 22px;text-align:center;
                backdrop-filter:blur(8px)">
        <div style="font-size:28px;margin-bottom:4px">🪪</div>
        <div style="font-size:12px;color:rgba(255,255,255,0.8)">Not yet linked</div>
        <div style="font-size:11px;color:rgba(255,255,255,0.6);margin-top:4px">
            Fill the form below
        </div>
    </div>
    <?php endif; ?>
</div>


<!-- ═══════════════════════════════════════════════════════════
     ALERTS
════════════════════════════════════════════════════════════ -->
<?php if ($successMsg): ?>
<div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>

<?php if ($errorMsg): ?>
<div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>


<!-- ═══════════════════════════════════════════════════════════
     STATS ROW
════════════════════════════════════════════════════════════ -->
<div class="stats-grid" style="margin-bottom:24px">

    <div class="stat-card" style="border-top:3px solid var(--success)">
        <div style="font-size:28px;margin-bottom:6px">✅</div>
        <div class="stat-val" style="font-size:26px;color:var(--success)"><?= $activeCount ?></div>
        <div class="stat-label">Benefits Active</div>
    </div>

    <div class="stat-card" style="border-top:3px solid var(--warn)">
        <div style="font-size:28px;margin-bottom:6px">⚡</div>
        <div class="stat-val" style="font-size:26px;color:var(--warn)"><?= $partialCount ?></div>
        <div class="stat-label">Partially Active</div>
    </div>

    <div class="stat-card" style="border-top:3px solid var(--info)">
        <div style="font-size:28px;margin-bottom:6px">⏳</div>
        <div class="stat-val" style="font-size:26px;color:var(--info)"><?= $pendingCount ?></div>
        <div class="stat-label">Pending / External</div>
    </div>

    <div class="stat-card" style="border-top:3px solid var(--gold)">
        <div style="font-size:28px;margin-bottom:6px">💰</div>
        <div class="stat-val" style="font-size:26px;color:var(--gold)">
            ৳<?= number_format($totalSubsidy) ?>
        </div>
        <div class="stat-label">Est. Annual Benefit</div>
    </div>

</div>


<!-- ═══════════════════════════════════════════════════════════
     MAIN GRID: Card Form + Benefit Progress
════════════════════════════════════════════════════════════ -->
<div class="grid-2" style="margin-bottom:24px">


    <!-- ─── LEFT: CARD REGISTRATION FORM ─────────────────── -->
    <div class="card">
        <div class="card-title" style="margin-bottom:4px">
            🪪 <?= $isRegistered ? 'Your Card Details' : 'Link Your Farmers Card' ?>
        </div>
        <div class="card-subtitle" style="margin-bottom:20px">
            <?= $isRegistered
                ? 'Registered ' . date('d M Y', strtotime($userRow['fc_registered_at']))
                : 'Enter your government-issued card details below' ?>
        </div>

        <form method="POST">
            <input type="hidden" name="save_card" value="1">

            <!-- Card number -->
            <div class="form-group">
                <label>Farmers Card Number *</label>
                <input type="text"
                       name="card_number"
                       value="<?= htmlspecialchars($cardNumber) ?>"
                       placeholder="e.g. FC-2026-012345"
                       maxlength="20"
                       required>
                <div style="font-size:11px;color:var(--text3);margin-top:5px;font-family:var(--font-mono)">
                    Found on the front of your Visa-powered Sonali Bank card
                </div>
            </div>

            <!-- Category -->
            <div class="form-group">
                <label>Farmer Category</label>
                <select name="category">
                    <?php foreach ($categoryLabels as $val => $label):
                        $sel = ($savedCategory ?? $autoCategory) === $val ? 'selected' : '';
                    ?>
                        <option value="<?= $val ?>" <?= $sel ?>>
                            <?= $label ?>
                            <?= $val === $autoCategory ? '(auto-detected)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div style="font-size:11px;color:var(--text3);margin-top:5px">
                    Auto-detected from your <?= number_format($totalArea, 2) ?> acres of registered fields
                </div>
            </div>

            <!-- Land size -->
            <div class="form-group">
                <label>Total Land Size (acres)</label>
                <input type="number"
                       name="land_size"
                       value="<?= $fcLandSize ?: $totalArea ?>"
                       step="0.01" min="0" max="500"
                       placeholder="0.00">
            </div>

            <!-- Sonali Bank account -->
            <div class="form-group">
                <label>Sonali Bank Account Number</label>
                <input type="text"
                       name="bank_account"
                       value="<?= htmlspecialchars($bankAccount) ?>"
                       placeholder="e.g. 2017110009845"
                       maxlength="30">
                <div style="font-size:11px;color:var(--text3);margin-top:5px">
                    Required for Tk 2,500 direct cash transfer (benefit #10)
                </div>
            </div>

            <!-- Phase -->
            <div class="form-group">
                <label>Rollout Phase</label>
                <select name="phase">
                    <?php foreach ($phaseLabels as $val => $label):
                        $sel = ($fcPhase ?: $suggestedPhase) === $val ? 'selected' : '';
                    ?>
                        <option value="<?= $val ?>" <?= $sel ?>>
                            <?= $label ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($inPrePilot): ?>
                <div style="font-size:11px;color:var(--success);margin-top:5px;font-weight:600">
                    ✅ <?= htmlspecialchars($userDistrict) ?> is in the pre-pilot phase — you may already be eligible!
                </div>
                <?php else: ?>
                <div style="font-size:11px;color:var(--text3);margin-top:5px">
                    Your district joins the pilot phase (August 2026) or national rollout.
                </div>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn btn-primary btn-block" style="margin-top:8px">
                🪪 <?= $isRegistered ? 'Update Card Details' : 'Link Farmers Card' ?>
            </button>
        </form>

        <?php if (!$isRegistered): ?>
        <div style="margin-top:16px;padding:12px 14px;background:var(--info-light);
                    border:1px solid var(--info);border-radius:var(--radius-sm);
                    font-size:12.5px;color:var(--info);line-height:1.6">
            <strong>Don't have a card yet?</strong><br>
            Visit your local DAE (Department of Agricultural Extension) upazila office with your NID and land documents.
            The card is free to obtain.
        </div>
        <?php endif; ?>
    </div>


    <!-- ─── RIGHT: PROFILE SUMMARY + SUBSIDY BREAKDOWN ────── -->
    <div>

        <!-- Profile card -->
        <?php if ($isRegistered): ?>
        <div class="card" style="margin-bottom:16px;border-left:4px solid var(--accent)">
            <div style="display:flex;align-items:center;gap:14px;margin-bottom:16px">
                <div style="width:52px;height:52px;border-radius:50%;
                            background:linear-gradient(135deg,var(--accent),var(--accent2));
                            display:flex;align-items:center;justify-content:center;
                            font-size:22px;flex-shrink:0">
                    👨‍🌾
                </div>
                <div>
                    <div style="font-family:var(--font-display);font-size:17px;font-weight:700;color:var(--text)">
                        <?= htmlspecialchars($user['name']) ?>
                    </div>
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:4px">
                        <span class="badge <?= $categoryBadge[$savedCategory ?? $autoCategory] ?>">
                            <?= $categoryLabels[$savedCategory ?? $autoCategory] ?>
                        </span>
                        <span class="badge badge-info">
                            <?= $phaseLabels[$fcPhase] ?? 'Phase TBD' ?>
                        </span>
                    </div>
                </div>
            </div>

            <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px">
                <?php
                $infoItems = [
                    ['📋', 'Card No', htmlspecialchars($cardNumber)],
                    ['🏦', 'Bank A/C', $bankAccount ? '••••' . substr($bankAccount,-4) : 'Not set'],
                    ['📐', 'Land size', ($fcLandSize ?: $totalArea) . ' acres'],
                    ['📍', 'District', htmlspecialchars($userDistrict ?: 'Not set')],
                    ['🌾', 'Active crops', $cropCount . ' crops'],
                    ['🗺️', 'Fields', $fieldCount . ' fields'],
                ];
                foreach ($infoItems as [$ic,$lb,$vl]):
                ?>
                <div style="background:var(--surface2);border-radius:var(--radius-xs);
                            padding:10px 12px;">
                    <div style="font-size:10px;font-family:var(--font-mono);color:var(--text3);
                                letter-spacing:0.5px;text-transform:uppercase;margin-bottom:2px">
                        <?= $ic ?> <?= $lb ?>
                    </div>
                    <div style="font-size:13px;font-weight:600;color:var(--text)">
                        <?= $vl ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Subsidy breakdown -->
        <div class="card" style="border-left:4px solid var(--gold)">
            <div class="card-title" style="margin-bottom:16px">
                💰 Estimated Annual Benefits (৳)
            </div>

            <?php
            $subsidyRows = [
                ['💸', 'Direct cash transfer', $directCash,        'badge-gold'],
                ['🌿', 'Fertiliser subsidy',   $fertilizerSubsidy, 'badge-success'],
                ['🌱', 'Seed subsidy',          $seedSubsidy,       'badge-success'],
                ['💧', 'Irrigation subsidy',    $irrigationSubsidy, 'badge-info'],
            ];
            foreach ($subsidyRows as [$ic, $lb, $amt, $bc]):
            ?>
            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:10px 0;border-bottom:1px solid var(--border)">
                <div style="font-size:13px;color:var(--text2)"><?= $ic ?> <?= $lb ?></div>
                <span class="badge <?= $bc ?>">৳<?= number_format($amt) ?></span>
            </div>
            <?php endforeach; ?>

            <div style="display:flex;align-items:center;justify-content:space-between;
                        padding:14px 0 4px;margin-top:4px">
                <div style="font-family:var(--font-display);font-weight:700;
                            font-size:15px;color:var(--text)">Total estimated benefit</div>
                <div style="font-family:var(--font-display);font-size:22px;
                            font-weight:900;color:var(--gold)">
                    ৳<?= number_format($totalSubsidy) ?>
                </div>
            </div>
            <div style="font-size:11px;color:var(--text3);font-family:var(--font-mono)">
                Based on your <?= number_format($totalArea,2) ?> acres &amp;
                <?= $cropCount ?> active crops. Government rates, approximate.
            </div>
        </div>

    </div><!-- /right col -->

</div><!-- /grid-2 -->


<!-- ═══════════════════════════════════════════════════════════
     ALL 10 BENEFITS
════════════════════════════════════════════════════════════ -->
<div class="card reveal">

    <div class="card-header" style="margin-bottom:20px">
        <div>
            <div class="card-title">🎁 All 10 Government Benefits</div>
            <div class="card-subtitle">
                <?= $activeCount ?> active &nbsp;·&nbsp;
                <?= $partialCount ?> partial &nbsp;·&nbsp;
                <?= $pendingCount ?> pending/external
            </div>
        </div>
        <!-- Progress bar across all benefits -->
        <div style="text-align:right;min-width:120px">
            <div style="font-size:11px;color:var(--text3);margin-bottom:4px;font-family:var(--font-mono)">
                <?= $activeCount ?>/10 unlocked
            </div>
            <div class="progress-track" style="width:120px">
                <div class="progress-fill" style="width:<?= ($activeCount/10)*100 ?>%"></div>
            </div>
        </div>
    </div>

    <div style="display:flex;flex-direction:column;gap:0">
    <?php foreach ($benefits as $i => $b):

        $statusCfg = match($b['status']) {
            'active'   => ['color'=>'var(--success)', 'bg'=>'var(--success-light)',
                           'border'=>'var(--success)', 'label'=>'Active', 'dot'=>'✅'],
            'partial'  => ['color'=>'var(--warn)',    'bg'=>'var(--warn-light)',
                           'border'=>'var(--warn)',    'label'=>'Partial', 'dot'=>'⚡'],
            'pending'  => ['color'=>'var(--info)',    'bg'=>'var(--info-light)',
                           'border'=>'var(--info)',    'label'=>'Pending', 'dot'=>'⏳'],
            'external' => ['color'=>'var(--text3)',   'bg'=>'var(--surface2)',
                           'border'=>'var(--border)',  'label'=>'External', 'dot'=>'🔗'],
            default    => ['color'=>'var(--text3)',   'bg'=>'var(--surface2)',
                           'border'=>'var(--border)',  'label'=>'Unknown', 'dot'=>'❓'],
        };

    ?>
    <div style="
        display:flex;align-items:flex-start;gap:16px;
        padding:16px 0;
        border-bottom:1px solid var(--border);
        <?= $i === count($benefits)-1 ? 'border-bottom:none' : '' ?>
    ">
        <!-- Number badge -->
        <div style="
            width:36px;height:36px;border-radius:50%;flex-shrink:0;
            background:<?= $statusCfg['bg'] ?>;
            border:1.5px solid <?= $statusCfg['border'] ?>;
            display:flex;align-items:center;justify-content:center;
            font-family:var(--font-mono);font-size:12px;font-weight:700;
            color:<?= $statusCfg['color'] ?>;
        ">
            <?= $b['num'] ?>
        </div>

        <!-- Content -->
        <div style="flex:1;min-width:0">
            <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:4px">
                <span style="font-size:18px"><?= $b['icon'] ?></span>
                <span style="font-family:var(--font-display);font-size:15px;
                             font-weight:700;color:var(--text)">
                    <?= htmlspecialchars($b['title']) ?>
                </span>
                <span style="
                    font-size:10px;font-weight:700;font-family:var(--font-mono);
                    padding:2px 8px;border-radius:20px;
                    background:<?= $statusCfg['bg'] ?>;
                    color:<?= $statusCfg['color'] ?>;
                    border:1px solid <?= $statusCfg['border'] ?>;
                ">
                    <?= $statusCfg['dot'] ?> <?= $statusCfg['label'] ?>
                </span>
            </div>

            <div style="font-size:13px;color:var(--text2);margin-bottom:8px;line-height:1.55">
                <?= htmlspecialchars($b['desc']) ?>
            </div>

            <!-- SoilSync integration note -->
            <div style="
                background:var(--accent-light);border:1px solid var(--border2);
                border-left:3px solid var(--accent);border-radius:var(--radius-xs);
                padding:8px 12px;font-size:12.5px;color:var(--text2);
                margin-bottom:8px;line-height:1.5;
            ">
                <strong style="color:var(--accent);font-size:11px;
                               font-family:var(--font-mono);text-transform:uppercase;
                               letter-spacing:0.5px">
                    🌱 SoilSync
                </strong><br>
                <?= htmlspecialchars($b['soilsync']) ?>
            </div>

            <!-- How to claim + link -->
            <div style="display:flex;align-items:flex-start;gap:12px;flex-wrap:wrap">
                <div style="font-size:12px;color:var(--text3);flex:1;min-width:180px">
                    <strong style="color:var(--text2)">How to claim:</strong>
                    <?= htmlspecialchars($b['how']) ?>
                </div>
                <?php if ($b['link']): ?>
                <a href="<?= $b['link'] ?>" class="btn btn-outline btn-sm" style="flex-shrink:0">
                    <?= $b['icon'] ?> <?= htmlspecialchars($b['link_label']) ?> →
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    </div>

</div><!-- /10 benefits card -->


<!-- ═══════════════════════════════════════════════════════════
     IMPORTANT INFO FOOTER
════════════════════════════════════════════════════════════ -->
<div class="grid-2" style="margin-top:20px">

    <div class="card" style="border-left:4px solid var(--info)">
        <div class="card-title" style="margin-bottom:12px">📍 Pre-Pilot Upazila Coverage</div>
        <div style="font-size:13px;color:var(--text2);margin-bottom:10px">
            The scheme currently covers 11 upazilas across 10 districts.
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:6px">
            <?php
            $locations = [
                'Tangail Sadar','Shibganj (Bogura)','Panchagarh Sadar',
                'Boda (Panchagarh)','Islampur (Jamalpur)','Shailkupa (Jhenaidah)',
                'Nesarabad (Pirojpur)','Juri (Moulvibazar)','Cumilla Sadar',
                'Goalanda (Rajbari)','Teknaf (Cox\'s Bazar)',
            ];
            foreach ($locations as $loc):
                $isUser = (str_contains($loc, $userDistrict) && $userDistrict);
            ?>
            <span class="badge <?= $isUser ? 'badge-success' : 'badge-neutral' ?>">
                <?= $isUser ? '📍 ' : '' ?><?= htmlspecialchars($loc) ?>
            </span>
            <?php endforeach; ?>
        </div>
        <?php if ($inPrePilot): ?>
        <div style="margin-top:12px;padding:10px 12px;background:var(--success-light);
                    border-radius:var(--radius-xs);font-size:13px;color:var(--success);font-weight:600">
            ✅ Your district is in the pre-pilot! Visit your local DAE office now.
        </div>
        <?php endif; ?>
    </div>

    <div class="card" style="border-left:4px solid var(--gold)">
        <div class="card-title" style="margin-bottom:12px">📋 Documents to Bring</div>
        <div style="display:flex;flex-direction:column;gap:8px">
            <?php
            $docs = [
                ['✅','National ID Card (NID)','Required for all categories'],
                ['✅','Land ownership documents','Or tenant farming agreement'],
                ['✅','Bank account details','Sonali Bank preferred for direct transfer'],
                ['⚡','Upazila DAE registration','May be required in some areas'],
                ['⚡','Passport-size photo','2 copies recommended'],
            ];
            foreach ($docs as [$ic,$title,$note]):
            ?>
            <div style="display:flex;align-items:flex-start;gap:10px">
                <span style="font-size:16px;flex-shrink:0"><?= $ic ?></span>
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--text)"><?= $title ?></div>
                    <div style="font-size:11px;color:var(--text3)"><?= $note ?></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

</div>


<!-- QUICK ACTIONS -->
<div class="card" style="margin-top:20px">
    <div class="card-title" style="margin-bottom:14px">⚡ Quick Actions</div>
    <div style="display:flex;flex-wrap:wrap;gap:10px">
        <a href="seeds.php"      class="btn btn-outline">🌱 Seed Finder (Benefit #1)</a>
        <a href="loan_hub.php"      class="btn btn-outline">🏦 Loan Hub (Benefit #2)</a>
        <a href="irrigation.php" class="btn btn-outline">💧 Irrigation Log (Benefit #4)</a>
        <a href="market.php"     class="btn btn-outline">💰 Market Prices (Benefit #6)</a>
        <a href="faq.php"        class="btn btn-outline">📚 Ask Expert (Benefit #7)</a>
        <a href="disease.php"    class="btn btn-outline">🦠 Disease Check (Benefit #9)</a>
        <a href="dashboard.php"  class="btn btn-outline">🌦️ Weather (Benefit #8)</a>
        <?php if ($isRegistered): ?>
        <a href="farmers_card_print.php" class="btn btn-primary">🖨️ Print My Card</a>
        <?php endif; ?>
    </div>
</div>

<?php include 'farmers_card_extras.php'; ?>

<?php include 'layout_end.php'; ?>