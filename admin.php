<?php
/* ============================================================
   admin.php — SoilSync Admin Panel
   Full control: Users, Content, System Stats, Notifications
   ============================================================ */
require_once 'db.php';
requireLogin();

$pageTitle = 'Admin Panel';
$activeNav = 'admin';

/* ── Auth guard ── */
$me = currentUser($conn);
if ($me['role'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

/* ════════════════════════════════════════
   POST ACTIONS
════════════════════════════════════════ */
$successMsg = '';
$errorMsg   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    /* ── Change user role ── */
    if ($action === 'change_role') {
        $tid  = (int)$_POST['user_id'];
        $role = $conn->real_escape_string($_POST['role']);
        if (in_array($role, ['farmer','expert','admin']) && $tid !== (int)$me['id']) {
            $conn->query("UPDATE users SET role='$role' WHERE id=$tid");
            $successMsg = 'User role updated successfully.';
        } else {
            $errorMsg = 'Cannot change your own role.';
        }
    }

    /* ── Delete user ── */
    if ($action === 'delete_user') {
        $tid = (int)$_POST['user_id'];
        if ($tid !== (int)$me['id']) {
            $conn->query("DELETE FROM notifications WHERE user_id=$tid");
            $conn->query("DELETE FROM users WHERE id=$tid");
            $successMsg = 'User deleted.';
        } else {
            $errorMsg = 'You cannot delete yourself.';
        }
    }

    /* ── Delete advisory ── */
    if ($action === 'delete_advisory') {
        $aid = (int)$_POST['advisory_id'];
        $conn->query("DELETE FROM advisory_feed WHERE id=$aid");
        $successMsg = 'Advisory deleted.';
    }

    /* ── Broadcast notification ── */
    if ($action === 'broadcast') {
        $title = clean($conn, $_POST['notif_title'] ?? '');
        $msg   = clean($conn, $_POST['notif_msg']   ?? '');
        $type  = clean($conn, $_POST['notif_type']  ?? 'advisory');
        if ($title && $msg) {
            $users = $conn->query("SELECT id FROM users");
            $count = 0;
            while ($u = $users->fetch_assoc()) {
                $conn->query("INSERT INTO notifications (user_id,title,message,type,is_read)
                              VALUES ({$u['id']},'$title','$msg','$type',0)");
                $count++;
            }
            $successMsg = "Broadcast sent to $count users.";
        } else {
            $errorMsg = 'Title and message are required.';
        }
    }

    /* ── Reset unread notifications ── */
    if ($action === 'mark_all_read') {
        $conn->query("UPDATE notifications SET is_read=1");
        $successMsg = 'All notifications marked as read.';
    }
}

/* ════════════════════════════════════════
   STATS
════════════════════════════════════════ */
$totalUsers    = (int)$conn->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'];
$totalFarmers  = (int)$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='farmer'")->fetch_assoc()['c'];
$totalExperts  = (int)$conn->query("SELECT COUNT(*) AS c FROM users WHERE role='expert'")->fetch_assoc()['c'];
$totalFields   = (int)$conn->query("SELECT COUNT(*) AS c FROM fields")->fetch_assoc()['c'];
$totalCrops    = (int)$conn->query("SELECT COUNT(*) AS c FROM farmer_crops")->fetch_assoc()['c'];
$totalPests    = (int)$conn->query("SELECT COUNT(*) AS c FROM pest_reports")->fetch_assoc()['c'];
$totalAdvisory = (int)$conn->query("SELECT COUNT(*) AS c FROM advisory_feed")->fetch_assoc()['c'];
$totalQ        = (int)$conn->query("SELECT COUNT(*) AS c FROM questions")->fetch_assoc()['c'];
$totalAnswered = (int)$conn->query("SELECT COUNT(*) AS c FROM answers")->fetch_assoc()['c'];
$totalNotifs   = (int)$conn->query("SELECT COUNT(*) AS c FROM notifications WHERE is_read=0")->fetch_assoc()['c'];
$totalMarket   = (int)$conn->query("SELECT COUNT(*) AS c FROM market_prices")->fetch_assoc()['c'];
$totalSeeds    = (int)$conn->query("SELECT COUNT(*) AS c FROM seeds")->fetch_assoc()['c'];

/* ── New users this week ── */
$newUsersWeek = (int)$conn->query("
    SELECT COUNT(*) AS c FROM users
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc()['c'];

/* ── New pest reports this week ── */
$newPestWeek = (int)$conn->query("
    SELECT COUNT(*) AS c FROM pest_reports
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
")->fetch_assoc()['c'];

/* ════════════════════════════════════════
   DATA TABLES
════════════════════════════════════════ */

/* All users */
$users = $conn->query("
    SELECT u.*, l.division, l.district
    FROM users u
    LEFT JOIN locations l ON u.location_id = l.id
    ORDER BY u.created_at DESC
");

/* Advisories */
$advisories = $conn->query("
    SELECT af.*, l.district
    FROM advisory_feed af
    LEFT JOIN locations l ON af.location_id = l.id
    ORDER BY af.created_at DESC
    LIMIT 20
");

/* Recent pest reports */
$pestReports = $conn->query("
    SELECT pr.*, u.name AS farmer_name, c.name AS crop_name, p.name AS pest_name
    FROM pest_reports pr
    JOIN users u ON pr.user_id = u.id
    JOIN crops c ON pr.crop_id = c.id
    JOIN pests p ON pr.pest_id = p.id
    ORDER BY pr.created_at DESC
    LIMIT 15
");

/* Unanswered questions */
$unanswered = $conn->query("
    SELECT q.*, u.name AS farmer_name
    FROM questions q
    JOIN users u ON q.user_id = u.id
    LEFT JOIN answers a ON q.id = a.question_id
    WHERE a.id IS NULL
    ORDER BY q.created_at DESC
");

/* Recent notifications log */
$notifLog = $conn->query("
    SELECT n.*, u.name AS user_name
    FROM notifications n
    JOIN users u ON n.user_id = u.id
    ORDER BY n.created_at DESC
    LIMIT 10
");

/* Tab from URL */
$tab = $_GET['tab'] ?? 'overview';

include 'layout.php';
?>

<!-- ══════════════════════════════════════════════════
     HERO BANNER
══════════════════════════════════════════════════ -->
<div style="
    background:linear-gradient(135deg,#0d3b1e 0%,#1a3a5c 100%);
    border-radius:var(--radius);padding:24px 28px;margin-bottom:22px;
    display:flex;justify-content:space-between;align-items:center;
    flex-wrap:wrap;gap:14px;color:#fff;
    box-shadow:0 8px 32px rgba(0,0,0,0.2);position:relative;overflow:hidden;
">
    <div style="position:absolute;right:-10px;top:-20px;font-size:130px;opacity:0.05;pointer-events:none">⚙️</div>
    <div>
        <div style="font-size:10px;font-family:var(--font-mono);letter-spacing:2px;
                    color:rgba(255,255,255,0.55);text-transform:uppercase;margin-bottom:5px">
            SoilSync · System Administration
        </div>
        <h2 style="font-family:var(--font-display);font-size:1.7rem;font-weight:900;
                   color:#fff;margin-bottom:5px;line-height:1.2">
            ⚙️ Admin Control Panel
        </h2>
        <div style="font-size:13px;color:rgba(255,255,255,0.65)">
            Logged in as <strong><?= htmlspecialchars($me['name']) ?></strong> ·
            <?= date('l, d F Y') ?>
        </div>
    </div>
    <div style="display:flex;gap:10px;flex-wrap:wrap">
        <div style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);
                    border-radius:10px;padding:12px 18px;text-align:center;min-width:90px">
            <div style="font-family:var(--font-display);font-size:24px;font-weight:900;color:#fff"><?= $totalUsers ?></div>
            <div style="font-size:10px;color:rgba(255,255,255,0.6);font-family:var(--font-mono);text-transform:uppercase">Users</div>
        </div>
        <div style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);
                    border-radius:10px;padding:12px 18px;text-align:center;min-width:90px">
            <div style="font-family:var(--font-display);font-size:24px;font-weight:900;color:#4ade80"><?= $totalFarmers ?></div>
            <div style="font-size:10px;color:rgba(255,255,255,0.6);font-family:var(--font-mono);text-transform:uppercase">Farmers</div>
        </div>
        <div style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.2);
                    border-radius:10px;padding:12px 18px;text-align:center;min-width:90px">
            <div style="font-family:var(--font-display);font-size:24px;font-weight:900;color:#60a5fa"><?= $totalExperts ?></div>
            <div style="font-size:10px;color:rgba(255,255,255,0.6);font-family:var(--font-mono);text-transform:uppercase">Experts</div>
        </div>
    </div>
</div>

<?php if ($successMsg): ?>
<div class="alert alert-success"><?= htmlspecialchars($successMsg) ?></div>
<?php endif; ?>
<?php if ($errorMsg): ?>
<div class="alert alert-error"><?= htmlspecialchars($errorMsg) ?></div>
<?php endif; ?>

<!-- ══════════════════════════════════════════════════
     TABS NAV
══════════════════════════════════════════════════ -->
<div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:20px;
            border-bottom:2px solid var(--border);padding-bottom:0">
    <?php
    $tabs = [
        'overview'  => ['⚡','Overview'],
        'users'     => ['👥','Users ('.$totalUsers.')'],
        'content'   => ['📢','Content'],
        'pests'     => ['🐛','Pest Reports ('.$totalPests.')'],
        'questions' => ['❓','Questions ('.$totalQ.')'],
        'broadcast' => ['📣','Broadcast'],
        'system'    => ['🔧','System'],
    ];
    foreach ($tabs as $key => [$icon,$label]):
        $isActive = $tab === $key;
    ?>
    <a href="?tab=<?= $key ?>" style="
        display:inline-flex;align-items:center;gap:6px;
        padding:10px 16px;font-size:13px;font-weight:600;
        text-decoration:none;border-radius:10px 10px 0 0;
        margin-bottom:-2px;
        background:<?= $isActive ? 'var(--surface)' : 'transparent' ?>;
        color:<?= $isActive ? 'var(--accent)' : 'var(--text3)' ?>;
        border:<?= $isActive ? '2px solid var(--border)' : '2px solid transparent' ?>;
        border-bottom:<?= $isActive ? '2px solid var(--surface)' : '2px solid transparent' ?>;
        transition:color 0.15s,background 0.15s;
    "><?= $icon ?> <?= $label ?></a>
    <?php endforeach; ?>
</div>


<!-- ══════════════════════════════════════════════════
     TAB: OVERVIEW
══════════════════════════════════════════════════ -->
<?php if ($tab === 'overview'): ?>

<!-- Big stats grid -->
<div class="stats-grid" style="grid-template-columns:repeat(auto-fill,minmax(150px,1fr));margin-bottom:20px">
    <?php
    $statCards = [
        ['👥', $totalUsers,    'Total Users',       'var(--info)'],
        ['👨‍🌾', $totalFarmers, 'Farmers',           'var(--success)'],
        ['🧑‍🔬', $totalExperts, 'Experts',           'var(--info)'],
        ['🗺️', $totalFields,   'Total Fields',      'var(--accent)'],
        ['🌾', $totalCrops,    'Crop Records',      'var(--accent2)'],
        ['🐛', $totalPests,    'Pest Reports',      'var(--danger)'],
        ['📢', $totalAdvisory, 'Advisories',        'var(--warn)'],
        ['❓', $totalQ,        'Questions',         'var(--gold)'],
        ['✅', $totalAnswered, 'Answered',          'var(--success)'],
        ['🔔', $totalNotifs,   'Unread Notifs',     'var(--danger)'],
        ['💰', $totalMarket,   'Market Entries',    'var(--gold)'],
        ['🌱', $totalSeeds,    'Seed Varieties',    'var(--accent3)'],
    ];
    foreach ($statCards as [$icon,$val,$label,$color]): ?>
    <div class="stat-card" style="border-top:3px solid <?= $color ?>">
        <div style="font-size:24px;margin-bottom:6px"><?= $icon ?></div>
        <div class="stat-val" style="color:<?= $color ?>;font-size:24px"><?= $val ?></div>
        <div class="stat-label"><?= $label ?></div>
    </div>
    <?php endforeach; ?>
</div>

<!-- This week highlight -->
<div class="grid-2" style="margin-bottom:20px">
    <div class="card" style="border-left:4px solid var(--success)">
        <div class="card-title">📅 This Week</div>
        <div style="display:flex;gap:20px;flex-wrap:wrap;margin-top:10px">
            <div style="text-align:center">
                <div style="font-family:var(--font-display);font-size:36px;font-weight:900;color:var(--success)"><?= $newUsersWeek ?></div>
                <div style="font-size:12px;color:var(--text3)">New Users</div>
            </div>
            <div style="text-align:center">
                <div style="font-family:var(--font-display);font-size:36px;font-weight:900;color:var(--danger)"><?= $newPestWeek ?></div>
                <div style="font-size:12px;color:var(--text3)">Pest Reports</div>
            </div>
            <div style="text-align:center">
                <div style="font-family:var(--font-display);font-size:36px;font-weight:900;color:var(--warn)"><?= $unanswered->num_rows ?></div>
                <div style="font-size:12px;color:var(--text3)">Unanswered Q</div>
            </div>
        </div>
    </div>

    <div class="card" style="border-left:4px solid var(--info)">
        <div class="card-title">⚡ Quick Admin Actions</div>
        <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px">
            <a href="?tab=users"     class="btn btn-outline" style="justify-content:flex-start">👥 Manage Users</a>
            <a href="?tab=broadcast" class="btn btn-outline" style="justify-content:flex-start">📣 Send Broadcast Notification</a>
            <a href="?tab=content"   class="btn btn-outline" style="justify-content:flex-start">📢 Manage Advisories</a>
            <a href="?tab=questions" class="btn btn-outline" style="justify-content:flex-start">❓ View Unanswered Questions</a>
        </div>
    </div>
</div>

<!-- Recent notification log -->
<div class="card">
    <div class="card-title">🔔 Recent Notifications Log</div>
    <div class="table-wrap">
    <table>
        <thead><tr><th>User</th><th>Title</th><th>Message</th><th>Status</th><th>Time</th></tr></thead>
        <tbody>
        <?php $notifLog->data_seek(0); while ($n = $notifLog->fetch_assoc()): ?>
        <tr>
            <td><?= htmlspecialchars($n['user_name']) ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($n['title']) ?></td>
            <td style="font-size:12px;color:var(--text2)"><?= htmlspecialchars(substr($n['message'],0,60)) ?>...</td>
            <td><?php if ($n['is_read']): ?>
                <span class="badge badge-neutral">Read</span>
                <?php else: ?>
                <span class="badge badge-danger">Unread</span>
                <?php endif; ?></td>
            <td style="font-size:11px;color:var(--text3);font-family:var(--font-mono)">
                <?= date('d M, h:i A', strtotime($n['created_at'])) ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <form method="POST" style="margin-top:12px">
        <input type="hidden" name="action" value="mark_all_read">
        <button class="btn btn-outline btn-sm" type="submit">✅ Mark All Notifications Read</button>
    </form>
</div>

<?php endif; ?>


<!-- ══════════════════════════════════════════════════
     TAB: USERS
══════════════════════════════════════════════════ -->
<?php if ($tab === 'users'): ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;
                flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <div class="card-title" style="margin-bottom:0">👥 All Users</div>
        <input type="text" id="userSearch" placeholder="Search name or phone..."
               oninput="filterTable('userSearch','userTable')"
               style="padding:8px 14px;border:1.5px solid var(--border);
                      border-radius:8px;background:var(--surface2);
                      color:var(--text);font-family:var(--font-body);font-size:13px;
                      outline:none;width:220px">
    </div>

    <div class="table-wrap">
    <table id="userTable">
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Phone</th>
                <th>Role</th>
                <th>Location</th>
                <th>Joined</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php $users->data_seek(0); while ($u = $users->fetch_assoc()):
            $isMe = ($u['id'] == $me['id']);
            $roleColors = ['farmer'=>'badge-success','expert'=>'badge-info','admin'=>'badge-danger'];
            $rc = $roleColors[$u['role']] ?? 'badge-neutral';
        ?>
        <tr data-search="<?= strtolower($u['name'].' '.$u['phone']) ?>">
            <td style="font-family:var(--font-mono);font-size:11px;color:var(--text3)">#<?= $u['id'] ?></td>
            <td>
                <div style="font-weight:600;color:var(--text)"><?= htmlspecialchars($u['name']) ?></div>
                <?php if ($isMe): ?><span style="font-size:10px;color:var(--accent);font-family:var(--font-mono)">(You)</span><?php endif; ?>
            </td>
            <td style="font-family:var(--font-mono);font-size:12px"><?= htmlspecialchars($u['phone']) ?></td>
            <td><span class="badge <?= $rc ?>"><?= ucfirst($u['role']) ?></span></td>
            <td style="font-size:12px;color:var(--text2)">
                <?= htmlspecialchars($u['district'] ?? '—') ?>,
                <?= htmlspecialchars($u['division'] ?? '') ?>
            </td>
            <td style="font-size:11px;color:var(--text3);font-family:var(--font-mono)">
                <?= date('d M Y', strtotime($u['created_at'])) ?>
            </td>
            <td>
                <?php if (!$isMe): ?>
                <div style="display:flex;gap:6px;flex-wrap:wrap">
                    <!-- Change role form -->
                    <form method="POST" style="display:flex;gap:4px;align-items:center">
                        <input type="hidden" name="action" value="change_role">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <select name="role" style="
                            padding:4px 6px;border:1.5px solid var(--border);
                            border-radius:6px;background:var(--surface2);
                            color:var(--text);font-size:11px;font-family:var(--font-body)">
                            <option value="farmer"  <?= $u['role']==='farmer'  ? 'selected' : '' ?>>Farmer</option>
                            <option value="expert"  <?= $u['role']==='expert'  ? 'selected' : '' ?>>Expert</option>
                            <option value="admin"   <?= $u['role']==='admin'   ? 'selected' : '' ?>>Admin</option>
                        </select>
                        <button class="btn btn-sm" style="background:var(--info);color:#fff;border:none;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer">
                            Save
                        </button>
                    </form>

                    <!-- Delete form -->
                    <form method="POST" onsubmit="return confirm('Delete <?= htmlspecialchars(addslashes($u['name'])) ?>? This cannot be undone.')">
                        <input type="hidden" name="action" value="delete_user">
                        <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                        <button class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer">
                            🗑️
                        </button>
                    </form>
                </div>
                <?php else: ?>
                <span style="font-size:11px;color:var(--text3)">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php endif; ?>


<!-- ══════════════════════════════════════════════════
     TAB: CONTENT (Advisories)
══════════════════════════════════════════════════ -->
<?php if ($tab === 'content'): ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;
                flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <div class="card-title" style="margin-bottom:0">📢 Advisory Feed</div>
        <a href="advisory_manage.php" class="btn btn-primary btn-sm">+ Create Advisory</a>
    </div>

    <div class="table-wrap">
    <table>
        <thead>
            <tr><th>#</th><th>Title</th><th>Category</th><th>Location</th><th>Urgent</th><th>Date</th><th>Delete</th></tr>
        </thead>
        <tbody>
        <?php while ($adv = $advisories->fetch_assoc()):
            $catColors = ['weather'=>'badge-info','pest'=>'badge-danger','market'=>'badge-success','general'=>'badge-neutral'];
            $cc = $catColors[$adv['category']] ?? 'badge-neutral';
        ?>
        <tr>
            <td style="font-family:var(--font-mono);font-size:11px;color:var(--text3)">#<?= $adv['id'] ?></td>
            <td style="font-weight:600;max-width:220px"><?= htmlspecialchars($adv['title']) ?></td>
            <td><span class="badge <?= $cc ?>"><?= ucfirst($adv['category']) ?></span></td>
            <td style="font-size:12px;color:var(--text2)"><?= $adv['district'] ? htmlspecialchars($adv['district']) : '<span style="color:var(--text3)">All Bangladesh</span>' ?></td>
            <td><?= $adv['is_urgent'] ? '<span class="badge badge-danger">🔥 Yes</span>' : '<span style="color:var(--text3);font-size:12px">No</span>' ?></td>
            <td style="font-size:11px;color:var(--text3);font-family:var(--font-mono)"><?= date('d M Y', strtotime($adv['created_at'])) ?></td>
            <td>
                <form method="POST" onsubmit="return confirm('Delete this advisory?')">
                    <input type="hidden" name="action" value="delete_advisory">
                    <input type="hidden" name="advisory_id" value="<?= $adv['id'] ?>">
                    <button class="btn btn-sm" style="background:var(--danger);color:#fff;border:none;padding:4px 10px;border-radius:6px;font-size:11px;cursor:pointer">🗑️</button>
                </form>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
</div>

<?php endif; ?>


<!-- ══════════════════════════════════════════════════
     TAB: PEST REPORTS
══════════════════════════════════════════════════ -->
<?php if ($tab === 'pests'): ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;
                flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <div class="card-title" style="margin-bottom:0">🐛 Recent Pest Reports</div>
        <a href="pest_review.php" class="btn btn-outline btn-sm">Expert Review →</a>
    </div>

    <div class="table-wrap">
    <table>
        <thead>
            <tr><th>#</th><th>Farmer</th><th>Crop</th><th>Pest</th><th>Severity</th><th>District</th><th>Status</th><th>Date</th></tr>
        </thead>
        <tbody>
        <?php while ($pr = $pestReports->fetch_assoc()):
            $sevColor = match($pr['severity']) {
                'High'   => 'badge-danger',
                'Medium' => 'badge-warn',
                default  => 'badge-success',
            };
        ?>
        <tr>
            <td style="font-family:var(--font-mono);font-size:11px;color:var(--text3)">#<?= $pr['id'] ?></td>
            <td style="font-weight:600"><?= htmlspecialchars($pr['farmer_name']) ?></td>
            <td><?= htmlspecialchars($pr['crop_name']) ?></td>
            <td><?= htmlspecialchars($pr['pest_name']) ?></td>
            <td><span class="badge <?= $sevColor ?>"><?= $pr['severity'] ?></span></td>
            <td style="font-size:12px;color:var(--text2)"><?= htmlspecialchars($pr['district'] ?? '—') ?></td>
            <td><?php if ($pr['status'] === 'reviewed'): ?>
                <span class="badge badge-success">✅ Reviewed</span>
                <?php else: ?>
                <span class="badge badge-warn">⏳ Pending</span>
                <?php endif; ?></td>
            <td style="font-size:11px;color:var(--text3);font-family:var(--font-mono)">
                <?= date('d M Y', strtotime($pr['created_at'])) ?>
            </td>
        </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
    </div>
    <div style="margin-top:10px;font-size:12px;color:var(--text3)">
        Showing latest 15 reports. Admin cannot delete pest reports to preserve data integrity.
    </div>
</div>

<?php endif; ?>


<!-- ══════════════════════════════════════════════════
     TAB: QUESTIONS
══════════════════════════════════════════════════ -->
<?php if ($tab === 'questions'): ?>

<div class="card">
    <div style="display:flex;justify-content:space-between;align-items:center;
                flex-wrap:wrap;gap:10px;margin-bottom:16px">
        <div class="card-title" style="margin-bottom:0">❓ Unanswered Questions</div>
        <a href="faq_manage.php" class="btn btn-primary btn-sm">Answer as Expert →</a>
    </div>

    <?php if ($unanswered->num_rows === 0): ?>
    <div class="empty-state">
        <div class="empty-icon">🎉</div>
        <p>All questions have been answered!</p>
    </div>
    <?php else: ?>

    <?php $unanswered->data_seek(0); while ($q = $unanswered->fetch_assoc()): ?>
    <div style="padding:14px 0;border-bottom:1px solid var(--border)">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:8px">
            <div style="flex:1;min-width:200px">
                <div style="font-weight:700;font-size:14px;color:var(--text);margin-bottom:4px">
                    <?= htmlspecialchars($q['question']) ?>
                </div>
                <div style="font-size:12px;color:var(--text3)">
                    👨‍🌾 <?= htmlspecialchars($q['farmer_name']) ?> ·
                    📂 <?= htmlspecialchars($q['category'] ?? 'General') ?> ·
                    <?= date('d M Y', strtotime($q['created_at'])) ?>
                </div>
            </div>
            <a href="faq_manage.php?id=<?= $q['id'] ?>" class="btn btn-primary btn-sm">
                Answer →
            </a>
        </div>
    </div>
    <?php endwhile; ?>

    <?php endif; ?>
</div>

<?php endif; ?>


<!-- ══════════════════════════════════════════════════
     TAB: BROADCAST
══════════════════════════════════════════════════ -->
<?php if ($tab === 'broadcast'): ?>

<div class="grid-2">
    <!-- Broadcast Form -->
    <div class="card" style="border-left:4px solid var(--warn)">
        <div class="card-title">📣 Broadcast Notification to All Users</div>
        <div style="font-size:13px;color:var(--text2);margin-bottom:16px">
            This will send a notification to every user (farmers, experts, admins) in the system.
            Use responsibly.
        </div>

        <form method="POST">
            <input type="hidden" name="action" value="broadcast">

            <div style="margin-bottom:14px">
                <label style="font-size:12px;font-weight:600;color:var(--text2);
                              font-family:var(--font-mono);text-transform:uppercase;
                              letter-spacing:0.5px;display:block;margin-bottom:6px">
                    Notification Title *
                </label>
                <input type="text" name="notif_title" required maxlength="100"
                       placeholder="e.g. System Maintenance Notice"
                       style="width:100%;padding:10px 14px;border:1.5px solid var(--border);
                              border-radius:10px;background:var(--surface2);color:var(--text);
                              font-family:var(--font-body);font-size:13px;outline:none">
            </div>

            <div style="margin-bottom:14px">
                <label style="font-size:12px;font-weight:600;color:var(--text2);
                              font-family:var(--font-mono);text-transform:uppercase;
                              letter-spacing:0.5px;display:block;margin-bottom:6px">
                    Message *
                </label>
                <textarea name="notif_msg" required rows="4" maxlength="500"
                          placeholder="Write your message here..."
                          style="width:100%;padding:10px 14px;border:1.5px solid var(--border);
                                 border-radius:10px;background:var(--surface2);color:var(--text);
                                 font-family:var(--font-body);font-size:13px;outline:none;
                                 resize:vertical"></textarea>
            </div>

            <div style="margin-bottom:18px">
                <label style="font-size:12px;font-weight:600;color:var(--text2);
                              font-family:var(--font-mono);text-transform:uppercase;
                              letter-spacing:0.5px;display:block;margin-bottom:6px">
                    Type
                </label>
                <select name="notif_type" style="
                    width:100%;padding:10px 14px;border:1.5px solid var(--border);
                    border-radius:10px;background:var(--surface2);color:var(--text);
                    font-family:var(--font-body);font-size:13px;outline:none">
                    <option value="advisory">📢 Advisory</option>
                    <option value="alert">🔥 Alert</option>
                    <option value="info">ℹ️ Info</option>
                </select>
            </div>

            <button type="submit" class="btn btn-primary"
                    onclick="return confirm('Send this notification to ALL <?= $totalUsers ?> users?')"
                    style="width:100%;justify-content:center">
                📣 Send to All <?= $totalUsers ?> Users
            </button>
        </form>
    </div>

    <!-- Info panel -->
    <div>
        <div class="card" style="margin-bottom:14px;border-left:4px solid var(--info)">
            <div class="card-title">📊 User Distribution</div>
            <div style="display:flex;flex-direction:column;gap:10px;margin-top:10px">
                <?php
                $breakdown = [
                    ['👨‍🌾','Farmers', $totalFarmers, 'var(--success)'],
                    ['🧑‍🔬','Experts',  $totalExperts, 'var(--info)'],
                    ['⚙️','Admins',   $totalUsers - $totalFarmers - $totalExperts, 'var(--danger)'],
                ];
                foreach ($breakdown as [$ic,$label,$count,$color]):
                ?>
                <div style="display:flex;align-items:center;gap:12px">
                    <span style="font-size:18px;width:28px"><?= $ic ?></span>
                    <div style="flex:1">
                        <div style="display:flex;justify-content:space-between;font-size:13px;
                                    font-weight:600;margin-bottom:4px">
                            <span><?= $label ?></span>
                            <span style="font-family:var(--font-mono);color:<?= $color ?>"><?= $count ?></span>
                        </div>
                        <div style="height:6px;background:var(--border);border-radius:10px;overflow:hidden">
                            <div style="height:100%;background:<?= $color ?>;border-radius:10px;
                                        width:<?= $totalUsers > 0 ? round(($count/$totalUsers)*100) : 0 ?>%"></div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="border-left:4px solid var(--gold)">
            <div class="card-title">⚠️ Broadcast Guidelines</div>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px;font-size:13px;color:var(--text2)">
                <div>✅ Use for system-wide alerts only</div>
                <div>✅ Weather or disease emergencies</div>
                <div>✅ Platform maintenance notices</div>
                <div>❌ Do not spam marketing messages</div>
                <div>❌ Do not use for individual advice</div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>


<!-- ══════════════════════════════════════════════════
     TAB: SYSTEM
══════════════════════════════════════════════════ -->
<?php if ($tab === 'system'): ?>

<div class="grid-2">

    <!-- Database Summary -->
    <div class="card">
        <div class="card-title">🗄️ Database Tables Summary</div>
        <div class="table-wrap">
        <table>
            <thead><tr><th>Table</th><th>Records</th></tr></thead>
            <tbody>
            <?php
            $tables = [
                'users','fields','farmer_crops','crops','seeds',
                'pest_reports','pest_reviews','advisory_feed',
                'questions','answers','notifications',
                'market_prices','weather_data','locations',
                'irrigation_logs','activity_schedule',
            ];
            foreach ($tables as $t):
                $cnt = (int)$conn->query("SELECT COUNT(*) AS c FROM `$t`")->fetch_assoc()['c'];
            ?>
            <tr>
                <td style="font-family:var(--font-mono);font-size:12px">
                    <code><?= $t ?></code>
                </td>
                <td>
                    <span style="font-family:var(--font-mono);font-weight:700;
                                 color:<?= $cnt > 0 ? 'var(--accent)' : 'var(--text3)' ?>">
                        <?= number_format($cnt) ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
    </div>

    <!-- System Info -->
    <div>
        <div class="card" style="margin-bottom:14px">
            <div class="card-title">🖥️ Server Info</div>
            <div style="display:flex;flex-direction:column;gap:8px;margin-top:10px">
                <?php
                $infos = [
                    ['PHP Version',     phpversion()],
                    ['MySQL Version',   $conn->server_info],
                    ['Server Time',     date('d M Y, H:i:s')],
                    ['DB Name',         DB_NAME],
                    ['Session ID',      substr(session_id(), 0, 12).'...'],
                    ['Memory Usage',    round(memory_get_usage()/1024/1024, 2).' MB'],
                ];
                foreach ($infos as [$label,$val]): ?>
                <div style="display:flex;justify-content:space-between;padding:7px 0;
                            border-bottom:1px solid var(--border);font-size:12.5px">
                    <span style="color:var(--text2)"><?= $label ?></span>
                    <span style="font-family:var(--font-mono);color:var(--text);font-weight:600">
                        <?= htmlspecialchars($val) ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card" style="border-left:4px solid var(--success)">
            <div class="card-title">🌿 SoilSync Info</div>
            <div style="font-size:13px;color:var(--text2);line-height:1.6;margin-top:8px">
                <strong>SoilSync</strong> — Smart Farming Platform for Bangladesh<br>
                Built for the Bangladesh Farmers Card initiative (April 2026).<br><br>
                <div style="display:flex;flex-wrap:wrap;gap:6px">
                    <span class="badge badge-success">v2.0</span>
                    <span class="badge badge-info">PHP 8.2</span>
                    <span class="badge badge-neutral">MariaDB 10.4</span>
                    <span class="badge badge-warn">XAMPP</span>
                </div>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>


<!-- JS for table search -->
<script>
function filterTable(inputId, tableId) {
    const q = document.getElementById(inputId).value.toLowerCase().trim();
    document.querySelectorAll('#' + tableId + ' tbody tr').forEach(row => {
        const text = (row.dataset.search || row.innerText).toLowerCase();
        row.style.display = !q || text.includes(q) ? '' : 'none';
    });
}
</script>

<?php include 'layout_end.php'; ?>
