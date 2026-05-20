<?php
require_once 'db.php';
requireLogin();

if ($_SESSION['user_role'] !== 'expert') {
    header("Location: dashboard.php");
    exit;
}

$user = currentUser($conn);
$expert_id = (int)$user['id'];

$report_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$report_id) {
    die("Invalid report");
}

/* =========================
   GET REPORT
========================= */
$report = $conn->query("
    SELECT pr.*, c.name AS crop_name, p.name AS pest_name
    FROM pest_reports pr
    JOIN crops c ON pr.crop_id = c.id
    JOIN pests p ON pr.pest_id = p.id
    WHERE pr.id = $report_id
")->fetch_assoc();

if (!$report) {
    die("Report not found");
}

$success = $error = '';

/* =========================
   POST (ADD / UPDATE REVIEW)
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $advice = trim($_POST['advice'] ?? '');

    if ($advice === '') {
        $error = "Advice is required!";
    } else {

        $advice = mysqli_real_escape_string($conn, $advice);

        /* CHECK EXISTING REVIEW */
        $existing = $conn->query("
            SELECT id FROM pest_reviews WHERE report_id = $report_id
        ")->fetch_assoc();

        if ($existing) {

            /* UPDATE */
            $conn->query("
                UPDATE pest_reviews 
                SET advice='$advice', expert_id=$expert_id, created_at=CURRENT_TIMESTAMP
                WHERE report_id=$report_id
            ");

            $actionText = "updated";
        } else {

            /* INSERT */
            $conn->query("
                INSERT INTO pest_reviews (report_id, expert_id, advice)
                VALUES ($report_id, $expert_id, '$advice')
            ");

            $actionText = "created";
        }

        /* =========================
           GET FARMER
        ========================= */
        $farmer = $conn->query("
            SELECT user_id FROM pest_reports WHERE id = $report_id
        ")->fetch_assoc();

        if ($farmer) {
            $farmer_id = (int)$farmer['user_id'];

            /* =========================
               NOTIFICATION (IMPORTANT PART)
            ========================= */
            $title = "Pest Expert Advice";
            $message = "Expert has $actionText advice on your pest report (Crop: {$report['crop_name']}).";

            $conn->query("
                INSERT INTO notifications (user_id, title, message, type)
                VALUES ($farmer_id, '$title', '$message', 'pest_review')
            ");
        }

        $success = "Advice $actionText successfully!";
    }
}

/* =========================
   GET EXISTING REVIEW (for edit view)
========================= */
$oldReview = $conn->query("
    SELECT * FROM pest_reviews WHERE report_id = $report_id
")->fetch_assoc();

include 'layout.php';
?>

<div class="card">

  <div class="card-title">🧠 Pest Review System</div>

  <div style="margin-bottom:12px">
    <b>Crop:</b> <?= htmlspecialchars($report['crop_name']) ?><br>
    <b>Pest:</b> <?= htmlspecialchars($report['pest_name']) ?><br>
    <b>Severity:</b> <?= htmlspecialchars($report['severity']) ?>
  </div>

  <?php if ($success): ?>
    <div class="alert alert-success">✅ <?= $success ?></div>
  <?php endif; ?>

  <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= $error ?></div>
  <?php endif; ?>

  <form method="POST">

    <div class="form-group">
      <label>Expert Advice</label>
      <textarea name="advice" rows="6" required><?= htmlspecialchars($oldReview['advice'] ?? '') ?></textarea>
    </div>

    <button class="btn btn-primary">
      💡 Save Advice
    </button>

    <a href="pest_review.php" class="btn btn-outline">Back</a>

  </form>

</div>

<?php include 'layout_end.php'; ?>