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

$pageTitle = 'My Questions';
$activeNav = 'faq';
include 'layout.php';
?>

<style>
.mq-wrap { max-width: 860px; margin: 0 auto; padding: 0 1rem 3rem; }

/* Header */
.mq-header {
    display: flex; align-items: center; justify-content: space-between;
    margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;
    padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);
}
.mq-title {
    display: flex; align-items: center; gap: 0.65rem;
    font-size: 1.4rem; font-weight: 900;
    color: var(--text); font-family: var(--font-display);
}
.mq-title-icon {
    width: 44px; height: 44px; border-radius: 12px;
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    display: flex; align-items: center; justify-content: center;
    font-size: 1.2rem; box-shadow: 0 4px 12px var(--accent-glow);
}
.mq-ask-btn {
    background: linear-gradient(135deg, var(--accent), var(--accent2));
    color: #fff; font-weight: 700; font-size: 0.85rem;
    border-radius: 10px; padding: 0.6rem 1.3rem;
    text-decoration: none; transition: all 0.2s;
    display: inline-flex; align-items: center; gap: 0.4rem;
    box-shadow: 0 3px 10px var(--accent-glow);
}
.mq-ask-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 18px var(--accent-glow); color: #fff; }

/* Stats */
.mq-stats { display: flex; gap: 0.75rem; flex-wrap: wrap; margin-bottom: 1.75rem; }
.mq-stat {
    background: var(--surface); border: 1px solid var(--border);
    border-radius: 12px; padding: 0.6rem 1.1rem;
    display: flex; align-items: center; gap: 0.5rem;
    font-size: 0.82rem; color: var(--text3);
    box-shadow: var(--shadow-xs);
}
.mq-stat strong { color: var(--text2); font-size: 1.05rem; font-weight: 700; }

/* Question card */
.mq-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px; overflow: hidden;
    margin-bottom: 1rem;
    box-shadow: var(--shadow-sm);
    transition: transform 0.22s, border-color 0.22s, box-shadow 0.22s;
    animation: mqIn 0.4s ease both;
    position: relative;
}
@keyframes mqIn {
    from { opacity:0; transform:translateY(14px); }
    to   { opacity:1; transform:translateY(0); }
}
.mq-card:hover { transform: translateY(-3px); border-color: var(--border2); box-shadow: var(--shadow-md); }

/* Answered vs pending left border */
.mq-card.answered { border-left: 4px solid var(--accent2); }
.mq-card.pending  { border-left: 4px solid var(--warn); }

.mq-body { display: flex; }
.mq-content { flex: 1; padding: 1.25rem 1.5rem; }

/* Image panel */
.mq-img-panel {
    width: 140px; flex-shrink: 0; overflow: hidden;
    position: relative; cursor: zoom-in;
}
.mq-img-panel img {
    width: 140px; height: 100%; min-height: 130px;
    object-fit: cover; display: block;
    transition: transform 0.3s, filter 0.3s;
    filter: brightness(0.92);
}
.mq-img-panel:hover img { transform: scale(1.06); filter: brightness(1.05); }
.mq-img-panel::after {
    content: '🔍';
    position: absolute; inset: 0;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; background: rgba(0,0,0,0);
    opacity: 0; transition: all 0.2s; pointer-events: none;
}
.mq-img-panel:hover::after { background: rgba(0,0,0,0.3); opacity: 1; }

.mq-question {
    font-size: 0.98rem; font-weight: 700; color: var(--text);
    line-height: 1.55; margin-bottom: 0.6rem;
}

/* Meta tags */
.mq-meta { display: flex; gap: 0.4rem; flex-wrap: wrap; margin-bottom: 0.85rem; }
.mq-tag {
    font-size: 0.7rem; font-weight: 700; padding: 0.2rem 0.55rem;
    border-radius: 20px; text-transform: uppercase; letter-spacing: 0.06em;
}
.mq-tag.cat  { background: var(--accent-light); color: var(--accent2); border: 1px solid var(--border2); }
.mq-tag.tags { background: var(--surface2);     color: var(--text3);   border: 1px solid var(--border); }
.mq-tag.pub  { background: var(--info-light);   color: var(--info);    border: 1px solid var(--info); }
.mq-tag.priv { background: var(--warn-light);   color: var(--warn);    border: 1px solid var(--warn); }

/* Answer box */
.mq-answer {
    background: var(--surface2); border: 1px solid var(--border);
    border-left: 3px solid var(--accent2);
    border-radius: 0 10px 10px 0; padding: 0.8rem 1rem;
    margin-top: 0.5rem;
}
.mq-answer-label {
    font-size: 0.68rem; font-weight: 800; color: var(--accent2);
    text-transform: uppercase; letter-spacing: 0.1em; margin-bottom: 0.3rem;
    font-family: var(--font-mono);
}
.mq-answer-text { font-size: 0.88rem; color: var(--text2); line-height: 1.6; }

