<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'My Crops';
$activeNav = 'crops';

$user = currentUser($conn);
$uid = (int)$user['id'];

$success = '';
$error   = '';

/* =====================================================
   STATUS UPDATE
===================================================== */

if (isset($_GET['status'], $_GET['cid'])) {

    $cid = (int)$_GET['cid'];
    $st  = clean($conn, $_GET['status']);

    if (in_array($st, ['growing','harvested','failed'])) {

        $conn->query("
            UPDATE farmer_crops
            SET status='$st'
            WHERE id=$cid
            AND user_id=$uid
        ");

        $success = 'Status updated!';
    }
}

/* =====================================================
   DELETE CROP
===================================================== */

if (isset($_GET['delete'])) {

    $cid = (int)$_GET['delete'];

    $conn->query("
        DELETE FROM farmer_crops
        WHERE id=$cid
        AND user_id=$uid
    ");

    $success = 'Crop removed.';
}

/* =====================================================
   ADD NEW CROP
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $crop_id  = (int)($_POST['crop_id'] ?? 0);
    $seed_id  = (int)($_POST['seed_id'] ?? 0);
    $field_id = (int)($_POST['field_id'] ?? 0);
    $planted  = clean($conn, $_POST['planted_date'] ?? '');

    if (!$crop_id || !$seed_id || !$planted) {

        $error = 'Crop, seed and planting date are required.';

    } else {

        $seed = $conn->query("
            SELECT harvest_days
            FROM seeds
            WHERE id=$seed_id
        ")->fetch_assoc();

        $days = (int)($seed['harvest_days'] ?? 0);

        $harvest = date(
            'Y-m-d',
            strtotime($planted . " +$days days")
        );

        $fv = $field_id > 0 ? $field_id : 'NULL';

        $conn->query("
            INSERT INTO farmer_crops
            (
                user_id,
                crop_id,
                field_id,
                seed_id,
                planted_date,
                expected_harvest
            )
            VALUES
            (
                $uid,
                $crop_id,
                $fv,
                $seed_id,
                '$planted',
                '$harvest'
            )
        ");

        $cropInsertId = $conn->insert_id;

        /* =========================================
           DEFAULT ACTIVITIES
        ========================================= */

        $conn->query("
            INSERT INTO activity_schedule
            (
                user_id,
                farmer_crop_id,
                day_number,
                activity
            )
            VALUES
            ($uid, $cropInsertId, 7, 'Apply fertilizer'),
            ($uid, $cropInsertId, 15, 'Pest monitoring'),
            ($uid, $cropInsertId, 25, 'Irrigation check'),
            ($uid, $cropInsertId, 40, 'Weeding')
        ");

        /* =========================================
           NOTIFICATION
        ========================================= */

        $conn->query("
            INSERT INTO notifications
            (
                user_id,
                title,
                message,
                type
            )
            VALUES
            (
                $uid,
                'Crop Planted',
                'You planted a new crop.',
                'advisory'
            )
        ");

        $success = 'Crop added successfully!';
    }
}

$showForm =
    isset($_GET['action'])
    && $_GET['action'] === 'add';

/* =====================================================
   FETCH CROPS
===================================================== */

$myCrops = $conn->query("
SELECT

    fc.*,

    c.name AS crop_name,

    f.name AS field_name,

    s.name AS seed_name,
    s.type AS seed_type

FROM farmer_crops fc

JOIN crops c
ON fc.crop_id = c.id

LEFT JOIN fields f
ON fc.field_id = f.id

LEFT JOIN seeds s
ON fc.seed_id = s.id

WHERE fc.user_id = $uid

ORDER BY fc.created_at DESC
");

/* =====================================================
   CROP DATA
===================================================== */

$allCrops = $conn->query("
SELECT *
FROM crops
ORDER BY name
");

$myFields = $conn->query("
SELECT *
FROM fields
WHERE user_id=$uid
ORDER BY name
");

/* =====================================================
   SEEDS JSON
===================================================== */

$seedData = $conn->query("
SELECT *
FROM seeds
ORDER BY crop_id, name
");

$temp = [];

while ($s = $seedData->fetch_assoc()) {

    $temp[$s['crop_id']][] = [

        'id'   => $s['id'],
        'name' => $s['name'],
        'type' => $s['type']

    ];
}

$seedsJson = json_encode(
    $temp,
    JSON_UNESCAPED_UNICODE
);

include 'layout.php';
?>

<?php if ($success): ?>

<div class="alert alert-success">
    ✅ <?= htmlspecialchars($success) ?>
</div>

<?php endif; ?>

<?php if ($error): ?>

<div class="alert alert-error">
    ⚠️ <?= htmlspecialchars($error) ?>
</div>

<?php endif; ?>

<!-- =====================================================
     TOP BUTTON
===================================================== -->

<div style="
    display:flex;
    justify-content:flex-end;
    margin-bottom:20px;
">

    <a
        href="crops.php?action=add"
        class="btn btn-primary"
    >
        + Plant Crop
    </a>

</div>

<!-- =====================================================
     ADD FORM
===================================================== -->

<?php if ($showForm): ?>

<div class="card">

    <div class="card-title">
        🌱 Record New Planting
    </div>

    <form method="post">

        <div class="grid-2">

            <!-- CROP -->

            <div class="form-group">

                <label>Crop *</label>

                <select
                    name="crop_id"
                    id="cropSelect"
                    required
                >

                    <option value="">
                        — Select Crop —
                    </option>

                    <?php while ($c = $allCrops->fetch_assoc()): ?>

                        <option value="<?= $c['id'] ?>">

                            <?= htmlspecialchars($c['name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <!-- SEED -->

            <div class="form-group">

                <label>Seed *</label>

                <select
                    name="seed_id"
                    id="seedSelect"
                    required
                >

                    <option value="">
                        — Select Seed —
                    </option>

                </select>

            </div>

            <!-- FIELD -->

            <div class="form-group">

                <label>Field</label>

                <select name="field_id">

                    <option value="">
                        — No field —
                    </option>

                    <?php while ($fld = $myFields->fetch_assoc()): ?>

                        <option value="<?= $fld['id'] ?>">

                            <?= htmlspecialchars($fld['name']) ?>

                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

            <!-- DATE -->

            <div class="form-group">

                <label>Plant Date *</label>

                <input
                    type="date"
                    name="planted_date"
                    max="<?= date('Y-m-d') ?>"
                    required
                >

            </div>

        </div>

        <button class="btn btn-primary">
            🌱 Plant Crop
        </button>

    </form>

</div>

<script>

const seeds = <?= $seedsJson ?>;

document
.getElementById('cropSelect')
.addEventListener('change', function () {

    const seedSelect =
        document.getElementById('seedSelect');

    seedSelect.innerHTML =
        '<option value="">— Select Seed —</option>';

    if (seeds[this.value]) {

        seeds[this.value].forEach(seed => {

            seedSelect.innerHTML += `
                <option value="${seed.id}">
                    ${seed.name} (${seed.type})
                </option>
            `;
        });
    }
});

</script>

<?php endif; ?>

<!-- =====================================================
     MY CROPS
===================================================== -->

<div class="card">

<div class="card-title">
    🌾 My Crop Records
</div>

<?php if ($myCrops->num_rows === 0): ?>

<div class="empty-state">

    <div class="empty-icon">
        🌾
    </div>

    <p>No crops yet</p>

</div>

<?php else: ?>

<?php while ($cr = $myCrops->fetch_assoc()): ?>

<?php

$s = $cr['status'] ?? 'growing';

$scls =
    $s === 'growing'
    ? 'badge-success'
    : (
        $s === 'harvested'
        ? 'badge-info'
        : 'badge-danger'
    );

?>

<div style="
    border:1px solid var(--border);
    border-radius:16px;
    padding:20px;
    margin-bottom:20px;
    background:var(--surface);
">

    <!-- TOP -->

    <div style="
        display:flex;
        justify-content:space-between;
        align-items:flex-start;
        gap:20px;
        flex-wrap:wrap;
    ">

        <div>

            <div style="
                font-size:22px;
                font-weight:800;
                margin-bottom:6px;
            ">

                🌾 <?= htmlspecialchars($cr['crop_name']) ?>

            </div>

            <div style="color:var(--text2)">
                Seed:
                <b><?= htmlspecialchars($cr['seed_name'] ?? '-') ?></b>
            </div>

            <div style="color:var(--text2)">
                Field:
                <b><?= htmlspecialchars($cr['field_name'] ?? '-') ?></b>
            </div>

            <div style="color:var(--text2)">
                Planted:
                <b><?= $cr['planted_date'] ?></b>
            </div>

            <div style="color:var(--text2)">
                Harvest:
                <b><?= $cr['expected_harvest'] ?></b>
            </div>

        </div>

        <div>

            <span class="badge <?= $scls ?>">
                <?= ucfirst($s) ?>
            </span>

        </div>

    </div>

    <!-- ACTIONS -->

    <div style="
        display:flex;
        gap:10px;
        flex-wrap:wrap;
        margin-top:16px;
    ">

        <a
            href="activity_schedule.php?crop=<?= $cr['id'] ?>"
            class="btn btn-primary btn-sm"
        >
            📅 Activities
        </a>

        <a
            href="crops.php?status=growing&cid=<?= $cr['id'] ?>"
            class="btn btn-success btn-sm"
        >
            🌱 Growing
        </a>

        <a
            href="crops.php?status=harvested&cid=<?= $cr['id'] ?>"
            class="btn btn-info btn-sm"
        >
            🌾 Harvested
        </a>

        <a
            href="crops.php?status=failed&cid=<?= $cr['id'] ?>"
            class="btn btn-warning btn-sm"
        >
            ❌ Failed
        </a>

        <a
            href="crops.php?delete=<?= $cr['id'] ?>"
            class="btn btn-danger btn-sm"
            onclick="return confirm('Delete crop?')"
        >
            🗑️ Delete
        </a>

    </div>

</div>

<?php endwhile; ?>

<?php endif; ?>

</div>

<?php include 'layout_end.php'; ?>