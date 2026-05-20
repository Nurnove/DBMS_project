<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Compliance Checker';
$activeNav = 'compliance';

$resultData = null;
$warning = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $pesticide = clean($conn, $_POST['pesticide']);

    /* =========================
       CHECK BANNED
    ========================= */

    $ban = $conn->query("
        SELECT *
        FROM banned_pesticides
        WHERE LOWER(name)=LOWER('$pesticide')
    ");

    if ($ban->num_rows > 0) {

        $b = $ban->fetch_assoc();

        $warning = $b['reason'];

    } else {

        /* =========================
           SAFE GUIDELINE
        ========================= */

        $guide = $conn->query("
            SELECT *
            FROM pesticide_guidelines
            WHERE LOWER(pesticide_name)=LOWER('$pesticide')
        ");

        if ($guide->num_rows > 0) {

            $resultData = $guide->fetch_assoc();
        }
    }
}

include 'layout.php';
?>

<div class="card">

    <div class="card-title">
        ⚖️ Smart Compliance Checker
    </div>

    <form method="post">

        <div class="form-group">

            <label>Pesticide Name</label>

            <input
                type="text"
                name="pesticide"
                placeholder="Example: Malathion"
                required
            >
        </div>

        <button class="btn btn-primary">
            🔍 Check Compliance
        </button>

    </form>

</div>

<!-- =========================
     BANNED WARNING
========================= -->

<?php if ($warning): ?>

<div class="card" style="
    border:2px solid red;
    background:#fff5f5;
    margin-top:20px;
">

    <div style="
        color:red;
        font-size:22px;
        font-weight:800;
        margin-bottom:10px;
    ">
        ❌ BANNED PESTICIDE
    </div>

    <div>
        <b>Reason:</b>
        <?= htmlspecialchars($warning) ?>
    </div>

</div>

<?php endif; ?>

<!-- =========================
     SAFE RESULT
========================= -->

<?php if ($resultData): ?>

<div class="card" style="
    margin-top:20px;
">

    <div style="
        font-size:22px;
        font-weight:800;
        margin-bottom:14px;
    ">
        ✅ Safe Usage Guideline
    </div>

    <div style="margin-bottom:10px">
        🧪 Pesticide:
        <b><?= htmlspecialchars($resultData['pesticide_name']) ?></b>
    </div>

    <div style="margin-bottom:10px">
        💧 Safe Dosage:
        <b><?= htmlspecialchars($resultData['safe_dosage']) ?></b>
    </div>

    <div>
        🌾 PHI Days:
        <b><?= $resultData['phi_days'] ?> days</b>
    </div>

    <div style="
        margin-top:12px;
        color:var(--text2);
        font-size:13px;
    ">
        PHI = Pre Harvest Interval.
        Stop using pesticide before harvest.
    </div>

</div>

<?php endif; ?>

<?php include 'layout_end.php'; ?>