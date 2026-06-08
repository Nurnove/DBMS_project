<?php
require_once 'db.php';
requireLogin();

$user = currentUser($conn);
$uid = (int)$user['id'];

/* ========================= SUBMIT QUESTION ========================= */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $question  = mysqli_real_escape_string($conn, $_POST['question']);
    $category  = mysqli_real_escape_string($conn, $_POST['category']);
    $tags      = mysqli_real_escape_string($conn, $_POST['tags']);
    $is_public = (int)($_POST['is_public'] ?? 1);
    $image_path = '';

    if (!empty($_FILES['image']['name'])) {
        $dir = "assets/images/questions/";
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        $file = time() . "_" . basename($_FILES['image']['name']);
        $image_path = $dir . $file;
        move_uploaded_file($_FILES['image']['tmp_name'], $image_path);
    }

    if ($question !== '') {
        $conn->query("INSERT INTO questions (user_id, question, category, tags, is_public, image_url)
            VALUES ($uid, '$question', '$category', '$tags', $is_public, '$image_path')");
        $conn->query("INSERT INTO notifications (user_id, title, message, type)
            VALUES ($uid, 'Question Submitted', 'Your question has been submitted successfully.', 'faq')");
    }
}

/* ========================= FILTER ========================= */
$category = $_GET['category'] ?? '';
$search   = $_GET['search'] ?? '';

$sql = "SELECT q.*, a.answer FROM questions q
        LEFT JOIN answers a ON q.id = a.question_id
        WHERE (q.is_public = 1 OR q.user_id = $uid)";

if ($category) $sql .= " AND q.category = '" . mysqli_real_escape_string($conn, $category) . "' ";
if ($search) {
    $search = mysqli_real_escape_string($conn, $search);
    $sql .= " AND (q.question LIKE '%$search%' OR q.tags LIKE '%$search%') ";
}
$sql .= " ORDER BY q.created_at DESC";
$questions = $conn->query($sql);

$pageTitle = 'Ask Expert – FAQ';
$activeNav = 'faq';
include 'layout.php';
?>

<style>
/* ── FAQ Page ── */
.faq-wrap { max-width: 860px; margin: 0 auto; padding: 0 1rem 3rem; }

/* Ask Form Card */
.ask-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 20px;
    padding: 2rem;
    margin-bottom: 2rem;
    backdrop-filter: blur(12px);
    box-shadow: var(--shadow-md), inset 0 1px 0 rgba(134,197,97,0.15);
    animation: slideDown 0.5s ease both;
}
@keyframes slideDown {
    from { opacity:0; transform:translateY(-18px); }
    to   { opacity:1; transform:translateY(0); }
}
.ask-card-header {
    display: flex; align-items: center; gap: 0.75rem;
    margin-bottom: 1.5rem;
}
.ask-card-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, #86c561, #4a9e2f);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.3rem; flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(134,197,97,0.4);
}
.ask-card-title { font-size: 1.25rem; font-weight: 700; color: var(--text); letter-spacing: 0.01em; }

.ask-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
.ask-grid .full { grid-column: 1/-1; }

.faq-input, .faq-select, .faq-textarea {
    width: 100%; box-sizing: border-box;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px;
    color: var(--text2); font-size: 0.9rem;
    padding: 0.7rem 1rem;
    transition: border-color 0.2s, box-shadow 0.2s;
    outline: none;
}
.faq-input:focus, .faq-select:focus, .faq-textarea:focus {
    border-color: var(--accent2);
    box-shadow: 0 0 0 3px rgba(134,197,97,0.15);
}
.faq-textarea { resize: vertical; min-height: 100px; font-family: inherit; }
.faq-select option { background: var(--surface3); color: var(--text2); }

.faq-label {
    display: block; font-size: 0.75rem; font-weight: 600;
    color: var(--accent2); text-transform: uppercase; letter-spacing: 0.08em;
    margin-bottom: 0.4rem;
}

/* File Upload */
.file-upload-box {
    border: 2px dashed rgba(134,197,97,0.3); border-radius: 10px;
    padding: 1.2rem; text-align: center; cursor: pointer;
    transition: all 0.2s; position: relative;
}
.file-upload-box:hover { border-color: var(--accent2); background: rgba(134,197,97,0.05); }
.file-upload-box input { position: absolute; inset:0; opacity:0; cursor:pointer; }
.file-upload-text { color: var(--accent2); font-size: 0.85rem; }

.btn-submit-faq {
    background: linear-gradient(135deg, #86c561, #4a9e2f);
    color: #0d1f0d; font-weight: 700; font-size: 0.95rem;
    border: none; border-radius: 10px; padding: 0.75rem 2rem;
    cursor: pointer; transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(134,197,97,0.35);
}
.btn-submit-faq:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(134,197,97,0.5); }

