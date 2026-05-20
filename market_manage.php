<?php
require_once 'db.php';
requireLogin();

if ($_SESSION['user_role'] !== 'expert') {
    header("Location: dashboard.php");
    exit;
}

$user = currentUser($conn);
$uid = $user['id'];

/* =========================
   INSERT MARKET PRICE
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_price'])) {

    $crop_id     = (int)$_POST['crop_id'];
    $location_id = (int)$_POST['location_id'];
    $price       = (float)$_POST['price'];
    $unit        = $_POST['unit'] ?? 'kg';
    $source      = clean($conn, $_POST['source'] ?? '');
    $date        = $_POST['date'] ?? date('Y-m-d');

    if ($crop_id && $location_id && $price) {

        $stmt = $conn->prepare("
            INSERT INTO market_prices 
            (crop_id, location_id, price, unit, source, date)
            VALUES (?,?,?,?,?,?)
        ");

        $stmt->bind_param(
            "iiddss",
            $crop_id,
            $location_id,
            $price,
            $unit,
            $source,
            $date
        );

        $stmt->execute();
    }
}

/* =========================
   DATA FOR FORM
========================= */
$crops = $conn->query("SELECT id, name FROM crops ORDER BY name");
$locations = $conn->query("SELECT id, division, district FROM locations ORDER BY division, district");

/* =========================
   FILTER
========================= */
$filter_location = $_GET['location_id'] ?? 'all';
$today = date('Y-m-d');

$location_sql = "";

if ($filter_location !== 'all') {
    $filter_location = (int)$filter_location;
    $location_sql = " AND mp.location_id = $filter_location ";
}

/* =========================
   NO DUPLICATE + LATEST ONLY
========================= */
$prices = $conn->query("
    SELECT mp.*, c.name AS crop_name, l.district, l.division
    FROM market_prices mp
    JOIN crops c ON mp.crop_id = c.id
    JOIN locations l ON mp.location_id = l.id
    WHERE mp.id IN (
        SELECT MAX(id)
        FROM market_prices
        WHERE date = '$today'
        GROUP BY crop_id, location_id
    )
    $location_sql
    ORDER BY l.division, l.district
");

include 'layout.php';
?>

<div class="page-body">

<!-- ================= ADD PRICE ================= -->
<div class="card">
    <div class="card-title">💰 Add Market Price</div>

    <form method="post" style="display:flex; flex-direction:column; gap:12px">

        <select name="crop_id" required>
            <option value="">🌾 Select Crop</option>
            <?php while($c = $crops->fetch_assoc()): ?>
                <option value="<?= $c['id'] ?>">
                    <?= htmlspecialchars($c['name']) ?>
                </option>
            <?php endwhile; ?>
        </select>

        <select name="location_id" required>
            <option value="">📍 Select Location</option>
            <?php while($l = $locations->fetch_assoc()): ?>
                <option value="<?= $l['id'] ?>">
                    <?= $l['division'] ?> - <?= $l['district'] ?>
                </option>
            <?php endwhile; ?>
        </select>

        <input type="number" step="0.01" name="price" placeholder="Price" required>

        <select name="unit">
            <option value="kg">kg</option>
            <option value="quintal">quintal</option>
            <option value="ton">ton</option>
        </select>

        <input type="text" name="source" placeholder="Source">
        <input type="date" name="date" value="<?= date('Y-m-d') ?>">

        <button class="btn btn-primary" name="add_price">Add Price</button>
    </form>
</div>

<!-- ================= FILTER ================= -->
<div class="card" style="margin-top:20px">
    <div class="card-title">📍 Filter by Location</div>

    <form method="GET" style="display:flex; gap:10px; align-items:center">

        <select name="location_id">
            <option value="all">🌍 All Locations</option>

            <?php
            $loc2 = $conn->query("SELECT id, division, district FROM locations ORDER BY district");
            while($l = $loc2->fetch_assoc()):
            ?>
                <option value="<?= $l['id'] ?>"
                    <?= ($filter_location == $l['id']) ? 'selected' : '' ?>>
                    <?= $l['division'] ?> - <?= $l['district'] ?>
                </option>
            <?php endwhile; ?>

        </select>

        <button class="btn btn-outline">Filter</button>
    </form>
</div>

<!-- ================= TODAY PRICE ================= -->
<div class="card" style="margin-top:20px">

    <div class="card-title">📊 Today Market Price </div>

    <?php if ($prices && $prices->num_rows > 0): ?>
        <?php while($p = $prices->fetch_assoc()): ?>
            <div style="padding:10px;border-bottom:1px solid var(--border)">

                <b><?= htmlspecialchars($p['crop_name']) ?></b>
                → <?= $p['price'] ?> / <?= $p['unit'] ?>

                <div style="font-size:13px;opacity:.8">
                    📍 <?= $p['division'] ?> - <?= $p['district'] ?>
                </div>

                <small style="opacity:.7">
                    <?= $p['source'] ?> • <?= $p['date'] ?>
                </small>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p style="opacity:.7">No data found for today.</p>
    <?php endif; ?>

</div>

</div>

<?php include 'layout_end.php'; ?>