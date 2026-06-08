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
$existing = $conn->query("SELECT * FROM answers WHERE question_id=$qid")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $answer = mysqli_real_escape_string($conn, $_POST['answer']);

    if ($existing) {
        $conn->query("UPDATE answers SET answer='$answer', expert_id=$expert_id WHERE question_id=$qid");
    } else {
        $conn->query("INSERT INTO answers (question_id, expert_id, answer) VALUES ($qid, $expert_id, '$answer')");
    }

    $farmer = $conn->query("SELECT user_id FROM questions WHERE id=$qid")->fetch_assoc();
    $fid = (int)$farmer['user_id'];
    $conn->query("INSERT INTO notifications (user_id, title, message, type)
        VALUES ($fid, 'Answer Received', 'Expert answered your question.', 'faq')");

    header("Location: faq_manage.php");
    exit;
}

$pageTitle = 'Answer Question';
$activeNav = 'faq';
include 'layout.php';
?>

<style>
.ans-wrap { max-width: 700px; margin: 0 auto; padding: 0 1rem 3rem; }

/* Back link */
.ans-back {
    display: inline-flex; align-items: center; gap: 0.4rem;
    color: var(--accent2); font-size: 0.85rem; font-weight: 600;
    text-decoration: none; margin-bottom: 1.5rem;
    transition: gap 0.2s;
}
.ans-back:hover { gap: 0.65rem; }

/* Question Preview */
.q-preview {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px; padding: 1.5rem;
    margin-bottom: 1.5rem; backdrop-filter: blur(10px);
    box-shadow: var(--shadow-sm);
    animation: slideUp 0.4s ease both;
}
@keyframes slideUp { from{opacity:0;transform:translateY(16px)} to{opacity:1;transform:translateY(0)} }

.qp-label { font-size: 0.7rem; font-weight: 800; color: var(--accent2); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.6rem; }
.qp-text  { font-size: 1.05rem; color: var(--text); font-weight: 600; line-height: 1.6; }

/* Image in preview */
.qp-img-wrap { margin-top: 1rem; }
.qp-img {
    max-width: 280px; border-radius: 12px; cursor: zoom-in;
    border: 2px solid rgba(134,197,97,0.25);
    transition: transform 0.25s, box-shadow 0.25s;
}
.qp-img:hover { transform: scale(1.03); box-shadow: var(--shadow-sm); }