/* Filter Bar */
.filter-bar {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 14px; padding: 1rem 1.25rem;
    display: flex; gap: 0.75rem; align-items: center;
    flex-wrap: wrap; margin-bottom: 1.5rem;
    backdrop-filter: blur(8px);
    animation: fadeIn 0.5s 0.1s ease both;
}
@keyframes fadeIn { from{opacity:0} to{opacity:1} }
.filter-bar .faq-select { flex: 0 0 160px; }
.filter-bar .faq-input  { flex: 1; min-width: 140px; }
.btn-filter {
    background: rgba(134,197,97,0.15); border: 1px solid rgba(134,197,97,0.35);
    color: var(--accent2); border-radius: 8px; padding: 0.65rem 1.2rem;
    font-size: 0.85rem; font-weight: 600; cursor: pointer; transition: all 0.2s;
    white-space: nowrap;
}
.btn-filter:hover { background: rgba(134,197,97,0.28); }

/* Section header */
.section-hdr {
    display: flex; align-items: center; gap: 0.6rem;
    font-size: 1.1rem; font-weight: 700; color: var(--text2);
    margin-bottom: 1rem;
}

/* Question Cards */
.q-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 16px; padding: 1.25rem 1.5rem;
    margin-bottom: 1rem; backdrop-filter: blur(8px);
    transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
    animation: cardIn 0.4s ease both;
}
@keyframes cardIn {
    from { opacity:0; transform:translateY(12px); }
    to   { opacity:1; transform:translateY(0); }
}
.q-card:hover { transform: translateY(-3px); border-color: rgba(134,197,97,0.35); box-shadow: var(--shadow-sm); }

.q-text { font-size: 1rem; font-weight: 600; color: var(--text); margin-bottom: 0.5rem; line-height: 1.5; }

