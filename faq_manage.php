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

$pageTitle = 'Manage Questions';
$activeNav = 'faq';
include 'layout.php';
?>

<style>
/* ── FAQ Manage ── */
.fmg-wrap { max-width: 920px; margin: 0 auto; padding: 0 1rem 3rem; }

.fmg-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 1.75rem; flex-wrap: wrap; gap: 1rem;
}
.fmg-title {
    display: flex; align-items: center; gap: 0.65rem;
    font-size: 1.35rem; font-weight: 800; color: var(--text); letter-spacing: -0.01em;
}
.fmg-title-icon {
    width: 42px; height: 42px; border-radius: 12px;
    background: linear-gradient(135deg,#86c561,#4a9e2f);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; box-shadow: 0 4px 12px rgba(134,197,97,0.4);
}

/* Stats row */
.fmg-stats { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 2rem; }
.stat-pill {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 0.6rem 1.1rem;
    display: flex; align-items: center; gap: 0.5rem;
    font-size: 0.82rem; color: var(--text3); backdrop-filter: blur(8px);
}
.stat-pill strong { color: var(--text2); font-size: 1.1rem; font-weight: 700; }

/* Question Card */
.qm-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px; overflow: hidden;
    margin-bottom: 1.1rem; backdrop-filter: blur(10px);
    transition: transform 0.22s, border-color 0.22s, box-shadow 0.22s;
    animation: qmIn 0.4s ease both;
    box-shadow: var(--shadow-sm);
}
@keyframes qmIn {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
.qm-card:hover { transform: translateY(-3px); border-color: rgba(134,197,97,0.32); box-shadow: var(--shadow-lg); }

/* Accent bar */
.qm-card.pending   { border-left: 4px solid #ffa030; }
.qm-card.answered  { border-left: 4px solid #86c561; }

.qm-body { display: flex; gap: 0; }
.qm-content { flex: 1; padding: 1.25rem 1.5rem; }

/* Image panel (right) */
.qm-img-panel {
    width: 150px; flex-shrink: 0;
    display: flex; align-items: stretch;
    overflow: hidden;
}
.qm-img {
    width: 150px; height: 100%; min-height: 140px;
    object-fit: cover; cursor: zoom-in;
    transition: transform 0.3s, filter 0.3s;
    filter: brightness(0.9);
    display: block;
}
.qm-img:hover { transform: scale(1.05); filter: brightness(1.1); }
.qm-img-overlay {
    position: relative; width: 150px; flex-shrink: 0; overflow: hidden;
}
.qm-img-overlay::after {
    content: '🔍';
    position: absolute; inset:0;
    background: rgba(0,0,0,0); display:flex; align-items:center; justify-content:center;
    font-size: 1.5rem; opacity:0; transition: all 0.2s;
    pointer-events: none;
}
.qm-img-overlay:hover::after { background: rgba(0,0,0,0.35); opacity:1; }

.qm-question { font-size: 1rem; font-weight: 700; color: var(--text); line-height: 1.55; margin-bottom: 0.5rem; }
.qm-meta { display: flex; gap: 0.45rem; flex-wrap: wrap; margin-bottom: 0.8rem; }
.qm-badge {
    font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.55rem;
    border-radius: 20px; text-transform: uppercase; letter-spacing: 0.06em;
}
.qm-badge.cat   { background: rgba(134,197,97,0.15); color: var(--accent2); border: 1px solid var(--border); }
.qm-badge.user  { background: rgba(100,160,255,0.12); color: #90bfff; border: 1px solid rgba(100,160,255,0.2); }
.qm-badge.pub   { background: rgba(60,210,130,0.1);  color: #5dd4a0; border: 1px solid rgba(60,210,130,0.2); }
.qm-badge.priv  { background: var(--warn-light);  color: var(--warn); border: 1px solid var(--warn); }

/* Answer Box */
.qm-answer-box {
    background: var(--surface2);
    border: 1px solid var(--border);
    border-radius: 10px; padding: 0.85rem 1rem;
    margin-top: 0.7rem;
}
.qm-answer-label { font-size: 0.7rem; font-weight: 800; color: var(--accent2); text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.35rem; }
.qm-answer-text  { font-size: 0.88rem; color: var(--text2); line-height: 1.6; }

/* Status + action row */
.qm-footer { display: flex; align-items: center; justify-content: space-between; margin-top: 1rem; flex-wrap: wrap; gap: 0.5rem; }
.status-answered { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 700; color: var(--accent2); }
.status-pending  { display: inline-flex; align-items: center; gap: 0.4rem; font-size: 0.82rem; font-weight: 700; color: var(--warn); }

.btn-answer {
    background: linear-gradient(135deg, #86c561, #4a9e2f);
    color: #0d1f0d; font-weight: 700; font-size: 0.82rem;
    border: none; border-radius: 8px; padding: 0.5rem 1.2rem;
    cursor: pointer; text-decoration: none;
    transition: all 0.2s; display: inline-flex; align-items: center; gap: 0.35rem;
    box-shadow: 0 3px 10px rgba(134,197,97,0.3);
}
.btn-answer:hover { transform: translateY(-2px); box-shadow: 0 5px 16px rgba(134,197,97,0.5); }

/* Empty state */
.empty-fmg {
    text-align: center; padding: 4rem 1rem; color: var(--text4);
}
.empty-fmg .icon { font-size: 3.5rem; margin-bottom: 1rem; }

/* Lightbox */
/* Lightbox */
.lb-overlay {
    display: none !important; 
    position: fixed !important; 
    inset: 0 !important; 
    z-index: 99999 !important;
    background: rgba(0,0,0,0.95) !important; 
    backdrop-filter: blur(8px);
    align-items: center !important; 
    justify-content: center !important;
    width: 100vw !important;
    height: 100vh !important;
    overflow: hidden !important;
}
.lb-overlay.open { 
    display: flex !important; 
}
.lb-overlay img {
    max-width: 90% !important;   
    max-height: 85% !important;  
    width: auto !important;
    height: auto !important;
    object-fit: contain !important; 
    border-radius: 14px; 
    box-shadow: 0 24px 64px rgba(0,0,0,0.8);
    margin: 0 auto !important;   
    animation: lbZoom 0.25s ease-out both;
}
@keyframes lbZoom { from{transform:scale(0.85);opacity:0} to{transform:scale(1);opacity:1} }
.lb-close {
    position: fixed !important; top: 1.5rem !important; right: 1.5rem !important;
    width: 44px; height: 44px; border-radius: 50%;
    background: rgba(255,255,255,0.2) !important; border: 1px solid rgba(255,255,255,0.3) !important;
    color: #fff !important; font-size: 1.3rem; cursor: pointer;
    display: flex; align-items: center; justify-content: center;
    z-index: 100000 !important;
}
.lb-close:hover { background: rgba(255,255,255,0.4) !important; }
.lb-hint {
    position: fixed !important; bottom: 1.5rem !important; left: 50% !important; transform: translateX(-50%) !important;
    background: rgba(0,0,0,0.6) !important; color: rgba(255,255,255,0.6) !important;
    font-size: 0.72rem; padding: 0.35rem 0.85rem; border-radius: 20px;
    z-index: 100000 !important; pointer-events: none;
}
</style>

<!-- Lightbox -->
<div class="lb-overlay" id="lb" onclick="closeLb(event)">
    <button class="lb-close" onclick="closeLbBtn()">✕</button>
    <img id="lb-img" src="" alt="Question image">
    <div class="lb-hint">Press ESC or click outside to close</div>
</div>

<div class="fmg-wrap">

    <div class="fmg-header">
        <div class="fmg-title">
            <div class="fmg-title-icon">❓</div>
            All Questions
        </div>
    </div>

    <?php
    // Collect all for stats, then re-render
    $allQ = [];
    while($row = $questions->fetch_assoc()) $allQ[] = $row;
    $total    = count($allQ);
    $answered = count(array_filter($allQ, fn($r) => $r['answer_id']));
    $pending  = $total - $answered;
    ?>

    <div class="fmg-stats">
        <div class="stat-pill">📋 Total <strong><?= $total ?></strong></div>
        <div class="stat-pill">✅ Answered <strong><?= $answered ?></strong></div>
        <div class="stat-pill">⏳ Pending <strong><?= $pending ?></strong></div>
    </div>

    <?php if(empty($allQ)): ?>
        <div class="empty-fmg">
            <div class="icon">📭</div>
            <p>No questions submitted yet.</p>
        </div>
    <?php else: ?>

    <?php foreach($allQ as $i => $q): ?>
    <div class="qm-card <?= $q['answer_id'] ? 'answered' : 'pending' ?>"
         style="animation-delay:<?= $i * 0.05 ?>s">
        <div class="qm-body">

            <div class="qm-content">
                <div class="qm-question"><?= htmlspecialchars($q['question']) ?></div>

                <div class="qm-meta">
                    <span class="qm-badge cat">📂 <?= htmlspecialchars($q['category']) ?></span>
                    <span class="qm-badge user">👨‍🌾 <?= htmlspecialchars($q['name']) ?></span>
                    <span class="qm-badge <?= $q['is_public'] ? 'pub' : 'priv' ?>">
                        <?= $q['is_public'] ? '🌍 Public' : '🔒 Private' ?>
                    </span>
                </div>

                <?php if ($q['answer_id']): ?>
                    <div class="qm-answer-box">
                        <div class="qm-answer-label">Expert Answer</div>
                        <div class="qm-answer-text">💡 <?= htmlspecialchars($q['answer']) ?></div>
                    </div>
                <?php endif; ?>

                <div class="qm-footer">
                    <?php if ($q['answer_id']): ?>
                        <span class="status-answered">✔ Answered</span>
                    <?php else: ?>
                        <span class="status-pending">⏳ Pending response</span>
                        <a href="faq_answer.php?id=<?= $q['id'] ?>" class="btn-answer">✍️ Answer</a>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($q['image_url'])): ?>
            <div class="qm-img-overlay"
                 onclick="openLb('<?= htmlspecialchars($q['image_url']) ?>')"
                 title="Click to view full image">
                <img class="qm-img" src="<?= htmlspecialchars($q['image_url']) ?>" alt="Question image">
            </div>
            <?php endif; ?>

        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
function openLb(src) {
    const lb = document.getElementById('lb');
    const lbImg = document.getElementById('lb-img');
    
    // ইমেজ সোর্স সেট করা
    lbImg.src = src;
    
    // লাইটবক্স ওপেন করা
    lb.classList.add('open');
    document.body.style.overflow = 'hidden';
    
    // জাভাস্ক্রিপ্ট দিয়ে সরাসরি ইমেজের সাইজ কন্ট্রোল (সেফটি মেজার)
    lbImg.style.maxWidth = '90vw';
    lbImg.style.maxHeight = '85vh';
    lbImg.style.width = 'auto';
    lbImg.style.height = 'auto';
    lbImg.style.display = 'block';
    lbImg.style.margin = 'auto';
}

function closeLbBtn() {
    document.getElementById('lb').classList.remove('open');
    document.body.style.overflow = '';
    document.getElementById('lb-img').src = ''; // ইমেজ মেমোরি ক্লিয়ার
}

function closeLb(e) {
    if (e.target === document.getElementById('lb')) {
        closeLbBtn();
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        closeLbBtn();
    }
});
</script>

<?php include 'layout_end.php'; ?>