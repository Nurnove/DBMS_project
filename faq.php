<?php
require_once 'db.php';
requireLogin();

$user = currentUser($conn);
$uid = (int)$user['id'];

/* =========================
   SUBMIT QUESTION
========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $question = mysqli_real_escape_string($conn, $_POST['question']);
    $category = $_POST['category'];
    $tags = mysqli_real_escape_string($conn, $_POST['tags']);
    $is_public = (int)$_POST['is_public'];

    if ($question !== '') {

        $conn->query("
            INSERT INTO questions (user_id, question, category, tags, is_public)
            VALUES ($uid, '$question', '$category', '$tags', $is_public)
        ");

        $conn->query("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES ($uid, 'Question Submitted', 'Your question has been submitted.', 'faq')
        ");
    }
}

/* =========================
   FILTER
========================= */
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "
SELECT q.*, a.answer
FROM questions q
LEFT JOIN answers a ON q.id = a.question_id
WHERE (q.is_public = 1 OR q.user_id = $uid)
";

if ($category) {
    $sql .= " AND q.category = '$category' ";
}

if ($search) {
    $sql .= " AND (q.question LIKE '%$search%' OR q.tags LIKE '%$search%') ";
}

$sql .= " ORDER BY q.created_at DESC";

$questions = $conn->query($sql);

include 'layout.php';
?>

<div class="card">
  <div class="card-title">❓ Ask Expert</div>

  <form method="POST">

    <textarea name="question" required placeholder="Ask your farming problem..."></textarea>

    <select name="category">
      <option value="pest">🐛 Pest</option>
      <option value="crop">🌾 Crop</option>
      <option value="irrigation">💧 Irrigation</option>
      <option value="other">Other</option>
    </select>

    <input type="text" name="tags" placeholder="tags (rice, aphid, yellow leaf)">

    <select name="is_public">
      <option value="1">🌍 Public</option>
      <option value="0">🔒 Private</option>
    </select>

    <button class="btn btn-primary">Submit</button>
  </form>
</div>

<div class="card">
  <div class="card-title">📚 FAQ</div>

  <form method="GET">
    <select name="category">
      <option value="">All</option>
      <option value="pest">Pest</option>
      <option value="crop">Crop</option>
      <option value="irrigation">Irrigation</option>
    </select>

    <input type="text" name="search" placeholder="Search...">

    <button class="btn btn-outline">Filter</button>
  </form>

  <?php while($q = $questions->fetch_assoc()): ?>

    <div class="card" style="margin-top:10px">

      <b>Q:</b> <?= htmlspecialchars($q['question']) ?><br>

      <small>
        📂 <?= $q['category'] ?> |
        🏷️ <?= htmlspecialchars($q['tags']) ?> |
        <?= $q['is_public'] ? '🌍 Public' : '🔒 Private' ?>
      </small>

      <div style="margin-top:8px">

        <?php if ($q['answer']): ?>
          <div style="color:green">
            💡 <?= htmlspecialchars($q['answer']) ?>
          </div>
        <?php else: ?>
          <span style="color:orange">⏳ Waiting for answer</span>
        <?php endif; ?>

      </div>

    </div>

  <?php endwhile; ?>

</div>

<?php include 'layout_end.php'; ?>