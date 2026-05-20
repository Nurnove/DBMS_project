<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Activity Schedule';
$activeNav = 'crops';

$user = currentUser($conn);
$uid  = (int)$user['id'];

$cropId = (int)($_GET['crop'] ?? 0);

if (!$cropId) {
    header("Location:crops.php");
    exit;
}

/* =========================================
   GET CROP
========================================= */

$crop = $conn->query("
SELECT
    fc.*,
    c.name AS crop_name
FROM farmer_crops fc
JOIN crops c ON fc.crop_id=c.id
WHERE fc.id=$cropId
AND fc.user_id=$uid
")->fetch_assoc();

if (!$crop) {
    die("Crop not found");
}

$success = '';
$error   = '';

/* =========================================
   ADD ACTIVITY
========================================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $day  = (int)$_POST['day_number'];
    $act  = clean($conn, $_POST['activity']);

    if (!$day || !$act) {

        $error = 'All fields required';

    } else {

        $conn->query("
        INSERT INTO activity_schedule
        (
            user_id,
            farmer_crop_id,
            day_number,
            activity
        )
        VALUES
        (
            $uid,
            $cropId,
            $day,
            '$act'
        )
        ");

        $success = 'Activity added!';
    }
}

/* =========================================
   DELETE
========================================= */

if (isset($_GET['delete'])) {

    $did = (int)$_GET['delete'];

    $conn->query("
    DELETE FROM activity_schedule
    WHERE id=$did
    AND user_id=$uid
    ");

    $success = 'Deleted!';
}

/* =========================================
   GET ACTIVITIES
========================================= */

$activities = $conn->query("
SELECT *
FROM activity_schedule
WHERE farmer_crop_id=$cropId
AND user_id=$uid
ORDER BY day_number ASC
");

include 'layout.php';
?>

<?php if($success): ?>
<div class="alert alert-success">
    ✅ <?= $success ?>
</div>
<?php endif; ?>

<?php if($error): ?>
<div class="alert alert-error">
    ⚠️ <?= $error ?>
</div>
<?php endif; ?>

<div class="card">

    <div class="card-title">
        📅 <?= htmlspecialchars($crop['crop_name']) ?> Activity Schedule
    </div>

    <div style="margin-bottom:20px;color:var(--text2)">
        🌱 Plant Date:
        <b><?= $crop['planted_date'] ?></b>
    </div>

    <!-- ADD FORM -->

    <form method="post">

        <div class="grid-2">

            <div class="form-group">
                <label>Day Number</label>

                <input
                    type="number"
                    name="day_number"
                    min="1"
                    required
                >
            </div>

            <div class="form-group">
                <label>Activity</label>

                <input
                    type="text"
                    name="activity"
                    placeholder="Apply fertilizer"
                    required
                >
            </div>

        </div>

        <button class="btn btn-primary">
            ➕ Add Activity
        </button>

    </form>

</div>

<!-- LIST -->

<div class="card">

<div class="card-title">
    📋 Activity Timeline
</div>

<?php if($activities->num_rows == 0): ?>

<div class="empty-state">
    No activities yet
</div>

<?php else: ?>

<?php while($a = $activities->fetch_assoc()): ?>

<?php

$notifyDate = date(
    'Y-m-d',
    strtotime(
        $crop['planted_date']
        . " +".$a['day_number']." days"
    )
);

$today = date('Y-m-d');

$isToday = ($notifyDate == $today);

?>

<div style="
    padding:16px;
    border:1px solid var(--border);
    border-radius:14px;
    margin-bottom:14px;
    background:
    <?= $isToday ? '#fff3cd' : 'var(--surface)' ?>;
">

    <div style="
        display:flex;
        justify-content:space-between;
        gap:20px;
        flex-wrap:wrap;
    ">

        <div>

            <div style="
                font-size:18px;
                font-weight:700;
            ">
                📅 Day <?= $a['day_number'] ?>
            </div>

            <div style="
                margin-top:6px;
                color:var(--text2);
            ">
                <?= htmlspecialchars($a['activity']) ?>
            </div>

            <div style="
                margin-top:8px;
                font-size:13px;
            ">
                🔔 Activity Date:
                <b><?= $notifyDate ?></b>
            </div>

            <?php if($isToday): ?>

            <div style="
                margin-top:10px;
                color:#856404;
                font-weight:700;
            ">
                ⚠️ Today Task Reminder
            </div>

            <?php endif; ?>

        </div>

        <div>

            <a
               href="activity_schedule.php?crop=<?= $cropId ?>&delete=<?= $a['id'] ?>"
               class="btn btn-danger btn-sm"
            >
               🗑️
            </a>

        </div>

    </div>

</div>

<?php endwhile; ?>

<?php endif; ?>

</div>

<?php include 'layout_end.php'; ?>