<?php
require_once 'db.php';
requireLogin();

require_once 'config/activity_templates.php';

$pageTitle = 'Activity Schedule';
$activeNav = 'crops';

$user  = currentUser($conn);
$uid   = (int)$user['id'];
$locId = (int)($user['location_id'] ?? 0);

$cropId = (int)($_GET['crop'] ?? 0);
if (!$cropId) { header("Location: crops.php"); exit; }

/* =========================================
   GET CROP
========================================= */

$crop = $conn->query("
    SELECT fc.*, c.name AS crop_name
    FROM farmer_crops fc
    JOIN crops c ON fc.crop_id = c.id
    WHERE fc.id = $cropId AND fc.user_id = $uid
")->fetch_assoc();

if (!$crop) die("Crop not found.");

$daysElapsed = (int) floor(
    (time() - strtotime($crop['planted_date'])) / 86400
);

/* =========================================
   LATEST WEATHER
========================================= */

$weather = null;
if ($locId) {
    $w = $conn->query("
        SELECT * FROM weather_data
        WHERE location_id = $locId
        ORDER BY recorded_at DESC LIMIT 1
    ");
    if ($w) $weather = $w->fetch_assoc();
}
if (!$weather) {
    $w = $conn->query("SELECT * FROM weather_data ORDER BY recorded_at DESC LIMIT 1");
    if ($w) $weather = $w->fetch_assoc();
}

/* =========================================
   WEATHER MODIFIER FUNCTION
========================================= */

function applyWeatherLogic(string $activity, ?array $weather): array
{
    $note   = '';
    $urgent = false;

    if (!$weather) return [$activity, $note, $urgent];

    $rain  = (float)($weather['rain_probability'] ?? 0);
    $humid = (float)($weather['humidity'] ?? 0);
    $temp  = (float)($weather['temperature'] ?? 0);

    if (stripos($activity, 'irrigation') !== false || stripos($activity, 'water') !== false) {
        if ($rain >= 70)
            $note = '🌧 Heavy rain expected (' . $rain . '%). Skip irrigation today.';
        elseif ($rain >= 50)
            $note = '🌦 Moderate rain likely. Check moisture before irrigating.';
    }

    if (stripos($activity, 'fertilizer') !== false) {
        if ($temp >= 38)
            $note = '🌡 Temperature ' . $temp . '°C — Apply early morning (before 8AM) or after 5PM.';
        elseif ($rain >= 70)
            $note = '🌧 Heavy rain expected. Delay fertilizer — will wash away.';
    }

    if (stripos($activity, 'spray') !== false || stripos($activity, 'fungicide') !== false || stripos($activity, 'pesticide') !== false) {
        if ($rain >= 60)
            $note = '🌧 Rain expected (' . $rain . '%). Spraying ineffective — reschedule.';
    }

    if (stripos($activity, 'monitoring') !== false || stripos($activity, 'inspection') !== false || stripos($activity, 'blight') !== false) {
        if ($humid >= 85) {
            $activity = '🚨 URGENT: ' . $activity;
            $note     = '⚠ Humidity ' . $humid . '% — High fungal & pest disease risk. Inspect immediately.';
            $urgent   = true;
        } elseif ($humid >= 75) {
            $note = '⚠ Humidity elevated (' . $humid . '%). Increased disease risk — monitor closely.';
        }
    }

    if (stripos($activity, 'harvest') !== false) {
        if ($rain >= 60)
            $note = '🌧 Rain expected. Delay harvest to avoid grain damage.';
    }

    return [$activity, $note, $urgent];
}

/* =========================================
   AUTO GENERATE ACTIVITIES FROM TEMPLATE
========================================= */

$template = getActivityTemplate($crop['crop_name']);

foreach ($template as $task) {
    $taskDay  = (int)$task['day'];
    $activity = $task['activity'];

    if ($taskDay > $daysElapsed + 7) continue;

    $exists = $conn->query("
        SELECT id FROM activity_schedule
        WHERE farmer_crop_id = $cropId AND day_number = $taskDay LIMIT 1
    ");
    if ($exists->num_rows > 0) continue;

    [$activity, $weatherNote, $urgent] = applyWeatherLogic($activity, $weather);

    $stmt = $conn->prepare("
        INSERT INTO activity_schedule (user_id, farmer_crop_id, day_number, activity, weather_note, status)
        VALUES (?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iiiss", $uid, $cropId, $taskDay, $activity, $weatherNote);
    $stmt->execute();

    if ($urgent) {
        $title = "🚨 Urgent Farm Alert";
        $msg   = $conn->real_escape_string($activity . " for " . $crop['crop_name']);
        $safeTitle = $conn->real_escape_string($title);
        $check = $conn->query("SELECT id FROM notifications WHERE user_id=$uid AND title='$safeTitle' AND DATE(created_at)=CURDATE() LIMIT 1");
        if ($check->num_rows === 0) {
            $conn->query("INSERT INTO notifications (user_id, title, message, type) VALUES ($uid, '$safeTitle', '$msg', 'activity')");
        }
    }
}

/* =========================================
   REFRESH WEATHER NOTES ON EXISTING ROWS
========================================= */

$existing = $conn->query("SELECT * FROM activity_schedule WHERE farmer_crop_id=$cropId AND user_id=$uid AND status='pending'");
while ($row = $existing->fetch_assoc()) {
    [, $updatedNote, ] = applyWeatherLogic($row['activity'], $weather);
    $safeNote = $conn->real_escape_string($updatedNote);
    $conn->query("UPDATE activity_schedule SET weather_note='$safeNote' WHERE id={$row['id']}");
}

/* =========================================
   HANDLE MARK DONE / SKIPPED
========================================= */

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_status'])) {

    $aid    = (int)$_POST['activity_id'];
    $status = $_POST['mark_status'];
    $note   = clean($conn, $_POST['completion_note'] ?? '');

    if (!in_array($status, ['done', 'skipped'])) {
        $error = 'Invalid status.';
    } else {
        // Verify ownership
        $check = $conn->query("SELECT id FROM activity_schedule WHERE id=$aid AND user_id=$uid LIMIT 1");
        if ($check->num_rows === 0) {
            $error = 'Activity not found.';
        } else {
            $conn->query("
                UPDATE activity_schedule
                SET status='$status',
                    completed_at=NOW(),
                    completion_note='$note'
                WHERE id=$aid AND user_id=$uid
            ");

            // Send completion notification
            $label     = $status === 'done' ? '✅ Completed' : '⏭ Skipped';
            $actRow    = $conn->query("SELECT activity FROM activity_schedule WHERE id=$aid LIMIT 1")->fetch_assoc();
            $actText   = $conn->real_escape_string($actRow['activity']);
            $cropName  = $conn->real_escape_string($crop['crop_name']);
            $notifTitle = $conn->real_escape_string("$label: Activity Logged");
            $notifMsg   = $conn->real_escape_string("$label — $actText for $cropName.");

            $conn->query("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES ($uid, '$notifTitle', '$notifMsg', 'activity')
            ");

            $success = $status === 'done' ? '✅ Activity marked as done!' : '⏭ Activity skipped.';
        }
    }
}

/* =========================================
   HANDLE DELETE
========================================= */

if (isset($_GET['delete'])) {
    $did = (int)$_GET['delete'];
    $conn->query("DELETE FROM activity_schedule WHERE id=$did AND user_id=$uid");
    header("Location: activity_schedule.php?crop=$cropId");
    exit;
}

/* =========================================
   UNDO (reset back to pending)
========================================= */

if (isset($_GET['undo'])) {
    $aid = (int)$_GET['undo'];
    $conn->query("UPDATE activity_schedule SET status='pending', completed_at=NULL, completion_note=NULL WHERE id=$aid AND user_id=$uid");
    header("Location: activity_schedule.php?crop=$cropId");
    exit;
}

/* =========================================
   FETCH ALL ACTIVITIES
========================================= */

$activities = $conn->query("
    SELECT * FROM activity_schedule
    WHERE farmer_crop_id=$cropId AND user_id=$uid
    ORDER BY day_number ASC
");

/* stats for progress bar */
$statsRow = $conn->query("
    SELECT
        COUNT(*) AS total,
        SUM(status='done') AS done_count,
        SUM(status='skipped') AS skipped_count,
        SUM(status='pending') AS pending_count
    FROM activity_schedule
    WHERE farmer_crop_id=$cropId AND user_id=$uid
")->fetch_assoc();

$total      = (int)$statsRow['total'];
$doneCount  = (int)$statsRow['done_count'];
$skipCount  = (int)$statsRow['skipped_count'];
$pendCount  = (int)$statsRow['pending_count'];
$pct        = $total > 0 ? round(($doneCount / $total) * 100) : 0;

include 'layout.php';
?>

<?php if ($success): ?>
<div class="alert alert-success"><?= $success ?></div>
<?php endif; ?>
<?php if ($error): ?>
<div class="alert alert-error">⚠️ <?= $error ?></div>
<?php endif; ?>

<!-- ===== CROP HEADER ===== -->
<div class="card">
    <div class="card-title">📅 <?= htmlspecialchars($crop['crop_name']) ?> — Activity Schedule</div>

    <div style="display:flex; gap:24px; flex-wrap:wrap; color:var(--text2); margin-bottom:16px">
        <span>🌱 Planted: <b><?= $crop['planted_date'] ?></b></span>
        <span>📆 Age: <b><?= $daysElapsed ?> days</b></span>
        <span>🌾 Harvest: <b><?= $crop['expected_harvest'] ?></b></span>
    </div>

    <?php if ($weather): ?>
    <div style="padding:12px 16px; border-radius:12px; background:#eef6ff; border:1px solid #cfe2ff; display:flex; gap:24px; flex-wrap:wrap; font-size:14px; margin-bottom:16px">
        <span><?= $weather['rain_probability'] > 60 ? '🌧️' : ($weather['rain_probability'] > 30 ? '⛅' : '☀️') ?>
            Rain: <b><?= $weather['rain_probability'] ?>%</b></span>
        <span>💧 Humidity: <b><?= $weather['humidity'] ?>%</b></span>
        <span>🌡 Temp: <b><?= $weather['temperature'] ?>°C</b></span>
    </div>
    <?php endif; ?>

    <!-- PROGRESS BAR -->
    <div style="font-size:13px; color:var(--text2); margin-bottom:6px">
        Progress: <b><?= $doneCount ?> / <?= $total ?> completed</b>
        &nbsp;·&nbsp; <?= $skipCount ?> skipped
        &nbsp;·&nbsp; <?= $pendCount ?> pending
    </div>
    <div style="background:#e9ecef; border-radius:20px; height:14px; overflow:hidden">
        <div style="width:<?= $pct ?>%; height:100%; background:<?= $pct==100 ? '#198754' : '#0d6efd' ?>; border-radius:20px; transition:width 0.4s ease"></div>
    </div>
    <div style="font-size:12px; color:var(--text3); margin-top:4px"><?= $pct ?>% complete</div>
</div>

<!-- ===== TIMELINE ===== -->
<div class="card">
    <div class="card-title">📋 Activity Timeline</div>

    <?php if ($activities->num_rows === 0): ?>
        <div class="empty-state">No activities yet. They will appear as your crop grows.</div>
    <?php else: ?>

    <?php while ($a = $activities->fetch_assoc()):
        $activityDate = date('Y-m-d', strtotime($crop['planted_date'] . " +" . $a['day_number'] . " days"));
        $today        = date('Y-m-d');
        $isToday      = ($activityDate === $today);
        $isPast       = ($activityDate < $today);
        $isUrgent     = str_contains($a['activity'], '🚨');
        $status       = $a['status'];

        // Card styling based on status
        if ($status === 'done') {
            $bg = '#f0fff4'; $borderColor = '#198754';
        } elseif ($status === 'skipped') {
            $bg = '#f8f9fa'; $borderColor = '#adb5bd';
        } elseif ($isUrgent) {
            $bg = '#fff0f0'; $borderColor = '#dc3545';
        } elseif ($isToday) {
            $bg = '#fff3cd'; $borderColor = '#f0ad00';
        } elseif ($isPast) {
            $bg = '#fff8f0'; $borderColor = '#fd7e14';
        } else {
            $bg = 'var(--surface)'; $borderColor = 'var(--border)';
        }
    ?>

    <div style="
        padding:18px;
        border:1px solid <?= $borderColor ?>;
        border-left:5px solid <?= $borderColor ?>;
        border-radius:14px;
        margin-bottom:14px;
        background:<?= $bg ?>;
        <?= $status !== 'pending' ? 'opacity:0.85;' : '' ?>
    ">
        <div style="display:flex; justify-content:space-between; gap:16px; flex-wrap:wrap">

            <!-- LEFT: info -->
            <div style="flex:1">

                <!-- DAY + BADGES -->
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-bottom:8px">
                    <span style="font-size:17px; font-weight:800">📅 Day <?= $a['day_number'] ?></span>

                    <?php if ($status === 'done'): ?>
                        <span class="badge badge-success">✅ Done</span>
                    <?php elseif ($status === 'skipped'): ?>
                        <span class="badge badge-gray">⏭ Skipped</span>
                    <?php elseif ($isUrgent): ?>
                        <span class="badge badge-danger">🚨 Urgent</span>
                    <?php elseif ($isToday): ?>
                        <span class="badge badge-warning">⚠️ Today</span>
                    <?php elseif ($isPast): ?>
                        <span class="badge badge-danger">⏰ Overdue</span>
                    <?php else: ?>
                        <span class="badge badge-info">📅 Upcoming</span>
                    <?php endif; ?>
                </div>

                <!-- ACTIVITY TEXT -->
                <div style="font-size:15px; <?= $status === 'done' ? 'text-decoration:line-through; color:var(--text3)' : '' ?>">
                    <?= htmlspecialchars($a['activity']) ?>
                </div>

                <!-- WEATHER NOTE (only for pending) -->
                <?php if (!empty($a['weather_note']) && $status === 'pending'): ?>
                <div style="margin-top:8px; padding:10px 12px; border-radius:10px;
                    background:<?= $isUrgent ? '#ffe5e5' : '#fff3cd' ?>;
                    color:<?= $isUrgent ? '#8B0000' : '#856404' ?>;
                    font-size:13px">
                    <?= htmlspecialchars($a['weather_note']) ?>
                </div>
                <?php endif; ?>

                <!-- SCHEDULED DATE -->
                <div style="margin-top:8px; font-size:13px; color:var(--text3)">
                    🔔 Scheduled: <b><?= $activityDate ?></b>
                </div>

                <!-- COMPLETION INFO -->
                <?php if ($status !== 'pending'): ?>
                <div style="margin-top:6px; font-size:13px; color:var(--text3)">
                    🕐 <?= $status === 'done' ? 'Completed' : 'Skipped' ?>:
                    <b><?= $a['completed_at'] ? date('d M Y, h:i A', strtotime($a['completed_at'])) : '—' ?></b>
                </div>
                <?php if (!empty($a['completion_note'])): ?>
                <div style="margin-top:6px; font-size:13px; padding:8px 12px; border-radius:8px; background:rgba(0,0,0,0.04); color:var(--text2)">
                    📝 <?= htmlspecialchars($a['completion_note']) ?>
                </div>
                <?php endif; ?>
                <?php endif; ?>

            </div>

            <!-- RIGHT: action buttons -->
            <div style="display:flex; flex-direction:column; gap:8px; align-items:flex-end">

                <?php if ($status === 'pending'): ?>

                <!-- MARK DONE BUTTON — opens inline form -->
                <button
                    class="btn btn-success btn-sm"
                    onclick="toggleForm('form-<?= $a['id'] ?>')"
                >
                    ✅ Mark Done
                </button>

                <!-- SKIP BUTTON -->
                <button
                    class="btn btn-warning btn-sm"
                    onclick="toggleForm('skip-<?= $a['id'] ?>')"
                >
                    ⏭ Skip
                </button>

                <?php else: ?>

                <!-- UNDO BUTTON -->
                
                  <a  href="activity_schedule.php?crop=<?= $cropId ?>&undo=<?= $a['id'] ?>"
                    class="btn btn-outline btn-sm"
                    onclick="return confirm('Undo this? It will reset to pending.')"
                >
                    ↩ Undo
                </a>

                <?php endif; ?>

                <!-- DELETE -->
                
                  

            </div>
        </div>

        <!-- MARK DONE INLINE FORM -->
        <?php if ($status === 'pending'): ?>

        <div id="form-<?= $a['id'] ?>" style="display:none; margin-top:14px; padding:14px; background:rgba(25,135,84,0.07); border-radius:12px; border:1px solid #a3cfbb">
            <form method="post">
                <input type="hidden" name="activity_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="mark_status" value="done">
                <div class="form-group" style="margin-bottom:10px">
                    <label style="font-size:13px; font-weight:600">📝 Completion Note (optional)</label>
                    <input
                        type="text"
                        name="completion_note"
                        placeholder="e.g. Applied 2kg Urea per bigha"
                        style="font-size:13px"
                    >
                </div>
                <div style="display:flex; gap:8px">
                    <button class="btn btn-success btn-sm" type="submit">✅ Confirm Done</button>
                    <button class="btn btn-outline btn-sm" type="button" onclick="toggleForm('form-<?= $a['id'] ?>')">Cancel</button>
                </div>
            </form>
        </div>

        <!-- SKIP INLINE FORM -->
        <div id="skip-<?= $a['id'] ?>" style="display:none; margin-top:14px; padding:14px; background:rgba(173,181,189,0.15); border-radius:12px; border:1px solid #dee2e6">
            <form method="post">
                <input type="hidden" name="activity_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="mark_status" value="skipped">
                <div class="form-group" style="margin-bottom:10px">
                    <label style="font-size:13px; font-weight:600">📝 Reason for skipping (optional)</label>
                    <input
                        type="text"
                        name="completion_note"
                        placeholder="e.g. Heavy rain today, rescheduled"
                        style="font-size:13px"
                    >
                </div>
                <div style="display:flex; gap:8px">
                    <button class="btn btn-warning btn-sm" type="submit">⏭ Confirm Skip</button>
                    <button class="btn btn-outline btn-sm" type="button" onclick="toggleForm('skip-<?= $a['id'] ?>')">Cancel</button>
                </div>
            </form>
        </div>

        <?php endif; ?>
    </div>

    <?php endwhile; ?>
    <?php endif; ?>
</div>

<script>
function toggleForm(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>

<?php include 'layout_end.php'; ?>