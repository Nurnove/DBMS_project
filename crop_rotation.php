<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Crop Rotation Advisor';
$activeNav = 'rotation';

$user = currentUser($conn);
$uid  = (int)$user['id'];

/* ============================================================
   STEP 1 — LOAD ROTATION RULES FROM DATABASE
   Table: crop_rotation_rules
   Builds same structure as old $rotationRules PHP array
   ============================================================ */
$rotationRules = [];
$rulesResult = $conn->query("
    SELECT
        cp.name        AS prev_crop,
        cn.name        AS next_crop,
        rr.relation,
        rr.reason,
        rr.icon
    FROM crop_rotation_rules rr
    JOIN crops cp ON cp.id = rr.crop_id
    JOIN crops cn ON cn.id = rr.next_crop_id
    ORDER BY cp.name, rr.relation
");

if ($rulesResult) {
    while ($row = $rulesResult->fetch_assoc()) {
        $prev = $row['prev_crop'];

        // Set icon and reason once per previous crop
        if (!isset($rotationRules[$prev])) {
            $rotationRules[$prev] = [
                'icon'   => $row['icon'],
                'reason' => '',  // will be set from first 'good' or 'avoid' row
                'good'   => [],
                'avoid'  => [],
            ];
        }

        // Use the first reason we encounter as the general reason for this crop
        if (empty($rotationRules[$prev]['reason'])) {
            $rotationRules[$prev]['reason'] = $row['reason'];
        }

        if ($row['relation'] === 'good') {
            $rotationRules[$prev]['good'][] = $row['next_crop'];
        } else {
            $rotationRules[$prev]['avoid'][] = $row['next_crop'];
        }
    }
}

/* ============================================================
   STEP 2 — LOAD SOIL-CROP BONUS FROM DATABASE
   Table: soil_crop_bonus
   Builds same structure as old $soilBonus PHP array
   ============================================================ */
$soilBonus = [];
$bonusResult = $conn->query("
    SELECT
        scb.soil_type,
        c.name AS crop_name,
        scb.bonus
    FROM soil_crop_bonus scb
    JOIN crops c ON c.id = scb.crop_id
    ORDER BY scb.soil_type, scb.bonus DESC
");

if ($bonusResult) {
    while ($row = $bonusResult->fetch_assoc()) {
        $soilBonus[$row['soil_type']][$row['crop_name']] = (int)$row['bonus'];
    }
}

/* ============================================================
   STEP 3 — FETCH USER'S HARVESTED / FAILED CROPS
   Last 6 months only
   ============================================================ */
$harvestedCrops = $conn->query("
    SELECT
        fc.id             AS farmer_crop_id,
        fc.crop_id,
        fc.field_id,
        fc.planted_date,
        fc.expected_harvest,
        fc.status,
        fc.created_at,
        c.name            AS crop_name,
        f.name            AS field_name,
        f.soil_type,
        f.area
    FROM farmer_crops fc
    JOIN crops c ON c.id = fc.crop_id
    LEFT JOIN fields f ON f.id = fc.field_id
    WHERE fc.user_id = $uid
      AND fc.status IN ('harvested','failed')
      AND fc.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    ORDER BY fc.created_at DESC
");

/* ============================================================
   STEP 4 — FETCH ALL CROPS (suggestion pool)
   ============================================================ */
$allCrops = [];
$cr = $conn->query("SELECT id, name FROM crops ORDER BY name");
while ($row = $cr->fetch_assoc()) {
    $allCrops[$row['name']] = $row['id'];
}

/* ============================================================
   STEP 5 — GROWING CROPS (field busy warning)
   ============================================================ */
$growingFieldIds = [];
$gr = $conn->query("
    SELECT DISTINCT field_id
    FROM farmer_crops
    WHERE user_id = $uid
      AND status = 'growing'
      AND field_id IS NOT NULL
");
while ($row = $gr->fetch_assoc()) {
    $growingFieldIds[] = (int)$row['field_id'];
}

/* ============================================================
   STEP 6 — SEASON SUITABILITY (from existing crop_calendar)
   ============================================================ */
function getCurrentSeason(): string {
    $m = (int)date('n');
    if ($m >= 6 && $m <= 9)  return 'Monsoon';
    if ($m >= 10 || $m <= 2) return 'Winter';
    return 'Summer';
}

$currentSeason = getCurrentSeason();

$seasonScores = [];
$ss = $conn->query("
    SELECT c.name AS crop_name, cc.season, cc.suitability_score
    FROM crop_calendar cc
    JOIN crops c ON c.id = cc.crop_id
");
while ($row = $ss->fetch_assoc()) {
    $seasonScores[$row['crop_name']][$row['season']] = (int)$row['suitability_score'];
}

/* ============================================================
   STEP 7 — BEST SEED PER CROP (from seeds table)
   ============================================================ */
$seedInfo = [];
$si = $conn->query("
    SELECT s.*, c.name AS crop_name
    FROM seeds s
    JOIN crops c ON c.id = s.crop_id
    ORDER BY s.pest_resistance DESC, s.harvest_days ASC
");
while ($row = $si->fetch_assoc()) {
    if (!isset($seedInfo[$row['crop_name']])) {
        $seedInfo[$row['crop_name']] = $row;
    }
}

/* ============================================================
   CORE SCORING FUNCTION — unchanged logic, new data source
   ============================================================ */
function getRotationSuggestions(
    string $prevCrop,
    string $soilType,
    string $season,
    array  $rotationRules,
    array  $soilBonus,
    array  $seasonScores,
    array  $allCrops
): array {

    $rule  = $rotationRules[$prevCrop] ?? null;
    $good  = $rule['good']  ?? [];
    $avoid = $rule['avoid'] ?? [];
    $bonus = $soilBonus[$soilType] ?? [];

    $results = [];

    foreach ($allCrops as $cropName => $cropId) {

        if ($cropName === $prevCrop) continue;

        $score = 50;
        $tags  = [];
        $tier  = 'neutral';

        // Rotation relation
        if (in_array($cropName, $good)) {
            $score += 35;
            $tags[]  = 'Rotation fit';
            $tier    = 'best';
        } elseif (in_array($cropName, $avoid)) {
            $score -= 40;
            $tags[]  = 'Disease risk';
            $tier    = 'avoid';
        }

        // Soil compatibility
        if (isset($bonus[$cropName])) {
            $score  += $bonus[$cropName];
            $tags[]  = 'Soil match';
        }

        // Season suitability
        $seasonScore = $seasonScores[$cropName][$season] ?? 50;
        if ($seasonScore >= 80) {
            $score += 20;
            $tags[]  = 'Season ideal';
        } elseif ($seasonScore >= 50) {
            $score += 8;
            $tags[]  = 'Season ok';
        } else {
            $score -= 15;
            $tags[]  = 'Off-season';
        }

        $score = max(0, min(100, $score));

        $results[] = [
            'crop_id'      => $cropId,
            'crop_name'    => $cropName,
            'score'        => $score,
            'tier'         => $tier,
            'tags'         => $tags,
            'season_score' => $seasonScore,
        ];
    }

    usort($results, fn($a, $b) => $b['score'] - $a['score']);

    return $results;
}

/* ============================================================
   BUILD ADVISOR DATA
   ============================================================ */
$advisorData = [];
if ($harvestedCrops && $harvestedCrops->num_rows > 0) {
    while ($fc = $harvestedCrops->fetch_assoc()) {
        $prevCrop = $fc['crop_name'];
        $soilType = $fc['soil_type'] ?? 'Loamy';
        $rule     = $rotationRules[$prevCrop] ?? null;

        $suggestions = getRotationSuggestions(
            $prevCrop, $soilType, $currentSeason,
            $rotationRules, $soilBonus, $seasonScores, $allCrops
        );

        $best   = array_filter($suggestions, fn($s) => $s['tier'] === 'best');
        $good   = array_filter($suggestions, fn($s) => $s['tier'] === 'neutral' && $s['score'] >= 60);
        $avoidL = array_filter($suggestions, fn($s) => $s['tier'] === 'avoid');

        $advisorData[] = [
            'farmer_crop' => $fc,
            'rule'        => $rule,
            'suggestions' => $suggestions,
            'best'        => array_values($best),
            'good'        => array_values(array_slice($good, 0, 3)),
            'avoid'       => array_values($avoidL),
            'field_busy'  => in_array((int)$fc['field_id'], $growingFieldIds),
        ];
    }
}

include 'layout.php';
?>

<!-- PAGE HEADER -->
<div class="welcome-banner" style="margin-bottom:24px">
    <div class="welcome-left">
        <div class="welcome-greet">Smart Farming Tool</div>
        <h2 class="welcome-title">🔄 Crop Rotation Advisor</h2>
        <div class="welcome-sub">
            Based on your harvested crops, soil type &amp; current season —
            <strong><?= $currentSeason ?></strong>
        </div>
    </div>
    <div class="weather-card" style="text-align:left;min-width:180px">
        <div style="font-size:11px;color:rgba(255,255,255,0.7);font-family:var(--font-mono);
                    letter-spacing:1px;text-transform:uppercase;margin-bottom:4px">
            Current Season
        </div>
        <div style="font-size:22px;font-family:var(--font-display);
                    font-weight:900;color:#fff;line-height:1.1">
            <?= $currentSeason === 'Monsoon' ? '🌧️' : ($currentSeason === 'Winter' ? '❄️' : '☀️') ?>
            <?= $currentSeason ?>
        </div>
        <div style="font-size:12px;color:rgba(255,255,255,0.75);margin-top:6px">
            <?= date('F Y') ?>
        </div>
    </div>
</div>


<!-- WHAT IS CROP ROTATION -->
<div class="card reveal" style="margin-bottom:24px;border-left:4px solid var(--accent)">
    <div style="display:flex;align-items:flex-start;gap:16px">
        <div style="font-size:36px;flex-shrink:0">🔄</div>
        <div>
            <div class="card-title" style="margin-bottom:6px">What is Crop Rotation?</div>
            <p style="font-size:14px;color:var(--text2);line-height:1.7">
                Planting the same crop repeatedly on the same field depletes specific nutrients,
                builds up pests, and reduces yield each season. Crop rotation solves this by
                alternating crop families — each crop restores what the previous one consumed.
                This tool analyses your past crops, your field's soil type, and the current season
                to recommend the best next crop for each field.
            </p>
        </div>
    </div>
</div>


<?php if (empty($advisorData)): ?>

<!-- EMPTY STATE -->
<div class="card">
    <div class="empty-state">
        <div class="empty-icon">🌱</div>
        <h3>No harvested crops found</h3>
        <p>The rotation advisor works after you mark a crop as harvested or failed.
           Go track a crop first, then come back here for your personalised recommendation.</p>
        <a href="crops.php" class="btn btn-primary">🌾 Go to My Crops</a>
    </div>
</div>

<?php else: ?>

<!-- LEGEND -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-bottom:24px">
    <div style="display:flex;align-items:center;gap:7px;background:var(--success-light);
                border:1px solid var(--success);border-radius:var(--radius-sm);
                padding:8px 14px;font-size:13px;color:var(--success);font-weight:600">
        ✅ Best pick — rotation + soil + season all match
    </div>
    <div style="display:flex;align-items:center;gap:7px;background:var(--warn-light);
                border:1px solid var(--warn);border-radius:var(--radius-sm);
                padding:8px 14px;font-size:13px;color:var(--warn);font-weight:600">
        ⚠️ Good option — partial match
    </div>
    <div style="display:flex;align-items:center;gap:7px;background:var(--danger-light);
                border:1px solid var(--danger);border-radius:var(--radius-sm);
                padding:8px 14px;font-size:13px;color:var(--danger);font-weight:600">
        ❌ Avoid — disease carryover risk
    </div>
</div>


<?php foreach ($advisorData as $idx => $data):
    $fc        = $data['farmer_crop'];
    $rule      = $data['rule'];
    $bestList  = $data['best'];
    $goodList  = $data['good'];
    $avoidList = $data['avoid'];
?>

<!-- ADVISOR CARD PER FIELD -->
<div class="card reveal" style="margin-bottom:24px;position:relative;overflow:hidden">

    <!-- Top accent bar -->
    <div style="position:absolute;top:0;left:0;right:0;height:4px;
                background:linear-gradient(90deg,var(--accent),var(--accent2))"></div>

    <!-- HEADER -->
    <div style="display:flex;align-items:flex-start;justify-content:space-between;
                flex-wrap:wrap;gap:12px;margin-bottom:20px;padding-top:8px">

        <div>
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:6px">
                <div style="font-size:28px"><?= $rule['icon'] ?? '🌿' ?></div>
                <div>
                    <div style="font-family:var(--font-display);font-size:18px;
                                font-weight:700;color:var(--text)">
                        <?= htmlspecialchars($fc['crop_name']) ?>
                        <span style="font-size:13px;color:var(--text3);
                                     font-family:var(--font-body);font-weight:400">
                            previously grown
                        </span>
                    </div>
                    <div style="font-size:13px;color:var(--text3);margin-top:2px;
                                display:flex;gap:12px;flex-wrap:wrap">
                        <?php if ($fc['field_name']): ?>
                            <span>🗺️ <?= htmlspecialchars($fc['field_name']) ?></span>
                        <?php endif; ?>
                        <?php if ($fc['soil_type']): ?>
                            <span>🪨 <?= htmlspecialchars($fc['soil_type']) ?> soil</span>
                        <?php endif; ?>
                        <?php if ($fc['area']): ?>
                            <span>📐 <?= $fc['area'] ?> acres</span>
                        <?php endif; ?>
                        <span>📅 Planted <?= date('d M Y', strtotime($fc['planted_date'])) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap">
            <?php
            $stBg  = $fc['status'] === 'harvested' ? 'badge-success' : 'badge-danger';
            $stLbl = $fc['status'] === 'harvested' ? '✅ Harvested' : '❌ Failed';
            ?>
            <span class="badge <?= $stBg ?>"><?= $stLbl ?></span>
            <?php if ($data['field_busy']): ?>
                <span class="badge badge-warn">⚠️ Field busy</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- WHY ROTATE BOX -->
    <?php if ($rule): ?>
    <div style="background:var(--surface2);border:1px solid var(--border);
                border-radius:var(--radius-sm);padding:14px 16px;
                margin-bottom:22px;display:flex;gap:12px;align-items:flex-start">
        <div style="font-size:20px;flex-shrink:0">💡</div>
        <div>
            <div style="font-size:12px;font-weight:700;color:var(--text3);
                        font-family:var(--font-mono);letter-spacing:1px;
                        text-transform:uppercase;margin-bottom:4px">Why rotate?</div>
            <div style="font-size:13.5px;color:var(--text2);line-height:1.6">
                <?= htmlspecialchars($rule['reason']) ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SCORE BAR HEADER -->
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:14px">
        <div class="card-title" style="font-size:15px">Crop Recommendations</div>
        <div style="font-size:11px;color:var(--text4);font-family:var(--font-mono)">
            Sorted by rotation score
        </div>
    </div>


    <!-- BEST PICKS -->
    <?php if (!empty($bestList)): ?>
    <div style="margin-bottom:20px">
        <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
                    color:var(--success);font-family:var(--font-mono);margin-bottom:10px">
            ✅ Best picks for this field
        </div>
        <div class="grid-3">
        <?php foreach (array_slice($bestList, 0, 3) as $sug):
            $seed = $seedInfo[$sug['crop_name']] ?? null;
        ?>
            <div class="crop-card" style="border-color:var(--success);background:var(--success-light)">

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px">
                    <div style="font-size:22px">
                        <?= $rotationRules[$sug['crop_name']]['icon'] ?? '🌿' ?>
                    </div>
                    <div style="background:var(--success);color:#fff;border-radius:50%;
                                width:40px;height:40px;display:flex;align-items:center;
                                justify-content:center;font-family:var(--font-mono);
                                font-size:11px;font-weight:700;flex-shrink:0">
                        <?= $sug['score'] ?>
                    </div>
                </div>

                <div style="font-family:var(--font-display);font-size:16px;font-weight:700;
                            color:var(--text);margin-bottom:6px">
                    <?= htmlspecialchars($sug['crop_name']) ?>
                </div>

                <div class="progress-track" style="margin-bottom:10px">
                    <div class="progress-fill" style="width:<?= $sug['score'] ?>%"></div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:5px;margin-bottom:10px">
                    <?php foreach ($sug['tags'] as $tag):
                        $tc = 'badge-neutral';
                        if ($tag === 'Rotation fit') $tc = 'badge-success';
                        if ($tag === 'Soil match')   $tc = 'badge-info';
                        if ($tag === 'Season ideal') $tc = 'badge-gold';
                        if ($tag === 'Disease risk') $tc = 'badge-danger';
                        if ($tag === 'Off-season')   $tc = 'badge-warn';
                    ?>
                        <span class="badge <?= $tc ?>" style="font-size:10px"><?= $tag ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($seed): ?>
                <div style="background:rgba(255,255,255,0.5);border-radius:var(--radius-xs);
                            padding:8px 10px;font-size:12px;color:var(--text2);line-height:1.6">
                    🌱 <strong><?= htmlspecialchars($seed['name']) ?></strong>
                    (<?= $seed['type'] ?>)<br>
                    📦 Yield: <?= htmlspecialchars($seed['yield_info']) ?><br>
                    📅 Ready in <?= $seed['harvest_days'] ?> days
                    <?= $seed['pest_resistance'] ? ' · 🛡️ Pest resistant' : '' ?>
                </div>
                <?php endif; ?>

                <a href="crops.php?action=add&crop_id=<?= $sug['crop_id'] ?>&field_id=<?= $fc['field_id'] ?>"
                   class="btn btn-primary btn-sm btn-block" style="margin-top:12px">
                    🌱 Plant This
                </a>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>


    <!-- GOOD OPTIONS -->
    <?php if (!empty($goodList)): ?>
    <div style="margin-bottom:20px">
        <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
                    color:var(--warn);font-family:var(--font-mono);margin-bottom:10px">
            ⚠️ Good alternatives
        </div>
        <div class="grid-3">
        <?php foreach ($goodList as $sug):
            $seed = $seedInfo[$sug['crop_name']] ?? null;
        ?>
            <div class="crop-card" style="border-color:var(--warn)">

                <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
                    <div style="font-size:20px">
                        <?= $rotationRules[$sug['crop_name']]['icon'] ?? '🌿' ?>
                    </div>
                    <div style="background:var(--warn);color:#fff;border-radius:50%;
                                width:36px;height:36px;display:flex;align-items:center;
                                justify-content:center;font-family:var(--font-mono);
                                font-size:11px;font-weight:700;flex-shrink:0">
                        <?= $sug['score'] ?>
                    </div>
                </div>

                <div style="font-family:var(--font-display);font-size:15px;font-weight:700;
                            color:var(--text);margin-bottom:6px">
                    <?= htmlspecialchars($sug['crop_name']) ?>
                </div>

                <div class="progress-track" style="margin-bottom:8px">
                    <div class="progress-fill orange" style="width:<?= $sug['score'] ?>%"></div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:4px;margin-bottom:8px">
                    <?php foreach ($sug['tags'] as $tag):
                        $tc = 'badge-neutral';
                        if ($tag === 'Rotation fit') $tc = 'badge-success';
                        if ($tag === 'Soil match')   $tc = 'badge-info';
                        if ($tag === 'Season ideal') $tc = 'badge-gold';
                        if ($tag === 'Off-season')   $tc = 'badge-warn';
                    ?>
                        <span class="badge <?= $tc ?>" style="font-size:10px"><?= $tag ?></span>
                    <?php endforeach; ?>
                </div>

                <?php if ($seed): ?>
                <div style="font-size:11px;color:var(--text3);line-height:1.5">
                    🌱 <?= htmlspecialchars($seed['name']) ?> · <?= $seed['harvest_days'] ?>d
                    <?= $seed['pest_resistance'] ? '· 🛡️' : '' ?>
                </div>
                <?php endif; ?>

                <a href="crops.php?action=add&crop_id=<?= $sug['crop_id'] ?>&field_id=<?= $fc['field_id'] ?>"
                   class="btn btn-outline btn-sm btn-block" style="margin-top:10px">
                    + Consider This
                </a>
            </div>
        <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>


    <!-- AVOID LIST -->
    <?php if (!empty($avoidList)): ?>
    <div>
        <div style="font-size:11px;font-weight:700;letter-spacing:2px;text-transform:uppercase;
                    color:var(--danger);font-family:var(--font-mono);margin-bottom:8px">
            ❌ Do not plant next on this field
        </div>
        <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php foreach ($avoidList as $sug): ?>
                <div style="background:var(--danger-light);border:1px solid var(--danger);
                            border-radius:var(--radius-sm);padding:8px 14px;
                            display:flex;align-items:center;gap:8px">
                    <span style="font-size:18px">
                        <?= $rotationRules[$sug['crop_name']]['icon'] ?? '🌿' ?>
                    </span>
                    <span style="font-size:13px;font-weight:600;color:var(--danger)">
                        <?= htmlspecialchars($sug['crop_name']) ?>
                    </span>
                    <span style="font-size:10px;color:var(--danger);font-family:var(--font-mono)">
                        score: <?= $sug['score'] ?>
                    </span>
                </div>
            <?php endforeach; ?>
        </div>
        <div style="font-size:12px;color:var(--text3);margin-top:8px">
            ⚠️ These crops share the same disease family or nutrient needs as
            <?= htmlspecialchars($fc['crop_name']) ?>.
            Planting them risks lower yield and pest carryover.
        </div>
    </div>
    <?php endif; ?>

    <!-- FIELD BUSY WARNING -->
    <?php if ($data['field_busy']): ?>
    <div class="alert alert-warn" style="margin-top:16px;margin-bottom:0">
        ⚠️ <strong>This field already has a growing crop.</strong>
        These recommendations will be ready to use after the current crop is harvested.
    </div>
    <?php endif; ?>

</div><!-- /advisor card -->

<?php endforeach; ?>


<!-- ROTATION QUICK REFERENCE TABLE (from DB) -->
<div class="card reveal" style="margin-top:8px">
    <div class="card-header">
        <div>
            <div class="card-title">📋 Rotation Quick Reference</div>
            <div class="card-subtitle">All rotation rules — loaded from database</div>
        </div>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Previous Crop</th>
                    <th>Best Next Crops</th>
                    <th>Avoid</th>
                    <th>Reason</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rotationRules as $cropName => $rule): ?>
                <tr>
                    <td>
                        <span style="font-size:16px"><?= $rule['icon'] ?></span>
                        <strong><?= htmlspecialchars($cropName) ?></strong>
                    </td>
                    <td>
                        <?php foreach ($rule['good'] as $g): ?>
                            <span class="badge badge-success" style="margin:2px;font-size:10px">
                                <?= htmlspecialchars($g) ?>
                            </span>
                        <?php endforeach; ?>
                    </td>
                    <td>
                        <?php foreach ($rule['avoid'] as $a): ?>
                            <span class="badge badge-danger" style="margin:2px;font-size:10px">
                                <?= htmlspecialchars($a) ?>
                            </span>
                        <?php endforeach; ?>
                    </td>
                   <td style="font-size:12px;color:var(--text3);max-width:260px">
    <?= htmlspecialchars($rule['reason']) ?>
</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php endif; ?>

<?php include 'layout_end.php'; ?>