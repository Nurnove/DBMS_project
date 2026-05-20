<?php
require_once 'db.php';
requireLogin();

$user = currentUser($conn);
$uid = $user['id'];

$rows = $conn->query("
SELECT q.*, a.answer
FROM questions q
LEFT JOIN answers a ON q.id = a.question_id
WHERE q.user_id = $uid
ORDER BY q.created_at DESC
");

include 'layout.php';
?>

<div class="card">
  <div class="card-title">📝 My Questions</div>

  <?php while($r = $rows->fetch_assoc()): ?>

    <div style="padding:10px;border-bottom:1px solid #ddd">

      ❓ <?= htmlspecialchars($r['question']) ?><br>

      <?php if ($r['answer']): ?>
        🧠 Answer: <?= htmlspecialchars($r['answer']) ?>
      <?php else: ?>
        ⏳ Waiting for expert reply
      <?php endif; ?>

    </div>

  <?php endwhile; ?>

</div>

<?php include 'layout_end.php'; ?>