/* Answer Form */
.ans-form-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px; padding: 1.75rem;
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow-sm);
    animation: slideUp 0.4s 0.1s ease both;
}
.ans-form-title {
    display: flex; align-items: center; gap: 0.65rem;
    font-size: 1.15rem; font-weight: 800; color: var(--text);
    margin-bottom: 1.25rem;
}
.ans-form-icon {
    width: 40px; height: 40px; border-radius: 10px;
    background: linear-gradient(135deg,#86c561,#4a9e2f);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.1rem; box-shadow: 0 3px 10px rgba(134,197,97,0.35);
}

.ans-textarea {
    width: 100%; box-sizing: border-box;
    min-height: 160px; resize: vertical;
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 12px; padding: 0.9rem 1.1rem;
    color: var(--text2); font-size: 0.95rem; font-family: inherit; line-height: 1.6;
    transition: border-color 0.2s, box-shadow 0.2s; outline: none;
}
.ans-textarea:focus { border-color: var(--accent2); box-shadow: 0 0 0 3px rgba(134,197,97,0.15); }
.ans-textarea::placeholder { color: var(--text4); }

.ans-char-count { text-align: right; font-size: 0.75rem; color: var(--text4); margin-top: 0.35rem; }

.ans-actions { display: flex; gap: 0.75rem; margin-top: 1.25rem; flex-wrap: wrap; }
.btn-save {
    background: linear-gradient(135deg,#86c561,#4a9e2f);
    color: #0d1f0d; font-weight: 800; font-size: 0.95rem;
    border: none; border-radius: 10px; padding: 0.75rem 2rem;
    cursor: pointer; transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(134,197,97,0.35);
    display: flex; align-items: center; gap: 0.45rem;
}
.btn-save:hover { transform: translateY(-2px); box-shadow: 0 7px 22px rgba(134,197,97,0.55); }
.btn-cancel {
    background: var(--surface2); border: 1px solid rgba(255,255,255,0.12);
    color: var(--text3); font-size: 0.9rem; border-radius: 10px; padding: 0.75rem 1.5rem;
    cursor: pointer; text-decoration: none; transition: all 0.2s;
    display: flex; align-items: center; gap: 0.35rem;
}
.btn-cancel:hover { background: rgba(255,255,255,0.1); color: #c5ddb5; }

/* Existing answer notice */
.existing-notice {
    background: var(--surface2); border: 1px solid var(--border);
    border-radius: 10px; padding: 0.75rem 1rem;
    font-size: 0.8rem; color: var(--accent2); margin-bottom: 1rem;
    display: flex; align-items: center; gap: 0.5rem;
}

/* Lightbox */
.lightbox-overlay {
    display: none; position: fixed; inset: 0; z-index: 9999;
    background: rgba(0,0,0,0.9); backdrop-filter: blur(8px);
    align-items: center; justify-content: center;
}
.lightbox-overlay.open { display: flex; }
.lightbox-overlay img {
    max-width: 94vw; max-height: 90vh; border-radius: 14px;
    box-shadow: 0 24px 64px rgba(0,0,0,0.8);
    animation: lbZoom 0.3s cubic-bezier(.34,1.56,.64,1) both;
}
@keyframes lbZoom { from{transform:scale(0.65);opacity:0} to{transform:scale(1);opacity:1} }
.lb-close {
    position: fixed; top: 1rem; right: 1.25rem;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.12); border: 1.5px solid rgba(255,255,255,0.2);
    color: #fff; font-size: 1.3rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    transition: background 0.2s; z-index: 10000;
}
.lb-close:hover { background: rgba(255,255,255,0.25); }
</style>

<div class="lightbox-overlay" id="lb" onclick="if(event.target===this){closeLb()}">
    <button class="lb-close" onclick="closeLb()">✕</button>
    <img id="lb-img" src="" alt="">
</div>

<div class="ans-wrap">

    <a href="faq_manage.php" class="ans-back">← Back to Questions</a>

    <!-- Question Preview -->
    <div class="q-preview">
        <div class="qp-label">Farmer's Question</div>
        <div class="qp-text"><?= htmlspecialchars($q['question']) ?></div>

        <?php if (!empty($q['image_url'])): ?>
        <div class="qp-img-wrap">
            <img class="qp-img"
                 src="<?= htmlspecialchars($q['image_url']) ?>"
                 onclick="openLb('<?= htmlspecialchars($q['image_url']) ?>')"
                 alt="Question image">
        </div>
        <?php endif; ?>
    </div>

    <!-- Answer Form -->
    <div class="ans-form-card">
        <div class="ans-form-title">
            <div class="ans-form-icon">💡</div>
            <?= $existing ? 'Update Your Answer' : 'Write Your Answer' ?>
        </div>

        <?php if ($existing): ?>
        <div class="existing-notice">
            ✏️ You have already answered this question — you can update it below.
        </div>
        <?php endif; ?>

        <form method="POST">
            <textarea name="answer" class="ans-textarea" id="ans-ta"
                placeholder="Write a clear, helpful answer for the farmer..."
                oninput="updateCount()"><?= htmlspecialchars($existing['answer'] ?? '') ?></textarea>
            <div class="ans-char-count" id="char-count">0 characters</div>

            <div class="ans-actions">
                <button type="submit" class="btn-save">💾 Save Answer</button>
                <a href="faq_manage.php" class="btn-cancel">✕ Cancel</a>
            </div>
        </form>
    </div>

</div>

<script>
function updateCount() {
    const ta = document.getElementById('ans-ta');
    document.getElementById('char-count').textContent = ta.value.length + ' characters';
}
updateCount();

function openLb(src) {
    document.getElementById('lb-img').src = src;
    document.getElementById('lb').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLb() {
    document.getElementById('lb').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if(e.key==='Escape') closeLb(); });
</script>

<?php include 'layout_end.php'; ?>