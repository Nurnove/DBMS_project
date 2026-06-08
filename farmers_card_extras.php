<?php
/* ============================================================
   farmers_card_extras.php
   Drop-in for farmers_card.php — paste this include just
   before the final <?php include 'layout_end.php'; ?>

   Provides:
     1. Loan Readiness Score (animated progress gauge)
     2. DAE Office Locator (searchable table, DB-driven)
   ============================================================ */

/* ── LOAN READINESS SCORE CALCULATION ────────────────────
   Uses variables already set in farmers_card.php:
   $isRegistered, $fieldCount, $cropCount, $pestCount,
   $bankAccount, $userRow, $totalArea, $harvestedCount
   ──────────────────────────────────────────────────────── */

$loanScore   = 0;
$loanFactors = [];

// 1. Card Registered (+30)
if ($isRegistered) {
    $loanScore += 30;
    $loanFactors[] = ['label'=>'Farmers Card Registered',        'pts'=>30, 'earned'=>true,  'icon'=>'🪪'];
} else {
    $loanFactors[] = ['label'=>'Register your Farmers Card',      'pts'=>30, 'earned'=>false, 'icon'=>'🪪'];
}

// 2. Bank Account Linked (+20)
if (!empty($bankAccount) && $bankAccount !== '—') {
    $loanScore += 20;
    $loanFactors[] = ['label'=>'Bank Account Linked (Sonali Bank)', 'pts'=>20, 'earned'=>true,  'icon'=>'🏦'];
} else {
    $loanFactors[] = ['label'=>'Link Your Bank Account',            'pts'=>20, 'earned'=>false, 'icon'=>'🏦'];
}

// 3. Has Registered Fields (+20)
if ($fieldCount > 0) {
    $loanScore += 20;
    $loanFactors[] = ['label'=>"$fieldCount Field(s) Registered on SoilSync", 'pts'=>20, 'earned'=>true,  'icon'=>'🗺️'];
} else {
    $loanFactors[] = ['label'=>'Add at least 1 field on SoilSync',             'pts'=>20, 'earned'=>false, 'icon'=>'🗺️'];
}

// 4. Has Active or Harvested Crops (+15)
$anyCrops = ($cropCount + ($harvestedCount ?? 0)) > 0;
if ($anyCrops) {
    $loanScore += 15;
    $loanFactors[] = ['label'=>'Crop Records Documented',      'pts'=>15, 'earned'=>true,  'icon'=>'🌾'];
} else {
    $loanFactors[] = ['label'=>'Add crops to build crop history', 'pts'=>15, 'earned'=>false, 'icon'=>'🌾'];
}

// 5. Filed Pest Reports (+10)
if ($pestCount > 0) {
    $loanScore += 10;
    $loanFactors[] = ['label'=>'Pest Reports Filed (shows activity)', 'pts'=>10, 'earned'=>true,  'icon'=>'🐛'];
} else {
    $loanFactors[] = ['label'=>'File a Pest Report if applicable',    'pts'=>10, 'earned'=>false, 'icon'=>'🐛'];
}

// 6. Land size > 0 (+5)
if ($totalArea > 0) {
    $loanScore += 5;
    $loanFactors[] = ['label'=>'Land Area Recorded ('.number_format($totalArea,1).' acres)', 'pts'=>5, 'earned'=>true,  'icon'=>'📐'];
} else {
    $loanFactors[] = ['label'=>'Enter land area in your fields',   'pts'=>5, 'earned'=>false, 'icon'=>'📐'];
}

// Determine grade
$loanGrade = match(true) {
    $loanScore >= 90 => ['label'=>'Excellent',   'color'=>'#1a7a38', 'bg'=>'#d8f5e4', 'advice'=>'You are highly eligible. Visit your nearest Sonali Bank with your Farmers Card and NID.'],
    $loanScore >= 70 => ['label'=>'Good',         'color'=>'#2e6e1a', 'bg'=>'#e4f5d8', 'advice'=>'Good standing. Complete the remaining steps to maximise your loan amount.'],
    $loanScore >= 50 => ['label'=>'Fair',          'color'=>'#c47820', 'bg'=>'#fef0d0', 'advice'=>'You are on the right track. Register your card and link your bank account to improve your score.'],
    $loanScore >= 30 => ['label'=>'Needs Work',   'color'=>'#b83030', 'bg'=>'#fde8e8', 'advice'=>'Complete the steps below to build your eligibility. Start with registering your Farmers Card.'],
    default          => ['label'=>'Not Ready',    'color'=>'#7a9860', 'bg'=>'#f2f7ed', 'advice'=>'Begin by linking your Farmers Card on SoilSync. Each step increases your loan readiness.'],
};

