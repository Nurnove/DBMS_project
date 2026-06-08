<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'My Applications';
$activeNav = 'loan_hub';

$user = currentUser($conn);
$uid = (int)$user['id'];

$applications = $conn->query("
    SELECT
        la.*,
        lp.name AS loan_name,
        lp.interest_rate,
        prov.name AS provider_name,
        prov.logo_emoji
    FROM loan_applications la
    JOIN loan_products lp ON la.product_id = lp.id
    JOIN loan_providers prov ON lp.provider_id = prov.id
    WHERE la.user_id = $uid
    ORDER BY la.id DESC
");

include 'layout.php';
?>

<style>
/* ── My Applications ── */
.myapp-wrap { max-width: 1000px; margin: 0 auto; padding: 0 1rem 3rem; }

/* Page Header */
.myapp-header {
    display: flex; align-items: flex-end; justify-content: space-between;
    margin-bottom: 2rem; flex-wrap: wrap; gap: 1rem;
    padding-bottom: 1.25rem; border-bottom: 1px solid var(--border);
}
.myapp-title-block { }
.myapp-eyebrow { font-size: 0.72rem; font-weight: 700; color: var(--accent2); text-transform: uppercase; letter-spacing: 0.12em; margin-bottom: 0.3rem; }
.myapp-title { font-size: 1.7rem; font-weight: 900; color: var(--text); letter-spacing: -0.02em; display: flex; align-items: center; gap: 0.5rem; }
.myapp-browse-btn {
    background: rgba(134,197,97,0.12); border: 1.5px solid rgba(134,197,97,0.3);
    color: var(--accent2); font-size: 0.85rem; font-weight: 700;
    border-radius: 10px; padding: 0.6rem 1.3rem;
    text-decoration: none; transition: all 0.2s;
    display: inline-flex; align-items: center; gap: 0.4rem;
}
.myapp-browse-btn:hover { background: rgba(134,197,97,0.22); transform: translateY(-1px); }

/* Grid */
.app-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 1.25rem;
}

