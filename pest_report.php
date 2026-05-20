<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Pest Reports';
$activeNav = 'pest_report';

$user = currentUser($conn);
$uid = (int)$user['id'];

$success = $error = '';

/* =========================
   DELETE
========================= */
if (isset($_GET['delete'])) {

    $rid = (int)$_GET['delete'];

    $conn->query("DELETE FROM pest_images WHERE report_id=$rid");
    $conn->query("DELETE FROM pest_reviews WHERE report_id=$rid");

    $conn->query("DELETE FROM pest_reports WHERE id=$rid AND user_id=$uid");

    $success = "Report deleted successfully.";
}

/* =========================
   LOAD EDIT DATA
========================= */
$editData = null;

if (isset($_GET['action']) && $_GET['action'] === 'edit') {

    $eid = (int)$_GET['id'];

    $res = $conn->query("
        SELECT * FROM pest_reports
        WHERE id=$eid AND user_id=$uid
    ");

    $editData = $res->fetch_assoc();
}

/* =========================
   SUBMIT (ADD / UPDATE)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $id       = (int)($_POST['id'] ?? 0);
    $crop_id  = (int)($_POST['crop_id'] ?? 0);
    $pest_id  = (int)($_POST['pest_id'] ?? 0);
    $field_id = (int)($_POST['field_id'] ?? 0);

    $severity    = clean($conn, $_POST['severity'] ?? '');
    $description = clean($conn, $_POST['description'] ?? '');

    $latitude  = $_POST['latitude'] ?? '';
    $longitude = $_POST['longitude'] ?? '';
    $full_address = clean($conn, $_POST['full_address'] ?? '');

    /* ⭐ ADDED */
    $district = clean($conn, $_POST['district'] ?? '');

    if (!$crop_id || !$pest_id || !$severity || !$latitude || !$longitude || !$full_address) {
        $error = "All fields including GPS are required.";
    } else {

        $fv = $field_id > 0 ? $field_id : 'NULL';

        /* =========================
           UPDATE
        ========================= */
        if ($id > 0) {

            $conn->query("
                UPDATE pest_reports SET
                    crop_id=$crop_id,
                    pest_id=$pest_id,
                    field_id=$fv,
                    severity='$severity',
                    description='$description',
                    latitude='$latitude',
                    longitude='$longitude',
                    full_address='$full_address',
                    district='$district'
                WHERE id=$id AND user_id=$uid
            ");

            $success = "Report updated successfully!";
        }

        /* =========================
           INSERT
        ========================= */
        else {

            $conn->query("
                INSERT INTO pest_reports (
                    user_id,
                    crop_id,
                    pest_id,
                    field_id,
                    severity,
                    description,
                    latitude,
                    longitude,
                    full_address,
                    district
                )
                VALUES (
                    $uid,
                    $crop_id,
                    $pest_id,
                    $fv,
                    '$severity',
                    '$description',
                    '$latitude',
                    '$longitude',
                    '$full_address',
                    '$district'
                )
            ");

            /* ⭐ NOTIFICATION (ADDED) */
            $conn->query("
                INSERT INTO notifications (
                    user_id,
                    title,
                    message,
                    type,
                    is_read,
                    created_at
                )
                VALUES (
                    $uid,
                    'New Pest Report Submitted',
                    'Your pest report has been submitted successfully from $district.',
                    'pest_report',
                    0,
                    NOW()
                )
            ");

            $success = "Pest report submitted successfully!";
        }

        /* IMAGE UPLOAD */
        if (!empty($_FILES['image']['name'])) {

            $rid = $id > 0 ? $id : $conn->insert_id;

            $dir = "assets/images/pests/";
            if (!is_dir($dir)) mkdir($dir, 0777, true);

            $file = time() . "_" . basename($_FILES["image"]["name"]);
            $path = $dir . $file;

            move_uploaded_file($_FILES["image"]["tmp_name"], $path);

            $conn->query("
                INSERT INTO pest_images (report_id, image_url)
                VALUES ($rid, '$path')
            ");
        }
    }
}

$showForm =
    isset($_GET['action']) &&
    ($_GET['action'] === 'add' || $_GET['action'] === 'edit');

/* =========================
   FETCH REPORTS
========================= */
$myReports = $conn->query("
SELECT pr.*,
       c.name AS crop_name,
       p.name AS pest_name,
       f.name AS field_name,
       (SELECT image_url FROM pest_images WHERE report_id=pr.id LIMIT 1) AS img,
       r.advice AS expert_advice
FROM pest_reports pr
JOIN crops c ON pr.crop_id=c.id
JOIN pests p ON pr.pest_id=p.id
LEFT JOIN fields f ON pr.field_id=f.id
LEFT JOIN pest_reviews r ON pr.id = r.report_id
WHERE pr.user_id=$uid
ORDER BY pr.created_at DESC
");

$allCrops = $conn->query("SELECT * FROM crops ORDER BY name");
$allPests = $conn->query("SELECT * FROM pests ORDER BY name");
$myFields = $conn->query("SELECT * FROM fields WHERE user_id=$uid ORDER BY name");

include 'layout.php';
?>

<?php if ($success): ?>
<div class="alert alert-success">✅ <?= $success ?></div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error">⚠️ <?= $error ?></div>
<?php endif; ?>

<div style="display:flex;justify-content:flex-end;margin-bottom:20px">
    <a href="pest_report.php?action=add" class="btn btn-primary">+ Report Pest</a>
</div>

<?php if ($showForm): ?>
<div class="card">
<div class="card-title">
    <?= isset($editData) ? "✏️ Edit Report" : "🐛 Submit Report" ?>
</div>

<form method="post" enctype="multipart/form-data">

    <input type="hidden" name="id" value="<?= $editData['id'] ?? 0 ?>">
    <input type="hidden" name="latitude" id="lat">
    <input type="hidden" name="longitude" id="lng">
    <input type="hidden" name="full_address" id="full_address">

    <!-- ⭐ ADDED -->
    <input type="hidden" name="district" id="district">

    <div class="grid-2">

        <div class="form-group">
            <label>Crop</label>
            <select name="crop_id">
                <?php while ($c = $allCrops->fetch_assoc()): ?>
                    <option value="<?= $c['id'] ?>"
                        <?= isset($editData['crop_id']) && $editData['crop_id']==$c['id']?'selected':'' ?>>
                        <?= $c['name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Pest</label>
            <select name="pest_id">
                <?php while ($p = $allPests->fetch_assoc()): ?>
                    <option value="<?= $p['id'] ?>"
                        <?= isset($editData['pest_id']) && $editData['pest_id']==$p['id']?'selected':'' ?>>
                        <?= $p['name'] ?>
                    </option>
                <?php endwhile; ?>
            </select>
        </div>

        <div class="form-group">
            <label>Severity</label>
            <select name="severity">
                <option <?= ($editData['severity']??'')=='Low'?'selected':'' ?>>Low</option>
                <option <?= ($editData['severity']??'')=='Medium'?'selected':'' ?>>Medium</option>
                <option <?= ($editData['severity']??'')=='High'?'selected':'' ?>>High</option>
            </select>
        </div>

        <div class="form-group">
            <label>Description</label>
            <textarea name="description"><?= $editData['description'] ?? '' ?></textarea>
        </div>

        <div class="form-group">
            <label>Image</label>
            <input type="file" name="image">
        </div>

    </div>

    <button type="button" class="btn btn-outline" onclick="getLocation()">
        📍 Detect My Exact Location
    </button>

    <div id="gpsStatus" style="margin:10px 0;padding:12px;border-radius:8px;background:#f5f5f5;">
        ⏳ Waiting for location...
    </div>

    <button class="btn btn-primary">
        <?= isset($editData) ? "Update" : "Submit" ?>
    </button>

</form>
</div>
<?php endif; ?>

<div class="card">
<div class="card-title">📋 My Reports</div>

<div class="grid-3">

<?php while ($r = $myReports->fetch_assoc()): ?>

<div class="card">

    <?php if ($r['img']): ?>
    <img src="<?= $r['img'] ?>" style="width:100%;height:200px;object-fit:cover;">
<?php else: ?>
    <div style="height:200px;display:flex;align-items:center;justify-content:center;background:#eee;">
        📷 No Image
    </div>
<?php endif; ?>

    <b><?= $r['crop_name'] ?></b><br>
    🐛 <?= $r['pest_name'] ?> | <?= $r['severity'] ?><br>
    📍 <?= $r['full_address'] ?><br>
    <?php if (!$r['expert_advice']): ?>
        <span style="color:orange;">⏳ Pending Review</span>
    <?php else: ?>
        <div style="background:#e8f5e9;padding:8px;">
            🧠 <?= $r['expert_advice'] ?>
        </div>
    <?php endif; ?>

    <div style="margin-top:10px;display:flex;gap:10px;">
        <a href="pest_report.php?action=edit&id=<?= $r['id'] ?>" class="btn btn-sm">Edit</a>

        <a href="pest_report.php?delete=<?= $r['id'] ?>"
           onclick="return confirm('Delete?')"
           class="btn btn-danger btn-sm">
           Delete
        </a>
    </div>

</div>

<?php endwhile; ?>

</div>
</div>

<script>
function getLocation() {

    navigator.geolocation.getCurrentPosition(async function(pos){

        const lat = pos.coords.latitude;
        const lng = pos.coords.longitude;

        document.getElementById("lat").value = lat;
        document.getElementById("lng").value = lng;

        let res = await fetch(
            `https://nominatim.openstreetmap.org/reverse?format=jsonv2&lat=${lat}&lon=${lng}`
        );

        let data = await res.json();

        let addr = data.display_name;
        document.getElementById("full_address").value = addr;

        /* ⭐ ADDED district */
        let district =
            data.address?.state_district ||
            data.address?.county ||
            data.address?.state ||
            "";

        document.getElementById("district").value = district;

        document.getElementById("gpsStatus").innerHTML =
            "✅ " + district + " | " + addr;

    });
}
</script>

<?php include 'layout_end.php'; ?>