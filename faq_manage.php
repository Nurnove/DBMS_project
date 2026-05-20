<?php
require_once 'db.php';
requireLogin();

if ($_SESSION['user_role'] !== 'expert') {
    header("Location: dashboard.php");
    exit;
}

$questions = $conn->query("
SELECT q.*, u.name,
       a.id AS answer_id,
       a.answer
FROM questions q
JOIN users u ON q.user_id = u.id
LEFT JOIN answers a ON q.id = a.question_id
ORDER BY q.created_at DESC
");

include 'layout.php';
?>

<div class="card">
  <div class="card-title">❓ All Questions</div>

  <?php while($q = $questions->fetch_assoc()): ?>

    <div style="padding:10px;border-bottom:1px solid #ddd">

      <b><?= htmlspecialchars($q['question']) ?></b><br>

      <small>
        📂 <?= $q['category'] ?> |
        <?= $q['is_public'] ? '🌍 Public' : '🔒 Private' ?> |
        👨‍🌾 <?= htmlspecialchars($q['name']) ?>
      </small><br>

      <!-- STATUS BADGE -->
      <div style="margin:5px 0">
        <?php if ($q['answer_id']): ?>
            <span style="color:green;font-weight:bold;">✔ Answered</span>

            <!-- SHOW ANSWER -->
            <div style="margin-top:5px;background:#e8f5e9;padding:8px;">
              🧠 <?= htmlspecialchars($q['answer']) ?>
            </div>

        <?php else: ?>
            <span style="color:orange;font-weight:bold;">⏳ Pending</span>
        <?php endif; ?>
      </div>

      <!-- BUTTON LOGIC -->
      <?php if (!$q['answer_id']): ?>
        <a href="faq_answer.php?id=<?= $q['id'] ?>" class="btn btn-primary btn-sm">
          Answer
        </a>
      <?php endif; ?>

    </div>

  <?php endwhile; ?>

</div>

<?php include 'layout_end.php'; ?>