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
   CREATE ADVISORY
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {

    $title   = clean($conn, $_POST['title'] ?? '');
    $content = clean($conn, $_POST['content'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $is_urgent = isset($_POST['is_urgent']) ? 1 : 0;
    $location_id = ($_POST['location_id'] !== '') ? (int)$_POST['location_id'] : null;

    if ($title && $content) {

        $stmt = $conn->prepare("
            INSERT INTO advisory_feed 
            (title, content, category, is_urgent, location_id) 
            VALUES (?,?,?,?,?)
        ");

        $stmt->bind_param(
            "sssii",
            $title,
            $content,
            $category,
            $is_urgent,
            $location_id
        );

        $stmt->execute();
    }
}

/* =========================
   FILTER MODE (NEW 3 MODE)
========================= */
$mode = $_GET['mode'] ?? 'mixed'; // mixed | global | location
$filter_location = $_GET['location_id'] ?? '';

$where = "WHERE 1=1";

/* 🌍 GLOBAL ONLY */
if ($mode === 'global') {
    $where .= " AND location_id IS NULL";
}

/* 📍 LOCATION ONLY */
elseif ($mode === 'location' && $filter_location !== '') {
    $filter_location = (int)$filter_location;
    $where .= " AND location_id = $filter_location";
}

/* 🌎 MIXED (DEFAULT BEST UX) */
elseif ($mode === 'mixed') {

    if ($filter_location !== '' && $filter_location !== 'all') {
        $filter_location = (int)$filter_location;
        $where .= " AND (location_id = $filter_location OR location_id IS NULL)";
    }
}

/* =========================
   DATA
========================= */
$advisories = $conn->query("
    SELECT * FROM advisory_feed
    $where
    ORDER BY is_urgent DESC, created_at DESC
");

/* =========================
   LOCATIONS
========================= */
$locations = $conn->query("SELECT id, division, district FROM locations ORDER BY division, district");

$loc_list = [];
while ($l = $locations->fetch_assoc()) {
    $loc_list[] = $l;
}

include 'layout.php';
?>

<div class="page-body">

<!-- ================= CREATE ================= -->
<div class="card">
    <div class="card-title">📢 Create Advisory</div>

    <form method="post" style="display:flex; flex-direction:column; gap:10px">

        <input type="text" name="title" placeholder="Title" required>

        <textarea name="content" placeholder="Write advisory..." rows="4" required></textarea>

        <select name="category">
            <option value="general">General</option>
            <option value="weather">Weather</option>
            <option value="pest">Pest</option>
            <option value="market">Market</option>
        </select>

        <select name="location_id">
            <option value="">🌍 Global Advisory</option>
            <?php foreach ($loc_list as $l): ?>
                <option value="<?= $l['id'] ?>">
                    <?= $l['division'] ?> - <?= $l['district'] ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>
            <input type="checkbox" name="is_urgent">
            Mark as Urgent
        </label>

        <button class="btn btn-primary" name="create">Publish</button>
    </form>
</div>

<!-- ================= FILTER PANEL ================= -->
<div class="card" style="margin-top:20px">

<form method="GET" style="display:flex; gap:10px; flex-wrap:wrap">

    <!-- MODE -->
    <select name="mode">
        <option value="mixed" <?= $mode=='mixed'?'selected':'' ?>>🌎 Mixed (Recommended)</option>
        <option value="global" <?= $mode=='global'?'selected':'' ?>>🌍 Global Only</option>
        <option value="location" <?= $mode=='location'?'selected':'' ?>>📍 Location Only</option>
    </select>

    <!-- LOCATION -->
    <select name="location_id">
        <option value="all">All Locations</option>
        <?php foreach ($loc_list as $l): ?>
            <option value="<?= $l['id'] ?>"
                <?= ($filter_location == $l['id']) ? 'selected' : '' ?>>
                <?= $l['division'] ?> - <?= $l['district'] ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button class="btn btn-outline">Apply</button>
</form>

</div>

<!-- ================= LIST ================= -->
<div class="card" style="margin-top:20px">

<div class="card-title">📢 Advisories</div>

<?php while ($a = $advisories->fetch_assoc()): ?>
    <div style="padding:10px;border-bottom:1px solid var(--border)">

        <b>
            <?= htmlspecialchars($a['title']) ?>
            <?= $a['is_urgent'] ? '🔥' : '' ?>
        </b>

        <div style="font-size:13px;opacity:.8">
            <?= ucfirst($a['category']) ?>
        </div>

        <p><?= htmlspecialchars($a['content']) ?></p>

        <small style="opacity:.7">
            <?= $a['location_id'] ? '📍 Location-based' : '🌍 Global' ?>
            • <?= $a['created_at'] ?>
        </small>

    </div>
<?php endwhile; ?>

</div>

</div>

<?php include 'layout_end.php'; ?>