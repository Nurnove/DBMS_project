<?php
require_once 'db.php';
requireLogin();

if ($_SESSION['user_role'] !== 'expert') {
    header("Location: dashboard.php");
    exit;
}

$user = currentUser($conn);
$expert_id = (int)$user['id'];

$qid = (int)$_GET['id'];

$q = $conn->query("SELECT * FROM questions WHERE id=$qid")->fetch_assoc();

$existing = $conn->query("
SELECT * FROM answers WHERE question_id=$qid
")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $answer = mysqli_real_escape_string($conn, $_POST['answer']);

    if ($existing) {

        $conn->query("
            UPDATE answers
            SET answer='$answer', expert_id=$expert_id
            WHERE question_id=$qid
        ");

    } else {

        $conn->query("
            INSERT INTO answers (question_id, expert_id, answer)
            VALUES ($qid, $expert_id, '$answer')
        ");
    }

    $farmer = $conn->query("
        SELECT user_id FROM questions WHERE id=$qid
    ")->fetch_assoc();

    $fid = (int)$farmer['user_id'];

    $conn->query("
        INSERT INTO notifications (user_id, title, message, type)
        VALUES ($fid, 'Answer Received', 'Expert answered your question.', 'faq')
    ");

    header("Location: faq_manage.php");
}

include 'layout.php';
?>

<div class="card">
  <div class="card-title">💡 Answer Question</div>

  <p><b>Question:</b> <?= htmlspecialchars($q['question']) ?></p>

  <form method="POST">
    <textarea name="answer" rows="6"><?= htmlspecialchars($existing['answer'] ?? '') ?></textarea>
    <button class="btn btn-primary">Save Answer</button>
  </form>
</div>

<?php include 'layout_end.php'; ?>