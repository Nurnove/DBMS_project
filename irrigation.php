<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Smart Irrigation';
$activeNav = 'irrigation';

$user = currentUser($conn);
$uid = (int)$user['id'];

$success = $error = '';

/* =====================================================
   DELETE LOG
===================================================== */

if (isset($_GET['delete'])) {

    $iid = (int)$_GET['delete'];

    $conn->query("
        DELETE FROM irrigation_logs
        WHERE id = $iid
        AND user_id = $uid
    ");

    $success = 'Log deleted successfully.';
}

/* =====================================================
   USER LOCATION WEATHER
===================================================== */

$locId = (int)($user['location_id'] ?? 0);

$weather = null;

if ($locId > 0) {

    $weather = $conn->query("
        SELECT *
        FROM weather_data
        WHERE location_id = $locId
        ORDER BY recorded_at DESC
        LIMIT 1
    ")->fetch_assoc();
}

/* =====================================================
   SAVE IRRIGATION LOG
===================================================== */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $field_id = (int)($_POST['field_id'] ?? 0);

    $date = clean(
        $conn,
        $_POST['date'] ?? date('Y-m-d')
    );

    /* AUTO WEATHER VALUE */

    $rain_prob = (int)($weather['rain_probability'] ?? 0);

    /* SMART DECISION */

    if ($rain_prob > 60) {
        $suggestion = 'OFF';
    } elseif ($rain_prob >= 30) {
        $suggestion = 'MONITOR';
    } else {
        $suggestion = 'ON';
    }

    $fv = $field_id > 0 ? $field_id : 'NULL';

    $conn->query("
        INSERT INTO irrigation_logs
        (
            user_id,
            field_id,
            date,
            suggestion,
            rain_probability
        )
        VALUES
        (
            $uid,
            $fv,
            '$date',
            '$suggestion',
            $rain_prob
        )
    ");

    $success = "Irrigation decision saved successfully!";
}

/* =====================================================
   USER DATA
===================================================== */

$logs = $conn->query("
SELECT
    il.*,
    f.name AS field_name
FROM irrigation_logs il
LEFT JOIN fields f
ON il.field_id = f.id
WHERE il.user_id = $uid
ORDER BY il.date DESC, il.id DESC
LIMIT 20
");

$myFields = $conn->query("
    SELECT *
    FROM fields
    WHERE user_id = $uid
    ORDER BY name
");

include 'layout.php';
?>

<?php if ($success): ?>
<div class="alert alert-success">
    ✅ <?= $success ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-error">
    ⚠️ <?= $error ?>
</div>
<?php endif; ?>

<!-- =====================================================
     WEATHER WIDGET
===================================================== -->

<?php if ($weather):

    $rp = (int)$weather['rain_probability'];

    if ($rp > 60) {
        $irr = 'OFF';
        $irrIcon = '❌';
        $irrColor = 'var(--danger)';
    }
    elseif ($rp >= 30) {
        $irr = 'MONITOR';
        $irrIcon = '⚠️';
        $irrColor = 'var(--warn)';
    }
    else {
        $irr = 'ON';
        $irrIcon = '✅';
        $irrColor = 'var(--success)';
    }

    $weatherIcon = '☀️';

    if ($rp > 60) {
        $weatherIcon = '🌧️';
    }
    elseif ($rp >= 30) {
        $weatherIcon = '⛅';
    }

?>

<div style="
    background:linear-gradient(135deg,var(--accent),var(--accent2));
    border-radius:var(--radius);
    padding:26px;
    margin-bottom:20px;
    color:#fff;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
    gap:20px;
">

    <!-- LEFT -->

    <div>

        <div style="
            font-size:13px;
            opacity:.85;
            margin-bottom:6px;
        ">
            🌍 Live Weather Data
        </div>

        <div style="
            font-family:'Syne',sans-serif;
            font-size:2.4rem;
            font-weight:800;
        ">
            <?= $weatherIcon ?>
            <?= $weather['temperature'] ?>°C
        </div>

        <div style="
            font-size:14px;
            margin-top:8px;
            opacity:.92;
        ">
            💧 Humidity:
            <?= $weather['humidity'] ?>%
            <br>

            🌧️ Rainfall:
            <?= $weather['rainfall'] ?> mm
        </div>

    </div>

    <!-- RIGHT -->

    <div style="
        background:rgba(255,255,255,.15);
        border-radius:14px;
        padding:22px 30px;
        text-align:center;
        min-width:240px;
    ">

        <div style="
            font-size:13px;
            opacity:.85;
        ">
            Rain Probability
        </div>

        <div style="
            font-family:'Syne',sans-serif;
            font-size:2.7rem;
            font-weight:800;
            margin:4px 0;
        ">
            <?= $rp ?>%
        </div>

        <div style="
            font-size:1.1rem;
            font-weight:700;
        ">
            <?= $irrIcon ?>
            Irrigation:
            <?= $irr ?>
        </div>

        <div style="
            font-size:12px;
            opacity:.82;
            margin-top:8px;
        ">

            <?php if ($irr == 'OFF'): ?>

                Rain expected soon.
                Skip irrigation today.

            <?php elseif ($irr == 'MONITOR'): ?>

                Weather uncertain.
                Monitor soil moisture.

            <?php else: ?>

                Dry weather detected.
                Irrigation recommended.

            <?php endif; ?>

        </div>

    </div>

</div>

<?php else: ?>

<div class="alert alert-info">
    ☁️ No weather data available for your location.
</div>

<?php endif; ?>

<!-- =====================================================
     SMART LOGIC
===================================================== -->

<div class="card" style="margin-bottom:20px">

    <div class="card-title">
        ⚙️ Smart Irrigation Logic
    </div>

    <div class="grid-3">

        <div style="
            background:var(--surface2);
            border-radius:var(--radius-sm);
            padding:18px;
            text-align:center;
        ">
            <div style="font-size:34px">🌧️</div>

            <div style="
                margin:8px 0;
                font-weight:700;
                color:var(--danger);
            ">
                Rain > 60%
            </div>

            <div style="
                font-size:13px;
                color:var(--text2);
            ">
                Skip irrigation because rain is expected.
            </div>
        </div>

        <div style="
            background:var(--surface2);
            border-radius:var(--radius-sm);
            padding:18px;
            text-align:center;
        ">
            <div style="font-size:34px">⛅</div>

            <div style="
                margin:8px 0;
                font-weight:700;
                color:var(--warn);
            ">
                Rain 30–60%
            </div>

            <div style="
                font-size:13px;
                color:var(--text2);
            ">
                Monitor field conditions before irrigating.
            </div>
        </div>

        <div style="
            background:var(--surface2);
            border-radius:var(--radius-sm);
            padding:18px;
            text-align:center;
        ">
            <div style="font-size:34px">☀️</div>

            <div style="
                margin:8px 0;
                font-weight:700;
                color:var(--success);
            ">
                Rain < 30%
            </div>

            <div style="
                font-size:13px;
                color:var(--text2);
            ">
                Dry conditions detected. Irrigation recommended.
            </div>
        </div>

    </div>

</div>

<!-- =====================================================
     SAVE LOG
===================================================== -->

<div class="card" style="margin-bottom:20px">

    <div class="card-title">
        💧 Save Irrigation Decision
    </div>

    <form method="post">

        <div class="grid-2">

            <div class="form-group">
                <label>Date</label>

                <input
                    type="date"
                    name="date"
                    value="<?= date('Y-m-d') ?>"
                    required
                >
            </div>

            <div class="form-group">

                <label>Field (optional)</label>

                <select name="field_id">

                    <option value="">
                        Select Field
                    </option>

                    <?php while($f = $myFields->fetch_assoc()): ?>

                        <option value="<?= $f['id'] ?>">
                            <?= htmlspecialchars($f['name']) ?>
                        </option>

                    <?php endwhile; ?>

                </select>

            </div>

        </div>

        <button class="btn btn-primary">
            💧 Save Irrigation Log
        </button>

    </form>

</div>

<!-- =====================================================
     LOG HISTORY
===================================================== -->

<div class="card">

    <div class="card-title">
        📋 Irrigation History
    </div>

    <?php if ($logs->num_rows === 0): ?>

        <div class="empty-state">

            <div class="empty-icon">💧</div>

            <p>No irrigation logs found.</p>

        </div>

    <?php else: ?>

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>Date</th>
                    <th>Field</th>
                    <th>Rain Probability</th>
                    <th>Suggestion</th>
                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

            <?php while($lg = $logs->fetch_assoc()): ?>

                <tr>

                    <td>
                        <?= $lg['date'] ?>
                    </td>

                    <td>
                        <?= htmlspecialchars($lg['field_name'] ?? '—') ?>
                    </td>

                    <td>

                        <?= $lg['rain_probability'] ?>%

                    </td>

                    <td>

                        <?php if ($lg['suggestion'] == 'ON'): ?>

                            <span class="badge badge-success">
                                ✅ ON
                            </span>

                        <?php elseif ($lg['suggestion'] == 'MONITOR'): ?>

                            <span class="badge badge-warn">
                                ⚠️ MONITOR
                            </span>

                        <?php else: ?>

                            <span class="badge badge-danger">
                                ❌ OFF
                            </span>

                        <?php endif; ?>

                    </td>

                    <td>

                        <a
                            href="irrigation.php?delete=<?= $lg['id'] ?>"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Delete log?')"
                        >
                            🗑️
                        </a>

                    </td>

                </tr>

            <?php endwhile; ?>

            </tbody>

        </table>

    </div>

    <?php endif; ?>

</div>

<?php include 'layout_end.php'; ?>