/* Application Card */
.app-card {
    background: var(--surface);
    border: 1px solid var(--border);
    border-radius: 18px; overflow: hidden;
    backdrop-filter: blur(10px);
    box-shadow: var(--shadow-sm);
    transition: transform 0.22s, border-color 0.22s, box-shadow 0.22s;
    animation: appIn 0.4s ease both;
    position: relative;
}
@keyframes appIn {
    from { opacity:0; transform:translateY(18px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.app-card:hover { transform: translateY(-5px); border-color: rgba(134,197,97,0.32); box-shadow: var(--shadow-lg); }

/* Card top accent stripe */
.app-card::before {
    content: '';
    position: absolute; top:0; left:0; right:0; height: 3px;
    background: linear-gradient(90deg, #86c561, #4a9e2f, #86c561);
    background-size: 200% 100%;
    animation: shimmer 3s linear infinite;
}
@keyframes shimmer { from{background-position:200% 0} to{background-position:-200% 0} }

/* Bank header */
.app-card-top {
    padding: 1.2rem 1.4rem 0.9rem;
    border-bottom: 1px solid rgba(134,197,97,0.1);
    display: flex; align-items: center; gap: 0.75rem;
}
.app-bank-emoji {
    width: 46px; height: 46px; border-radius: 14px; flex-shrink: 0;
    background: linear-gradient(135deg, rgba(134,197,97,0.18), rgba(134,197,97,0.06));
    border: 1px solid var(--border);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.5rem;
}
.app-bank-info { }
.app-bank-name { font-size: 0.78rem; font-weight: 700; color: var(--accent2); text-transform: uppercase; letter-spacing: 0.06em; }
.app-loan-name { font-size: 1rem; font-weight: 700; color: var(--text); margin-top: 0.1rem; line-height: 1.3; }

/* Stats grid */
.app-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
.app-stat {
    padding: 0.9rem 1.2rem;
    border-right: 1px solid var(--border);
    border-bottom: 1px solid var(--border);
}
.app-stat:nth-child(2n) { border-right: none; }
.app-stat:nth-last-child(-n+2) { border-bottom: none; }

.app-stat-label { font-size: 0.68rem; font-weight: 600; color: var(--text3); text-transform: uppercase; letter-spacing: 0.07em; margin-bottom: 0.3rem; display: flex; align-items: center; gap: 0.3rem; }
.app-stat-value { font-size: 1rem; font-weight: 800; color: var(--text2); }
.app-stat-value.amount { color: var(--accent2); font-size: 1.1rem; }

/* Empty state */
.app-empty {
    text-align: center; padding: 5rem 1rem;
    animation: appIn 0.5s ease both;
}
.app-empty-icon { font-size: 4rem; margin-bottom: 1rem; filter: grayscale(0.3); }
.app-empty-title { font-size: 1.2rem; font-weight: 700; color: var(--text3); margin-bottom: 0.5rem; }
.app-empty-sub   { font-size: 0.88rem; color: var(--text4); margin-bottom: 1.5rem; }
.btn-browse-loans {
    background: linear-gradient(135deg,#86c561,#4a9e2f);
    color: #0d1f0d; font-weight: 800; font-size: 0.95rem;
    border: none; border-radius: 10px; padding: 0.75rem 2rem;
    cursor: pointer; text-decoration: none;
    transition: all 0.2s;
    box-shadow: 0 4px 14px rgba(134,197,97,0.35);
    display: inline-flex; align-items: center; gap: 0.4rem;
}
.btn-browse-loans:hover { transform: translateY(-2px); box-shadow: 0 7px 22px rgba(134,197,97,0.5); }
</style>

<div class="myapp-wrap">

    <div class="myapp-header">
        <div class="myapp-title-block">
            <div class="myapp-eyebrow">Loan Hub</div>
            <div class="myapp-title">📄 My Applications</div>
        </div>
        <a href="loan_hub.php" class="myapp-browse-btn">🏦 Browse Loans</a>
    </div>

    <?php if($applications->num_rows == 0): ?>

    <div class="app-empty">
        <div class="app-empty-icon">📭</div>
        <div class="app-empty-title">No applications yet</div>
        <div class="app-empty-sub">Browse available loan products and save the ones that fit your needs.</div>
        <a href="loan_hub.php" class="btn-browse-loans">🔍 Browse Loans</a>
    </div>

    <?php else: ?>

    <div class="app-grid">

    <?php $i=0; while($app = $applications->fetch_assoc()): $i++; ?>

    <div class="app-card" style="animation-delay:<?= $i*0.07 ?>s">

        <div class="app-card-top">
            <div class="app-bank-emoji"><?= $app['logo_emoji'] ?></div>
            <div class="app-bank-info">
                <div class="app-bank-name"><?= htmlspecialchars($app['provider_name']) ?></div>
                <div class="app-loan-name"><?= htmlspecialchars($app['loan_name']) ?></div>
            </div>
        </div>

        <div class="app-stats">

            <div class="app-stat">
                <div class="app-stat-label">💰 Amount</div>
                <div class="app-stat-value amount">৳<?= number_format($app['amount_needed']) ?></div>
            </div>

            <div class="app-stat">
                <div class="app-stat-label">📊 Interest</div>
                <div class="app-stat-value"><?= $app['interest_rate'] ?>%</div>
            </div>

            <div class="app-stat">
                <div class="app-stat-label">🌾 Land</div>
                <div class="app-stat-value"><?= $app['land_acres'] ?: '—' ?> acres</div>
            </div>

            <div class="app-stat">
                <div class="app-stat-label">🌱 Crop</div>
                <div class="app-stat-value"><?= htmlspecialchars($app['crop_type'] ?: '—') ?></div>
            </div>

        </div>

        <?php if (!empty($app['purpose'])): ?>
        <div style="padding: 0.85rem 1.2rem; border-top: 1px solid var(--border);">
            <div style="font-size:0.68rem;font-weight:600;color:var(--text4);text-transform:uppercase;letter-spacing:0.07em;margin-bottom:0.3rem;">📝 Purpose</div>
            <div style="font-size:0.85rem;color:#a0c080;line-height:1.5;"><?= htmlspecialchars($app['purpose']) ?></div>
        </div>
        <?php endif; ?>

    </div>

    <?php endwhile; ?>
    </div>
    <?php endif; ?>

</div>

<?php include 'layout_end.php'; ?>