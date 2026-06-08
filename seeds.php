<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Seed Finder';
$activeNav = 'seeds';

$selectedCrop = (int)($_GET['crop_id'] ?? 0);
$filterPR = isset($_GET['pest_resistant']);

$allCrops = $conn->query("
SELECT *
FROM crops
ORDER BY name
");

$seeds = null;
$cropName = '';

if ($selectedCrop > 0) {

    $cr = $conn->query("
    SELECT *
    FROM crops
    WHERE id=$selectedCrop
    ")->fetch_assoc();

    $cropName = $cr['name'] ?? '';

    $where = "crop_id=$selectedCrop";

    if ($filterPR) {
        $where .= " AND pest_resistance=1";
    }

    $seeds = $conn->query("
    SELECT *
    FROM seeds
    WHERE $where
    ORDER BY pest_resistance DESC, name
    ");
}

include 'layout.php';
?>

<div class="alert alert-info">
🌱 Select a crop to find the best seed varieties.
Filter by pest resistance and yield potential.
</div>

<!-- FILTER FORM -->
<div class="card" style="margin-bottom:20px">

    <div class="card-title">
        🔍 Find Seeds
    </div>

    <form
        method="get"
        style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end"
    >

        <!-- CROP -->
        <div
            class="form-group"
            style="margin:0;flex:1;min-width:200px"
        >

            <label>Crop</label>

            <select name="crop_id">

                <option value="">
                    — Select Crop —
                </option>

<?php while ($c = $allCrops->fetch_assoc()): ?>

    <option
        value="<?= $c['id'] ?>"
        <?= $c['id'] == $selectedCrop ? 'selected' : '' ?>
    >

        <?= htmlspecialchars($c['name']) ?>
        

    </option>

<?php endwhile; ?>

            </select>

        </div>

        <!-- FILTER -->
        <div
            style="
            display:flex;
            align-items:center;
            gap:8px;
            padding-bottom:2px
            "
        >

            <input
                type="checkbox"
                id="pr"
                name="pest_resistant"
                <?= $filterPR ? 'checked' : '' ?>
                style="width:18px;height:18px"
            >

            <label
                for="pr"
                style="margin:0;cursor:pointer"
            >
                Pest Resistant Only
            </label>

        </div>

        <button type="submit" class="btn btn-primary">
            Search Seeds →
        </button>

    </form>

</div>

<!-- RESULTS -->
<?php if ($selectedCrop > 0 && $seeds): ?>

<div class="card">

    <div class="card-title">
        🌱 Seeds for <?= htmlspecialchars($cropName) ?>
    </div>

<?php if ($seeds->num_rows === 0): ?>

    <div class="empty-state">

        <div class="empty-icon">
            🌱
        </div>

        <p>
            No seeds found matching your filter.
            Try removing the pest-resistant filter.
        </p>

    </div>

<?php else: ?>

<div class="grid-3">

<?php
while ($s = $seeds->fetch_assoc()):

$typeCls =
    $s['type'] === 'Hybrid'
    ? 'badge-warn'
    : (
        $s['type'] === 'HYV'
        ? 'badge-success'
        : 'badge-gray'
    );
?>

<div
    class="card"
    style="
    padding:20px;
    border:2px solid var(--border)
    "
>

    <!-- TOP -->
    <div
        style="
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        margin-bottom:12px
        "
    >

        <div style="font-size:32px">
            🌱
        </div>

        <div
            style="
            display:flex;
            flex-direction:column;
            gap:4px;
            align-items:flex-end
            "
        >

            <span class="badge <?= $typeCls ?>">

                <?= htmlspecialchars($s['type'] ?? 'Local') ?>

            </span>

<?php if ($s['pest_resistance']): ?>

    <span class="badge badge-success">
        🛡️ Pest Resistant
    </span>

<?php endif; ?>

        </div>

    </div>

    <!-- NAME -->
    <div
        style="
        font-family:'Syne',sans-serif;
        font-weight:700;
        font-size:16px;
        margin-bottom:10px
        "
    >

        <?= htmlspecialchars($s['name']) ?>

    </div>

    <!-- YIELD -->
<?php if ($s['yield_info']): ?>

<div
    style="
    display:flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    color:var(--text2);
    margin-bottom:8px
    "
>

    <span>📊</span>

    <span>
        Yield:
        <?= htmlspecialchars($s['yield_info']) ?>
    </span>

</div>

<?php endif; ?>

    <!-- HARVEST DAYS -->
<?php if (!empty($s['harvest_days'])): ?>

<div
    style="
    display:flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    color:var(--text2);
    margin-bottom:8px
    "
>

    <span>⏳</span>

    <span>
        Harvest Time:
        <?= (int)$s['harvest_days'] ?> days
    </span>

</div>

<?php endif; ?>

    <!-- RESISTANCE -->
<div
    style="
    display:flex;
    align-items:center;
    gap:6px;
    font-size:13px;
    color:var(--text2);
    margin-bottom:8px
    "
>

<span>
<?= $s['pest_resistance']
    ? '🛡️ Good pest resistance'
    : '⚠️ Low pest resistance'
?>
</span>

</div>

<hr class="divider">

<!-- FOOTER -->
<div
    style="
    display:flex;
    gap:8px;
    margin-top:8px
    "
>

<?php if ($s['type'] === 'Hybrid'): ?>

    <span style="font-size:12px;color:var(--text3)">
        ⚡ High yield potential
    </span>

<?php elseif ($s['type'] === 'HYV'): ?>

    <span style="font-size:12px;color:var(--text3)">
        🏆 Improved variety
    </span>

<?php else: ?>

    <span style="font-size:12px;color:var(--text3)">
        💰 Cost-effective
    </span>

<?php endif; ?>

</div>

</div>

<?php endwhile; ?>

</div>

<div
    class="alert alert-warn"
    style="margin-top:16px"
>
💡 Always buy certified seeds from
government-approved sources or reputable dealers.
</div>

<?php endif; ?>

</div>

<?php endif; ?>

<!-- GUIDE -->
<div class="card" style="margin-top:20px">

<div class="card-title">
    📚 Seed Type Guide
</div>

<div class="grid-3">

    <!-- HYV -->
    <div
        style="
        padding:16px;
        background:var(--surface2);
        border-radius:var(--radius-sm)
        "
    >

        <div style="font-size:24px;margin-bottom:8px">
            🏆
        </div>

        <div
            style="
            font-family:'Syne',sans-serif;
            font-weight:700;
            margin-bottom:6px
            "
        >
            HYV (High Yield Variety)
        </div>

        <div style="font-size:13px;color:var(--text2)">
            Government-developed improved varieties.
            Good yield, affordable seeds,
            can save seeds for next season.
        </div>

    </div>

    <!-- HYBRID -->
    <div
        style="
        padding:16px;
        background:var(--surface2);
        border-radius:var(--radius-sm)
        "
    >

        <div style="font-size:24px;margin-bottom:8px">
            ⚡
        </div>

        <div
            style="
            font-family:'Syne',sans-serif;
            font-weight:700;
            margin-bottom:6px
            "
        >
            Hybrid
        </div>

        <div style="font-size:13px;color:var(--text2)">
            Cross-bred for maximum yield.
            Highest production but must buy fresh seeds each season.
        </div>

    </div>

    <!-- LOCAL -->
    <div
        style="
        padding:16px;
        background:var(--surface2);
        border-radius:var(--radius-sm)
        "
    >

        <div style="font-size:24px;margin-bottom:8px">
            🌿
        </div>

        <div
            style="
            font-family:'Syne',sans-serif;
            font-weight:700;
            margin-bottom:6px
            "
        >
            Local Variety
        </div>

        <div style="font-size:13px;color:var(--text2)">
            Traditional seeds adapted to local conditions.
            Lower yield but cost-effective and can save seeds.
        </div>

    </div>

</div>

</div>

<?php include 'layout_end.php'; ?>