/* Pending badge */
.mq-pending {
    display: inline-flex; align-items: center; gap: 0.4rem;
    font-size: 0.82rem; font-weight: 700; color: var(--warn);
    margin-top: 0.5rem;
}

/* Date */
.mq-date { font-size: 0.72rem; color: var(--text4); margin-top: 0.75rem; font-family: var(--font-mono); }

/* Empty state */
.mq-empty {
    text-align: center; padding: 5rem 1rem;
    animation: mqIn 0.4s ease both;
}
.mq-empty-icon { font-size: 3.5rem; margin-bottom: 1rem; }
.mq-empty-title { font-size: 1.1rem; font-weight: 700; color: var(--text3); margin-bottom: 0.4rem; }
.mq-empty-sub   { font-size: 0.85rem; color: var(--text4); margin-bottom: 1.5rem; }

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
<div class="lb-overlay" id="lb" onclick="if(event.target===this)closeLb()">
    <button class="lb-close" onclick="closeLb()">✕</button>
    <img id="lb-img" src="" alt="">
    <div class="lb-hint">ESC or click outside to close</div>
</div>

<div class="mq-wrap">

    <div class="mq-header">
        <div class="mq-title">
            <div class="mq-title-icon">📝</div>
            My Questions
        </div>
        <a href="faq.php" class="mq-ask-btn">❓ Ask New Question</a>
    </div>

    <?php
    $all = [];
    while($r = $rows->fetch_assoc()) $all[] = $r;
    $total    = count($all);
    $answered = count(array_filter($all, fn($r) => $r['answer']));
    $pending  = $total - $answered;
    ?>

    <?php if($total > 0): ?>
    <div class="mq-stats">
        <div class="mq-stat">📋 Total <strong><?= $total ?></strong></div>
        <div class="mq-stat">✅ Answered <strong><?= $answered ?></strong></div>
        <div class="mq-stat">⏳ Pending <strong><?= $pending ?></strong></div>
    </div>
    <?php endif; ?>

    <?php if(empty($all)): ?>
    <div class="mq-empty">
        <div class="mq-empty-icon">💬</div>
        <div class="mq-empty-title">No questions yet</div>
        <div class="mq-empty-sub">Ask our experts anything about your crops or farm.</div>
        <a href="faq.php" class="mq-ask-btn">❓ Ask Your First Question</a>
    </div>

    <?php else: ?>
    <?php foreach($all as $i => $r): ?>

    <div class="mq-card <?= $r['answer'] ? 'answered' : 'pending' ?>"
         style="animation-delay:<?= $i * 0.05 ?>s">
        <div class="mq-body">

            <div class="mq-content">
                <div class="mq-question">❓ <?= htmlspecialchars($r['question']) ?></div>

                <div class="mq-meta">
                    <?php if(!empty($r['category'])): ?>
                        <span class="mq-tag cat">📂 <?= htmlspecialchars($r['category']) ?></span>
                    <?php endif; ?>
                    <?php if(!empty($r['tags'])): ?>
                        <span class="mq-tag tags">🏷️ <?= htmlspecialchars($r['tags']) ?></span>
                    <?php endif; ?>
                    <span class="mq-tag <?= $r['is_public'] ? 'pub' : 'priv' ?>">
                        <?= $r['is_public'] ? '🌍 Public' : '🔒 Private' ?>
                    </span>
                </div>

                <?php if($r['answer']): ?>
                <div class="mq-answer">
                    <div class="mq-answer-label">Expert Answer</div>
                    <div class="mq-answer-text">💡 <?= htmlspecialchars($r['answer']) ?></div>
                </div>
                <?php else: ?>
                <div class="mq-pending">⏳ Waiting for expert reply…</div>
                <?php endif; ?>

                <?php if(!empty($r['created_at'])): ?>
                <div class="mq-date">🕐 <?= date('d M Y, h:i A', strtotime($r['created_at'])) ?></div>
                <?php endif; ?>
            </div>

            <?php if(!empty($r['image_url'])): ?>
            <div class="mq-img-panel" onclick="openLb('<?= htmlspecialchars($r['image_url']) ?>')">
                <img src="<?= htmlspecialchars($r['image_url']) ?>" alt="Question image">
            </div>
            <?php endif; ?>

        </div>
    </div>

    <?php endforeach; ?>
    <?php endif; ?>

</div>

<script>
function openLb(src) {
    document.getElementById('lb-img').src = src;
    document.getElementById('lb').classList.add('open');
    document.body.style.overflow = 'hidden';
}
function closeLb() {
    document.getElementById('lb').classList.remove('open');
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if(e.key === 'Escape') closeLb(); });
</script>

<?php include 'layout_end.php'; ?>