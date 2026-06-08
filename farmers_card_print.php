<?php
/* ============================================================
   farmers_card_print.php — Printable Farmers Card Summary
   SoilSync · Bangladesh Farmers Card Hub
   ============================================================ */
require_once 'db.php';
requireLogin();

$user  = currentUser($conn);
$uid   = (int)$user['id'];
$locId = (int)($user['location_id'] ?? 0);

/* ── Ensure columns exist ── */
$conn->query("ALTER TABLE users
    ADD COLUMN IF NOT EXISTS fc_card_number   varchar(20)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_category      enum('landless','marginal','small','medium','large') DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_land_size     decimal(8,2) DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_bank_account  varchar(30)  DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_registered_at datetime     DEFAULT NULL,
    ADD COLUMN IF NOT EXISTS fc_phase         enum('pre_pilot','pilot','national') DEFAULT NULL
");

$userRow    = $conn->query("SELECT * FROM users WHERE id=$uid")->fetch_assoc();
$cardNumber = $userRow['fc_card_number'] ?? '';
$isRegistered = !empty($cardNumber);

/* If not registered, redirect back */
if (!$isRegistered) {
    header('Location: farmers_card.php');
    exit;
}

/* ── Stats ── */
$areaRow   = $conn->query("SELECT COALESCE(SUM(area),0) AS a FROM fields WHERE user_id=$uid")->fetch_assoc();
$totalArea = (float)$areaRow['a'];

$cropCount      = (int)$conn->query("SELECT COUNT(*) AS c FROM farmer_crops WHERE user_id=$uid AND status='growing'")->fetch_assoc()['c'];
$harvestedCount = (int)$conn->query("SELECT COUNT(*) AS c FROM farmer_crops WHERE user_id=$uid AND status='harvested'")->fetch_assoc()['c'];
$fieldCount     = (int)$conn->query("SELECT COUNT(*) AS c FROM fields WHERE user_id=$uid")->fetch_assoc()['c'];
$pestCount      = (int)$conn->query("SELECT COUNT(*) AS c FROM pest_reports WHERE user_id=$uid")->fetch_assoc()['c'];

/* ── Subsidy calc ── */
$directCash        = 2500;
$fertilizerSubsidy = round($totalArea * 1200);
$seedSubsidy       = round($cropCount * 450);
$irrigationSubsidy = round($totalArea * 800);
$totalSubsidy      = $directCash + $fertilizerSubsidy + $seedSubsidy + $irrigationSubsidy;

/* ── Category label ── */
$categoryLabels = [
    'landless' => 'Landless Farmer',
    'marginal' => 'Marginal Farmer',
    'small'    => 'Small Farmer',
    'medium'   => 'Medium Farmer',
    'large'    => 'Large Farmer',
];
$phaseLabels = [
    'pre_pilot' => 'Pre-Pilot (Active)',
    'pilot'     => 'Pilot Phase',
    'national'  => 'Nationwide',
];

$category  = $userRow['fc_category'] ?? 'marginal';
$phase     = $userRow['fc_phase']    ?? 'pilot';
$bankAcc   = $userRow['fc_bank_account'] ?? '—';
$landSize  = (float)($userRow['fc_land_size'] ?? $totalArea);
$regDate   = $userRow['fc_registered_at'] ? date('d F Y', strtotime($userRow['fc_registered_at'])) : date('d F Y');

/* ── Active benefits for checklist ── */
$benefits = [
    ['Agricultural Inputs at Fair Price',     $fieldCount > 0],
    ['Agricultural Loan on Easy Terms',       $isRegistered],
    ['Crop Insurance Access',                 $cropCount > 0],
    ['Irrigation at Fair Price',              true],
    ['Agricultural Machinery',               false],
    ['Fair Price for Produce',                true],
    ['Agricultural Training Access',          $isRegistered],
    ['Weather & Market Info (Digital)',       true],
    ['Crop Disease & Pest Guidance',          true],
    ['Direct Cash Subsidy (Tk 2,500/year)',   $isRegistered && !empty($userRow['fc_bank_account'])],
];
$activeCount = count(array_filter($benefits, fn($b) => $b[1]));

/* ── Location ── */
$locRow = $locId ? $conn->query("SELECT * FROM locations WHERE id=$locId")->fetch_assoc() : null;
$district = $locRow['district'] ?? ($user['district'] ?? '—');
$division = $locRow['division'] ?? ($user['division'] ?? '—');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Farmers Card — <?= htmlspecialchars($user['name']) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@700;900&family=DM+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;600&display=swap" rel="stylesheet">
<style>
/* ── RESET & BASE ── */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
html{font-size:14px;-webkit-print-color-adjust:exact;print-color-adjust:exact}
body{
    font-family:'DM Sans',sans-serif;
    background:#e8f5dc;
    color:#111a0c;
    min-height:100vh;
    padding:30px 20px;
}

/* ── SCREEN CONTROLS ── */
.screen-controls{
    max-width:820px;
    margin:0 auto 20px;
    display:flex;
    gap:10px;
    align-items:center;
    flex-wrap:wrap;
}
.btn-print{
    background:linear-gradient(135deg,#2e6e1a,#4a9830);
    color:#fff;border:none;border-radius:10px;
    padding:11px 24px;font-family:'DM Sans',sans-serif;
    font-size:14px;font-weight:600;cursor:pointer;
    display:flex;align-items:center;gap:8px;
    box-shadow:0 4px 16px rgba(46,110,26,0.3);
    transition:transform 0.15s,box-shadow 0.15s;
}
.btn-print:hover{transform:translateY(-1px);box-shadow:0 6px 20px rgba(46,110,26,0.4)}
.btn-back{
    background:#fff;color:#2e6e1a;
    border:1.5px solid #cce0bb;border-radius:10px;
    padding:11px 20px;font-family:'DM Sans',sans-serif;
    font-size:14px;font-weight:600;cursor:pointer;
    text-decoration:none;display:inline-flex;align-items:center;gap:6px;
    transition:background 0.15s;
}
.btn-back:hover{background:#f2f7ed}

/* ── PRINT WRAPPER ── */
.print-wrap{
    max-width:820px;
    margin:0 auto;
    display:flex;
    flex-direction:column;
    gap:16px;
}

/* ═══════════════════════════════════════
   CARD FACE — the hero piece
═══════════════════════════════════════ */
.card-face{
    background:linear-gradient(135deg,#0d3b1e 0%,#1a6090 60%,#1a7a38 100%);
    border-radius:20px;
    padding:0;
    overflow:hidden;
    position:relative;
    box-shadow:0 12px 48px rgba(0,0,0,0.25);
    min-height:200px;
}
.card-face-bg{
    position:absolute;inset:0;
    background:
        radial-gradient(ellipse 60% 80% at 90% 10%, rgba(255,255,255,0.06) 0%, transparent 60%),
        radial-gradient(ellipse 40% 60% at 10% 90%, rgba(255,255,255,0.04) 0%, transparent 60%);
}
.card-face-pattern{
    position:absolute;inset:0;
    background-image:repeating-linear-gradient(
        45deg,
        rgba(255,255,255,0.025) 0px,
        rgba(255,255,255,0.025) 1px,
        transparent 1px,
        transparent 24px
    );
}
.card-face-inner{
    position:relative;z-index:1;
    padding:28px 32px;
    display:flex;
    flex-direction:column;
    gap:0;
    min-height:200px;
}
.card-face-top{
    display:flex;
    justify-content:space-between;
    align-items:flex-start;
    margin-bottom:16px;
}
.card-govt{
    font-family:'JetBrains Mono',monospace;
    font-size:9.5px;
    color:rgba(255,255,255,0.6);
    text-transform:uppercase;
    letter-spacing:1.8px;
    line-height:1.6;
}
.card-govt strong{color:rgba(255,255,255,0.9)}
.card-chip{
    background:linear-gradient(135deg,#d4a820,#f0c840);
    border-radius:6px;
    width:44px;height:34px;
    display:flex;align-items:center;justify-content:center;
    font-size:18px;
    box-shadow:0 2px 8px rgba(0,0,0,0.3);
}
.card-face-number{
    font-family:'JetBrains Mono',monospace;
    font-size:22px;
    font-weight:600;
    color:#fff;
    letter-spacing:3px;
    margin-bottom:18px;
    text-shadow:0 2px 8px rgba(0,0,0,0.3);
}
.card-face-bottom{
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
    flex-wrap:wrap;
    gap:10px;
}
.card-holder-name{
    font-family:'Fraunces',serif;
    font-size:18px;
    font-weight:700;
    color:#fff;
    text-transform:uppercase;
    letter-spacing:1.5px;
    text-shadow:0 2px 6px rgba(0,0,0,0.3);
}
.card-meta-row{
    display:flex;
    gap:24px;
    flex-wrap:wrap;
}
.card-meta-item{}
.card-meta-label{
    font-family:'JetBrains Mono',monospace;
    font-size:8px;color:rgba(255,255,255,0.55);
    text-transform:uppercase;letter-spacing:1.5px;
}
.card-meta-val{
    font-family:'JetBrains Mono',monospace;
    font-size:12px;color:rgba(255,255,255,0.95);
    font-weight:600;margin-top:2px;
}
.card-logo-area{
    display:flex;flex-direction:column;align-items:flex-end;gap:4px;
}
.card-brand{
    font-family:'Fraunces',serif;font-size:14px;
    font-weight:900;color:#fff;letter-spacing:1px;
}
.card-brand-sub{
    font-size:9px;color:rgba(255,255,255,0.55);
    font-family:'JetBrains Mono',monospace;letter-spacing:1px;
    text-transform:uppercase;
}
.card-visa-like{
    font-family:'Fraunces',serif;font-size:22px;
    font-weight:900;color:rgba(255,255,255,0.2);
    letter-spacing:-2px;
}

/* ══════════════════════
   INFO GRID
══════════════════════ */
.info-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:14px;
}
.info-box{
    background:#fff;
    border-radius:14px;
    padding:18px 20px;
    border:1.5px solid #cce0bb;
    box-shadow:0 2px 10px rgba(46,110,26,0.07);
}
.info-box-title{
    font-family:'JetBrains Mono',monospace;
    font-size:9px;text-transform:uppercase;
    letter-spacing:1.5px;color:#7a9860;
    margin-bottom:12px;
    display:flex;align-items:center;gap:6px;
}
.info-row{
    display:flex;justify-content:space-between;
    align-items:center;padding:6px 0;
    border-bottom:1px solid #e6f0de;
    gap:8px;
}
.info-row:last-child{border-bottom:none}
.info-row-label{font-size:12px;color:#3a5228}
.info-row-val{
    font-size:12px;font-weight:600;
    color:#111a0c;font-family:'JetBrains Mono',monospace;
    text-align:right;
}
.badge-print{
    font-size:10px;font-weight:700;
    padding:2px 10px;border-radius:20px;
    font-family:'JetBrains Mono',monospace;
    text-transform:uppercase;letter-spacing:0.5px;
}
.bp-green{background:#d8f5e4;color:#1a7a38}
.bp-gold{background:#fdf5d0;color:#c47820}
.bp-blue{background:#d8eef8;color:#1a6090}

/* ══════════════════════
   SUBSIDY BOX
══════════════════════ */
.subsidy-box{
    background:linear-gradient(135deg,#1a7a38,#2e6e1a);
    border-radius:16px;
    padding:22px 24px;
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:space-between;
    flex-wrap:wrap;
    gap:16px;
    box-shadow:0 4px 20px rgba(26,122,56,0.3);
}
.subsidy-label{
    font-family:'JetBrains Mono',monospace;
    font-size:9px;text-transform:uppercase;letter-spacing:2px;
    color:rgba(255,255,255,0.65);margin-bottom:4px;
}
.subsidy-amount{
    font-family:'Fraunces',serif;
    font-size:36px;font-weight:900;color:#fff;
    line-height:1;
}
.subsidy-breakdown{
    display:flex;flex-direction:column;gap:4px;min-width:200px;
}
.subsidy-line{
    display:flex;justify-content:space-between;
    font-size:11.5px;padding:3px 0;
    border-bottom:1px solid rgba(255,255,255,0.1);
    gap:16px;
}
.subsidy-line:last-child{
    border-bottom:none;
    font-weight:700;
    font-size:12.5px;
    padding-top:6px;
}
.subsidy-line span:last-child{
    font-family:'JetBrains Mono',monospace;
    font-weight:600;
}

/* ══════════════════════
   BENEFITS CHECKLIST
══════════════════════ */
.benefits-box{
    background:#fff;
    border-radius:14px;
    padding:20px 22px;
    border:1.5px solid #cce0bb;
    box-shadow:0 2px 10px rgba(46,110,26,0.07);
}
.benefits-header{
    display:flex;justify-content:space-between;
    align-items:center;margin-bottom:14px;flex-wrap:wrap;gap:8px;
}
.benefits-title{
    font-family:'Fraunces',serif;
    font-size:15px;font-weight:700;color:#111a0c;
}
.progress-pill{
    background:linear-gradient(90deg,#1a7a38,#4a9830);
    color:#fff;border-radius:20px;
    padding:4px 14px;font-size:11px;
    font-family:'JetBrains Mono',monospace;font-weight:600;
}
.benefits-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:6px;
}
.benefit-row{
    display:flex;align-items:center;gap:8px;
    padding:6px 8px;border-radius:8px;
    font-size:12px;
}
.benefit-row.active-b{background:#d8f5e4}
.benefit-row.inactive-b{background:#f2f7ed;opacity:0.65}
.benefit-check{
    width:18px;height:18px;border-radius:50%;
    flex-shrink:0;display:flex;align-items:center;
    justify-content:center;font-size:10px;font-weight:700;
}
.bc-on{background:#1a7a38;color:#fff}
.bc-off{background:#cce0bb;color:#7a9860}
.benefit-text{color:#3a5228;line-height:1.3}

/* ══════════════════════
   FOOTER STRIP
══════════════════════ */
.print-footer{
    background:#fff;border-radius:14px;
    padding:16px 22px;
    border:1.5px solid #cce0bb;
    display:flex;align-items:center;
    justify-content:space-between;flex-wrap:wrap;gap:10px;
}
.footer-brand{
    font-family:'Fraunces',serif;
    font-size:16px;font-weight:900;color:#2e6e1a;
}
.footer-meta{
    font-size:11px;color:#7a9860;
    font-family:'JetBrains Mono',monospace;
}
.footer-qr-placeholder{
    width:52px;height:52px;border-radius:8px;
    background:#f2f7ed;border:1.5px solid #cce0bb;
    display:flex;align-items:center;justify-content:center;
    font-size:22px;
}

/* ── PRINT STYLES ── */
@media print {
    body{background:#fff;padding:10px}
    .screen-controls{display:none!important}
    .print-wrap{max-width:100%}
    .card-face{-webkit-print-color-adjust:exact;print-color-adjust:exact}
    .subsidy-box{-webkit-print-color-adjust:exact;print-color-adjust:exact}
}
@page{margin:1cm;size:A4}
</style>
</head>
<body>

<!-- SCREEN-ONLY CONTROLS -->
<div class="screen-controls">
    <a href="farmers_card.php" class="btn-back">← Back</a>
    <button class="btn-print" onclick="window.print()">
        🖨️ Print This Card
    </button>
    <span style="font-size:12px;color:#7a9860;font-family:'JetBrains Mono',monospace">
        Tip: Use Ctrl+P / Cmd+P · Set margins to "None" for best result
    </span>
</div>


<!-- PRINT WRAPPER -->
<div class="print-wrap">

    <!-- ══ 1. CARD FACE ══ -->
    <div class="card-face">
        <div class="card-face-bg"></div>
        <div class="card-face-pattern"></div>
        <div class="card-face-inner">

            <div class="card-face-top">
                <div class="card-govt">
                    <strong>গণপ্রজাতন্ত্রী বাংলাদেশ সরকার</strong><br>
                    Government of Bangladesh<br>
                    Ministry of Agriculture · কৃষি মন্ত্রণালয়
                </div>
                <div style="display:flex;flex-direction:column;align-items:flex-end;gap:8px">
                    <div class="card-chip">🌾</div>
                    <div class="card-visa-like">VISA</div>
                </div>
            </div>

            <div class="card-face-number">
                <?= wordwrap(str_pad(htmlspecialchars($cardNumber), 16, '0', STR_PAD_RIGHT), 4, ' — ', true) ?>
            </div>

            <div class="card-face-bottom">
                <div>
                    <div class="card-holder-name"><?= htmlspecialchars(strtoupper($user['name'])) ?></div>
                    <div class="card-meta-row" style="margin-top:10px">
                        <div class="card-meta-item">
                            <div class="card-meta-label">Category</div>
                            <div class="card-meta-val"><?= strtoupper($categoryLabels[$category] ?? $category) ?></div>
                        </div>
                        <div class="card-meta-item">
                            <div class="card-meta-label">District</div>
                            <div class="card-meta-val"><?= strtoupper(htmlspecialchars($district)) ?></div>
                        </div>
                        <div class="card-meta-item">
                            <div class="card-meta-label">Registered</div>
                            <div class="card-meta-val"><?= $regDate ?></div>
                        </div>
                        <div class="card-meta-item">
                            <div class="card-meta-label">Phase</div>
                            <div class="card-meta-val"><?= strtoupper($phaseLabels[$phase] ?? $phase) ?></div>
                        </div>
                    </div>
                </div>
                <div class="card-logo-area">
                    <div class="card-brand">🌱 SoilSync</div>
                    <div class="card-brand-sub">Farmers Card Hub</div>
                    <div style="margin-top:6px">
                        <span style="background:rgba(212,168,32,0.3);border:1px solid rgba(212,168,32,0.5);
                            color:#f0c840;border-radius:20px;padding:3px 10px;
                            font-size:9px;font-family:'JetBrains Mono',monospace;font-weight:600;
                            letter-spacing:1px;text-transform:uppercase">
                            ✅ VERIFIED
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </div>


    <!-- ══ 2. INFO GRID ══ -->
    <div class="info-grid">

        <!-- Farmer Details -->
        <div class="info-box">
            <div class="info-box-title">👨‍🌾 Farmer Information</div>
            <div class="info-row">
                <span class="info-row-label">Full Name</span>
                <span class="info-row-val"><?= htmlspecialchars($user['name']) ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Card Number</span>
                <span class="info-row-val"><?= htmlspecialchars($cardNumber) ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Category</span>
                <span class="info-row-val">
                    <span class="badge-print bp-green"><?= $categoryLabels[$category] ?? $category ?></span>
                </span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Land Size</span>
                <span class="info-row-val"><?= number_format($landSize, 2) ?> acres</span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Bank Account</span>
                <span class="info-row-val"><?= htmlspecialchars($bankAcc) ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Registered</span>
                <span class="info-row-val"><?= $regDate ?></span>
            </div>
        </div>

        <!-- Farm Stats -->
        <div class="info-box">
            <div class="info-box-title">🌾 Farm Summary</div>
            <div class="info-row">
                <span class="info-row-label">Total Fields</span>
                <span class="info-row-val"><?= $fieldCount ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Total Area</span>
                <span class="info-row-val"><?= number_format($totalArea, 2) ?> acres</span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Growing Crops</span>
                <span class="info-row-val"><?= $cropCount ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Harvested Crops</span>
                <span class="info-row-val"><?= $harvestedCount ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Pest Reports Filed</span>
                <span class="info-row-val"><?= $pestCount ?></span>
            </div>
            <div class="info-row">
                <span class="info-row-label">Location</span>
                <span class="info-row-val"><?= htmlspecialchars($district) ?>, <?= htmlspecialchars($division) ?></span>
            </div>
        </div>

    </div>


    <!-- ══ 3. SUBSIDY ESTIMATE ══ -->
    <div class="subsidy-box">
        <div>
            <div class="subsidy-label">Estimated Annual Subsidy Entitlement</div>
            <div class="subsidy-amount">৳ <?= number_format($totalSubsidy) ?></div>
            <div style="font-size:11px;color:rgba(255,255,255,0.55);margin-top:6px;
                        font-family:'JetBrains Mono',monospace">
                Calculated by SoilSync · <?= date('d M Y') ?>
            </div>
        </div>
        <div class="subsidy-breakdown">
            <div class="subsidy-line">
                <span>Direct Cash Subsidy</span>
                <span>৳ <?= number_format($directCash) ?></span>
            </div>
            <div class="subsidy-line">
                <span>Fertilizer Subsidy (<?= number_format($totalArea,1) ?> acres)</span>
                <span>৳ <?= number_format($fertilizerSubsidy) ?></span>
            </div>
            <div class="subsidy-line">
                <span>Seed Subsidy (<?= $cropCount ?> crops)</span>
                <span>৳ <?= number_format($seedSubsidy) ?></span>
            </div>
            <div class="subsidy-line">
                <span>Irrigation Subsidy</span>
                <span>৳ <?= number_format($irrigationSubsidy) ?></span>
            </div>
            <div class="subsidy-line" style="border-top:1px solid rgba(255,255,255,0.3)">
                <span>TOTAL ESTIMATE</span>
                <span>৳ <?= number_format($totalSubsidy) ?></span>
            </div>
        </div>
    </div>


    <!-- ══ 4. BENEFITS CHECKLIST ══ -->
    <div class="benefits-box">
        <div class="benefits-header">
            <div class="benefits-title">🏛️ 10 Government Benefits — Status</div>
            <div class="progress-pill"><?= $activeCount ?>/10 Active</div>
        </div>
        <div class="benefits-grid">
            <?php foreach ($benefits as [$label, $active]): ?>
            <div class="benefit-row <?= $active ? 'active-b' : 'inactive-b' ?>">
                <div class="benefit-check <?= $active ? 'bc-on' : 'bc-off' ?>">
                    <?= $active ? '✓' : '○' ?>
                </div>
                <div class="benefit-text"><?= htmlspecialchars($label) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>


    <!-- ══ 5. FOOTER ══ -->
    <div class="print-footer">
        <div class="footer-qr-placeholder">🌱</div>
        <div>
            <div class="footer-brand">SoilSync · Farmers Card Hub</div>
            <div class="footer-meta" style="margin-top:4px">
                Printed: <?= date('d F Y, h:i A') ?> &nbsp;·&nbsp;
                Card: <?= htmlspecialchars($cardNumber) ?> &nbsp;·&nbsp;
                SoilSync ID: #<?= $uid ?>
            </div>
            <div class="footer-meta" style="margin-top:3px">
                This is a digital summary. Official card issued by Ministry of Agriculture, Bangladesh.
            </div>
        </div>
        <div style="text-align:right">
            <div style="font-size:10px;color:#7a9860;font-family:'JetBrains Mono',monospace">
                VERIFIED FARMER<br>
                <span style="font-size:14px">✅</span>
            </div>
        </div>
    </div>

</div><!-- /print-wrap -->

</body>
</html>