/* ── DAE OFFICE LOCATOR — Database-driven ─────────────────
   Requires: $conn (MySQLi connection from db.php)
             $district (string|null — logged-in user's district)
   ──────────────────────────────────────────────────────── */
$userDistrictForDAE = $district ?? '';

$daeSQL = "
    SELECT l.division, l.district, d.upazila, d.address, d.phone, d.hours
    FROM   dae_offices d
    JOIN   locations   l ON l.id = d.location_id
    WHERE  d.is_active = 1
    ORDER BY
        CASE WHEN l.district = ? THEN 0 ELSE 1 END,
        l.division ASC,
        l.district ASC,
        d.upazila  ASC
";
$daeStmt = $conn->prepare($daeSQL);
$daeStmt->bind_param('s', $userDistrictForDAE);
$daeStmt->execute();
$daeOffices = $daeStmt->get_result()->fetch_all(MYSQLI_ASSOC);
$daeStmt->close();
?>

<!-- ══════════════════════════════════════════════════════════
     LOAN READINESS SCORE
     Drop this block into farmers_card.php
══════════════════════════════════════════════════════════ -->
<div class="card" style="margin-top:20px;position:relative;overflow:hidden">

    <!-- Decorative bg -->
    <div style="position:absolute;right:-20px;top:-20px;font-size:120px;opacity:0.04;pointer-events:none;user-select:none">🏦</div>

    <div class="card-title" style="margin-bottom:20px">
        🏦 Loan Readiness Score
    </div>

    <div style="display:flex;gap:28px;align-items:flex-start;flex-wrap:wrap">

        <!-- ── GAUGE ── -->
        <div style="flex-shrink:0;text-align:center;min-width:160px">
            <div style="position:relative;width:160px;height:90px;margin:0 auto 8px">
                <!-- SVG semi-circle gauge -->
                <svg width="160" height="90" viewBox="0 0 160 90" style="overflow:visible">
                    <!-- Background arc -->
                    <path d="M 15 85 A 65 65 0 0 1 145 85"
                          fill="none" stroke="var(--border)" stroke-width="12"
                          stroke-linecap="round"/>
                    <!-- Score arc — calculate stroke-dasharray from score -->
                    <?php
                    $circumference = 204; // half-circle arc length approx
                    $filled = round($circumference * ($loanScore / 100));
                    ?>
                    <path d="M 15 85 A 65 65 0 0 1 145 85"
                          fill="none"
                          stroke="<?= $loanGrade['color'] ?>"
                          stroke-width="12"
                          stroke-linecap="round"
                          stroke-dasharray="<?= $filled ?> <?= $circumference ?>"
                          style="transition:stroke-dasharray 1.2s cubic-bezier(0.4,0,0.2,1)"
                          class="loan-arc"/>
                    <!-- Score text -->
                    <text x="80" y="72" text-anchor="middle"
                          font-family="Fraunces,serif" font-size="32" font-weight="900"
                          fill="<?= $loanGrade['color'] ?>">
                        <?= $loanScore ?>
                    </text>
                    <text x="80" y="86" text-anchor="middle"
                          font-family="JetBrains Mono,monospace" font-size="9"
                          fill="var(--text3)" letter-spacing="1">
                        OUT OF 100
                    </text>
                </svg>
            </div>

            <!-- Grade badge -->
            <div style="
                display:inline-block;
                background:<?= $loanGrade['bg'] ?>;
                color:<?= $loanGrade['color'] ?>;
                border:1.5px solid <?= $loanGrade['color'] ?>40;
                border-radius:20px;padding:5px 18px;
                font-family:var(--font-mono);font-size:12px;font-weight:700;
                letter-spacing:0.5px;text-transform:uppercase;
            ">
                <?= $loanGrade['label'] ?>
            </div>

            <!-- Advice -->
            <div style="margin-top:12px;font-size:12px;color:var(--text2);
                        line-height:1.55;text-align:left;max-width:180px;margin-left:auto;margin-right:auto">
                <?= htmlspecialchars($loanGrade['advice']) ?>
            </div>
        </div>

        <!-- ── FACTOR BREAKDOWN ── -->
        <div style="flex:1;min-width:220px">

            <div style="font-size:12px;font-family:var(--font-mono);
                        text-transform:uppercase;letter-spacing:1px;
                        color:var(--text3);margin-bottom:12px">
                Score Breakdown
            </div>

            <div style="display:flex;flex-direction:column;gap:6px">
            <?php foreach ($loanFactors as $f): ?>
            <div style="
                display:flex;align-items:center;gap:10px;
                padding:9px 12px;border-radius:10px;
                background:<?= $f['earned'] ? 'var(--success-light)' : 'var(--surface2)' ?>;
                border:1px solid <?= $f['earned'] ? 'var(--success)40' : 'var(--border)' ?>;
                transition:background 0.2s;
            ">
                <!-- tick/cross -->
                <div style="
                    width:22px;height:22px;border-radius:50%;flex-shrink:0;
                    display:flex;align-items:center;justify-content:center;
                    font-size:11px;font-weight:700;
                    background:<?= $f['earned'] ? 'var(--success)' : 'var(--border2)' ?>;
                    color:<?= $f['earned'] ? '#fff' : 'var(--text3)' ?>;
                ">
                    <?= $f['earned'] ? '✓' : '○' ?>
                </div>

                <!-- icon + label -->
                <div style="flex:1;min-width:0">
                    <span style="font-size:13px;
                        color:<?= $f['earned'] ? 'var(--text)' : 'var(--text2)' ?>;
                        font-weight:<?= $f['earned'] ? '600' : '400' ?>">
                        <?= $f['icon'] ?> <?= htmlspecialchars($f['label']) ?>
                    </span>
                </div>

                <!-- pts -->
                <div style="
                    font-family:var(--font-mono);font-size:11px;font-weight:700;flex-shrink:0;
                    color:<?= $f['earned'] ? 'var(--success)' : 'var(--text3)' ?>;
                ">
                    +<?= $f['pts'] ?>
                </div>
            </div>
            <?php endforeach; ?>
            </div>

            <!-- Progress bar -->
            <div style="margin-top:14px">
                <div style="display:flex;justify-content:space-between;
                            font-size:11px;font-family:var(--font-mono);color:var(--text3);
                            margin-bottom:5px">
                    <span>Score Progress</span>
                    <span><?= $loanScore ?>/100</span>
                </div>
                <div style="height:8px;border-radius:20px;background:var(--border);overflow:hidden">
                    <div style="
                        height:100%;border-radius:20px;
                        width:<?= $loanScore ?>%;
                        background:linear-gradient(90deg,<?= $loanGrade['color'] ?>,<?= $loanGrade['color'] ?>aa);
                        transition:width 1s cubic-bezier(0.4,0,0.2,1);
                    "></div>
                </div>
            </div>

            <!-- CTA if not perfect -->
            <?php if ($loanScore < 100): ?>
            <div style="margin-top:14px;padding:12px 14px;
                        background:var(--gold-light);border:1px solid var(--gold)40;
                        border-radius:10px;font-size:12.5px;color:var(--text2);line-height:1.5">
                💡 <strong>Next Step:</strong>
                <?php
                $nextStep = null;
                foreach ($loanFactors as $f) {
                    if (!$f['earned']) { $nextStep = $f; break; }
                }
                echo htmlspecialchars($nextStep['label'] ?? 'All steps complete!');
                ?> to earn <strong>+<?= $nextStep['pts'] ?? 0 ?> points</strong>.
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     DAE OFFICE LOCATOR
══════════════════════════════════════════════════════════ -->
<div class="card" style="margin-top:20px">

    <div class="card-title" style="margin-bottom:4px">
        📍 DAE Office Locator
    </div>
    <div style="font-size:13px;color:var(--text2);margin-bottom:16px">
        Find your nearest Department of Agricultural Extension office to register,
        claim subsidies, or attend training. Bring your Farmers Card + NID.
    </div>

    <!-- Search box -->
    <div style="position:relative;margin-bottom:14px">
        <span style="position:absolute;left:12px;top:50%;transform:translateY(-50%);font-size:15px">🔍</span>
        <input
            type="text"
            id="daeSearch"
            placeholder="Search by district, upazila or division…"
            oninput="filterDAE()"
            style="
                width:100%;padding:10px 12px 10px 36px;
                border:1.5px solid var(--border);border-radius:10px;
                background:var(--surface2);color:var(--text);
                font-family:var(--font-body);font-size:13px;
                outline:none;transition:border-color 0.2s;
            "
            onfocus="this.style.borderColor='var(--accent)'"
            onblur="this.style.borderColor='var(--border)'"
        >
    </div>

    <!-- Your district highlight -->
    <?php if (!empty($userDistrictForDAE) && $userDistrictForDAE !== '—'): ?>
    <div style="
        display:inline-flex;align-items:center;gap:8px;
        background:var(--accent-light);border:1px solid var(--accent)30;
        border-radius:8px;padding:7px 12px;font-size:12px;
        color:var(--accent);font-weight:600;margin-bottom:12px;
    ">
        📍 Showing your district first: <strong><?= htmlspecialchars($userDistrictForDAE) ?></strong>
        <button onclick="document.getElementById('daeSearch').value='<?= htmlspecialchars(addslashes($userDistrictForDAE)) ?>';filterDAE()"
                style="background:var(--accent);color:#fff;border:none;border-radius:6px;
                       padding:3px 10px;font-size:11px;cursor:pointer;font-family:var(--font-body)">
            Show only mine
        </button>
        <button onclick="document.getElementById('daeSearch').value='';filterDAE()"
                style="background:none;color:var(--accent);border:1px solid var(--accent)40;
                       border-radius:6px;padding:3px 10px;font-size:11px;cursor:pointer;font-family:var(--font-body)">
            Show all
        </button>
    </div>
    <?php endif; ?>

    <!-- Table -->
    <div class="table-wrap" style="overflow-x:auto">
        <table id="daeTable" style="min-width:600px">
            <thead>
                <tr>
                    <th>Division</th>
                    <th>District</th>
                    <th>Upazila</th>
                    <th>Address</th>
                    <th>Phone</th>
                    <th>Hours</th>
                </tr>
            </thead>
            <tbody id="daeBody">
            <?php foreach ($daeOffices as $office):
                $isUserDist = (!empty($userDistrictForDAE) && $office['district'] === $userDistrictForDAE);
                $searchKey  = strtolower($office['division'].' '.$office['district'].' '.$office['upazila']);
            ?>
            <tr data-search="<?= htmlspecialchars($searchKey) ?>"
                style="<?= $isUserDist ? 'background:var(--success-light);' : '' ?>">
                <td>
                    <span class="badge badge-neutral" style="font-size:11px"><?= htmlspecialchars($office['division']) ?></span>
                </td>
                <td style="font-weight:<?= $isUserDist ? '700' : '400' ?>;color:<?= $isUserDist ? 'var(--success)' : 'inherit' ?>">
                    <?= $isUserDist ? '📍 ' : '' ?><?= htmlspecialchars($office['district']) ?>
                </td>
                <td style="font-weight:600"><?= htmlspecialchars($office['upazila']) ?></td>
                <td style="font-size:12px;color:var(--text2)"><?= htmlspecialchars($office['address']) ?></td>
                <td style="font-family:var(--font-mono);font-size:12px">
                    <a href="tel:<?= htmlspecialchars($office['phone']) ?>" style="color:var(--accent);text-decoration:none">
                        <?= htmlspecialchars($office['phone']) ?>
                    </a>
                </td>
                <td style="font-size:12px;color:var(--text3)"><?= htmlspecialchars($office['hours']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- No results state -->
    <div id="daeNoResults" style="display:none;padding:24px;text-align:center;color:var(--text3)">
        <div style="font-size:36px;margin-bottom:8px">🔍</div>
        <div>No offices found for that search. Try searching by division name.</div>
    </div>

    <div style="margin-top:12px;font-size:12px;color:var(--text3)">
        📋 Data sourced from Bangladesh Department of Agricultural Extension (DAE).
        Call ahead to confirm hours. Offices open Sunday–Thursday.
    </div>

</div>


<!-- JS for DAE search + gauge animation -->
<script>
function filterDAE() {
    const q = document.getElementById('daeSearch').value.toLowerCase().trim();
    const rows = document.querySelectorAll('#daeBody tr');
    let visible = 0;
    rows.forEach(row => {
        const match = !q || row.dataset.search.includes(q);
        row.style.display = match ? '' : 'none';
        if (match) visible++;
    });
    document.getElementById('daeNoResults').style.display = visible === 0 ? 'block' : 'none';
}

// Animate gauge arc on load
window.addEventListener('DOMContentLoaded', () => {
    const arc = document.querySelector('.loan-arc');
    if (arc) {
        const finalDash = arc.getAttribute('stroke-dasharray');
        arc.setAttribute('stroke-dasharray', '0 204');
        setTimeout(() => arc.setAttribute('stroke-dasharray', finalDash), 100);
    }
});
</script>