.q-meta { display: flex; gap: 0.5rem; flex-wrap: wrap; margin-bottom: 0.75rem; }
.q-tag {
    font-size: 0.72rem; font-weight: 600;
    padding: 0.2rem 0.6rem; border-radius: 20px;
    text-transform: uppercase; letter-spacing: 0.05em;
}
.q-tag.cat { background: rgba(134,197,97,0.18); color: var(--accent2); border: 1px solid rgba(134,197,97,0.3); }
.q-tag.tags { background: var(--surface2); color: var(--text3); border: 1px solid rgba(255,255,255,0.1); }
.q-tag.pub  { background: rgba(59,180,255,0.12); color: #7ecfff; border: 1px solid rgba(59,180,255,0.2); }
.q-tag.priv { background: var(--warn-light); color: var(--warn); border: 1px solid var(--warn); }

/* Thumbnail — clickable zoom */
.q-thumb-wrap { margin-bottom: 0.75rem; }
.q-thumb {
    width: 80px; height: 64px; object-fit: cover; border-radius: 8px;
    cursor: zoom-in; border: 2px solid rgba(134,197,97,0.25);
    transition: transform 0.2s, border-color 0.2s;
}
.q-thumb:hover { transform: scale(1.06); border-color: var(--accent2); }

.answer-box {
    background: var(--surface2); border-left: 3px solid #86c561;
    border-radius: 0 8px 8px 0; padding: 0.75rem 1rem;
    color: var(--text2); font-size: 0.9rem; line-height: 1.6;
}
.pending-badge {
    display: inline-flex; align-items: center; gap: 0.4rem;
    font-size: 0.82rem; color: var(--warn); font-weight: 600;
}

/* Lightbox */
.lightbox-overlay {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.88); backdrop-filter: blur(6px);
    align-items: center; justify-content: center;
    animation: lbIn 0.25s ease;
}
@keyframes lbIn { from{opacity:0} to{opacity:1} }
.lightbox-overlay.open { display: flex; }
.lightbox-overlay img {
    max-width: 92vw; max-height: 88vh;
    border-radius: 14px; box-shadow: 0 20px 60px rgba(0,0,0,0.7);
    animation: lbScale 0.3s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes lbScale { from{transform:scale(0.7)} to{transform:scale(1)} }
.lb-close {
    position: fixed; top: 1.25rem; right: 1.5rem;
    width: 40px; height: 40px; border-radius: 50%;
    background: rgba(255,255,255,0.15); border: none; color: #fff;
    font-size: 1.4rem; cursor: pointer; display: flex; align-items: center; justify-content: center;
    transition: background 0.2s;
}
.lb-close:hover { background: rgba(255,255,255,0.3); }
</style>

<!-- Lightbox -->
<div class="lightbox-overlay" id="lb" onclick="closeLb(event)">
    <button class="lb-close" onclick="document.getElementById('lb').classList.remove('open')">✕</button>
    <img id="lb-img" src="" alt="Question Image">
</div>

<div class="faq-wrap">

<!-- ── Ask Form ── -->
<div class="ask-card">
    <div class="ask-card-header">
        <div class="ask-card-icon">❓</div>
        <div class="ask-card-title">Ask an Expert</div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <div class="ask-grid">
            <div class="full">
                <label class="faq-label">Your Question</label>
                <textarea name="question" class="faq-textarea" required placeholder="Describe your farming problem in detail..."></textarea>
            </div>

            <div>
                <label class="faq-label">Category</label>
                <select name="category" class="faq-select">
                    <option value="pest">🐛 Pest</option>
                    <option value="crop">🌾 Crop</option>
                    <option value="irrigation">💧 Irrigation</option>
                    <option value="other">📦 Other</option>
                </select>
            </div>

            <div>
                <label class="faq-label">Visibility</label>
                <select name="is_public" class="faq-select">
                    <option value="1">🌍 Public</option>
                    <option value="0">🔒 Private</option>
                </select>
            </div>

            <div>
                <label class="faq-label">Tags</label>
                <input type="text" name="tags" class="faq-input" placeholder="e.g. rice, aphid, yellow leaf">
            </div>

            <div>
                <label class="faq-label">Attach Image (optional)</label>
                <div class="file-upload-box">
                    <input type="file" name="image" accept="image/*" onchange="showFileName(this)">
                    <div class="file-upload-text" id="file-name-display">📎 Click to upload image</div>
                </div>
            </div>

            <div class="full" style="margin-top:0.5rem">
                <button type="submit" class="btn-submit-faq">🚀 Submit Question</button>
            </div>
        </div>
    </form>
</div>

<!-- ── Filter ── -->
<form method="GET" class="filter-bar">
    <select name="category" class="faq-select">
        <option value="">All Categories</option>
        <option value="pest" <?= $category==='pest'?'selected':'' ?>>🐛 Pest</option>
        <option value="crop" <?= $category==='crop'?'selected':'' ?>>🌾 Crop</option>
        <option value="irrigation" <?= $category==='irrigation'?'selected':'' ?>>💧 Irrigation</option>
    </select>
    <input type="text" name="search" class="faq-input" placeholder="🔍 Search questions or tags..." value="<?= htmlspecialchars($search) ?>">
    <button type="submit" class="btn-filter">Filter</button>
</form>

<!-- ── FAQ List ── -->
<div class="section-hdr">📚 Community FAQ</div>

<?php $i=0; while($q = $questions->fetch_assoc()): $i++; ?>
<div class="q-card" style="animation-delay:<?= $i*0.05 ?>s">

    <div class="q-text">Q: <?= htmlspecialchars($q['question']) ?></div>

    <div class="q-meta">
        <span class="q-tag cat">📂 <?= htmlspecialchars($q['category']) ?></span>
        <?php if($q['tags']): ?>
            <span class="q-tag tags">🏷️ <?= htmlspecialchars($q['tags']) ?></span>
        <?php endif; ?>
        <span class="q-tag <?= $q['is_public'] ? 'pub' : 'priv' ?>">
            <?= $q['is_public'] ? '🌍 Public' : '🔒 Private' ?>
        </span>
    </div>

    <?php if (!empty($q['image_url'])): ?>
    <div class="q-thumb-wrap">
        <img class="q-thumb" src="<?= htmlspecialchars($q['image_url']) ?>"
             onclick="openLb('<?= htmlspecialchars($q['image_url']) ?>')"
             alt="Question image">
    </div>
    <?php endif; ?>

    <?php if ($q['answer']): ?>
        <div class="answer-box">💡 <?= htmlspecialchars($q['answer']) ?></div>
    <?php else: ?>
        <div class="pending-badge">⏳ Waiting for expert answer…</div>
    <?php endif; ?>

</div>
<?php endwhile; ?>

<?php if($i === 0): ?>
<div style="text-align:center; color:var(--text4); padding:3rem 0; font-size:0.95rem;">
    No questions found. Be the first to ask! ☝️
</div>
<?php endif; ?>

</div>

<script>
function openLb(src) {
    document.getElementById('lb-img').src = src;
    document.getElementById('lb').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLb(e) {
    if (e.target === document.getElementById('lb')) {
        document.getElementById('lb').classList.remove('open');
        document.body.style.overflow = '';
    }
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        document.getElementById('lb').classList.remove('open');
        document.body.style.overflow = '';
    }
});
function showFileName(input) {
    const el = document.getElementById('file-name-display');
    el.textContent = input.files.length ? '✅ ' + input.files[0].name : '📎 Click to upload image';
}
</script>

<?php include 'layout_end.php'; ?>