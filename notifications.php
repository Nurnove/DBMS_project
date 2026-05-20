<?php
require_once 'db.php';
requireLogin();
$pageTitle = 'Notifications';
$activeNav = 'notifications';

$user = currentUser($conn);
$uid  = (int)$user['id'];

// Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Mark single as read
    if ($action === 'mark_read' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("UPDATE notifications SET is_read = 1 WHERE id = $id AND user_id = $uid");
        header('Location: notifications.php');
        exit;
    }

    // Mark all as read
    if ($action === 'mark_all_read') {
        $conn->query("UPDATE notifications SET is_read = 1 WHERE user_id = $uid");
        header('Location: notifications.php');
        exit;
    }

    // Delete single
    if ($action === 'delete' && !empty($_POST['id'])) {
        $id = (int)$_POST['id'];
        $conn->query("DELETE FROM notifications WHERE id = $id AND user_id = $uid");
        header('Location: notifications.php');
        exit;
    }

    // Delete all read
    if ($action === 'delete_read') {
        $conn->query("DELETE FROM notifications WHERE user_id = $uid AND is_read = 1");
        header('Location: notifications.php');
        exit;
    }
}

// Filter
$filterType = $_GET['type'] ?? '';
$filterRead = $_GET['read'] ?? '';  // 'unread' | 'read' | ''

$where  = ["user_id = $uid"];
if ($filterType) {
    $ft = $conn->real_escape_string($filterType);
    $where[] = "type = '$ft'";
}
if ($filterRead === 'unread') $where[] = "is_read = 0";
if ($filterRead === 'read')   $where[] = "is_read = 1";

$whereSQL = implode(' AND ', $where);

$notifications = $conn->query("SELECT * FROM notifications WHERE $whereSQL ORDER BY created_at DESC");

// Counts
$totalCount  = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid")->fetch_assoc()['c'];
$unreadCount = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=0")->fetch_assoc()['c'];
$readCount   = $conn->query("SELECT COUNT(*) AS c FROM notifications WHERE user_id=$uid AND is_read=1")->fetch_assoc()['c'];

// Count by type
$typeCounts = [];
$tcRes = $conn->query("SELECT type, COUNT(*) AS c FROM notifications WHERE user_id=$uid GROUP BY type");
while ($tc = $tcRes->fetch_assoc()) $typeCounts[$tc['type']] = $tc['c'];

include 'layout.php';

$typeConfig = [
    'pest'     => ['icon' => '🐛', 'label' => 'Pest',     'badge' => 'badge-danger',  'bg' => 'var(--danger-light)',  'color' => 'var(--danger)'],
    'weather'  => ['icon' => '🌦️', 'label' => 'Weather',  'badge' => 'badge-info',    'bg' => 'var(--info-light)',    'color' => 'var(--info)'],
    'advisory' => ['icon' => '📢', 'label' => 'Advisory', 'badge' => 'badge-gold',    'bg' => 'var(--gold-light)',    'color' => 'var(--gold)'],
    'system'   => ['icon' => '⚙️', 'label' => 'System',   'badge' => 'badge-neutral', 'bg' => 'var(--surface2)',      'color' => 'var(--text3)'],
];
?>

<!-- Page header -->
<div class="flex-between mb-24">
  <div>
    <h1 style="font-family:var(--font-display);font-size:1.9rem;font-weight:900;color:var(--text);line-height:1.1">
      🔔 Notifications
    </h1>
    <p style="color:var(--text3);font-size:13px;margin-top:4px">
      <?= $unreadCount ?> unread · <?= $totalCount ?> total
    </p>
  </div>
  <div class="flex gap-8">
    <?php if ($unreadCount > 0): ?>
    <form method="post" style="margin:0">
      <input type="hidden" name="action" value="mark_all_read">
      <button type="submit" class="btn btn-outline btn-sm">✓ Mark all read</button>
    </form>
    <?php endif; ?>
    <?php if ($readCount > 0): ?>
    <form method="post" style="margin:0" onsubmit="return confirm('Delete all read notifications?')">
      <input type="hidden" name="action" value="delete_read">
      <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--danger)">🗑 Clear read</button>
    </form>
    <?php endif; ?>
  </div>
</div>

<!-- Stats row -->
<div class="stats-grid reveal-stagger" style="grid-template-columns:repeat(4,1fr)">
  <div class="stat-card reveal-item">
    <div class="stat-icon-wrap">🔔</div>
    <div class="stat-val"><?= $totalCount ?></div>
    <div class="stat-label">Total</div>
  </div>
  <div class="stat-card reveal-item" style="<?= $unreadCount > 0 ? 'border-color:var(--danger);' : '' ?>">
    <div class="stat-icon-wrap" style="<?= $unreadCount > 0 ? 'background:var(--danger-light)' : '' ?>">🔴</div>
    <div class="stat-val" style="<?= $unreadCount > 0 ? 'color:var(--danger)' : '' ?>"><?= $unreadCount ?></div>
    <div class="stat-label">Unread</div>
  </div>
  <div class="stat-card reveal-item">
    <div class="stat-icon-wrap">🐛</div>
    <div class="stat-val"><?= $typeCounts['pest'] ?? 0 ?></div>
    <div class="stat-label">Pest Alerts</div>
  </div>
  <div class="stat-card reveal-item">
    <div class="stat-icon-wrap">🌦️</div>
    <div class="stat-val"><?= $typeCounts['weather'] ?? 0 ?></div>
    <div class="stat-label">Weather</div>
  </div>
</div>

<!-- Filters -->
<div class="card mb-20 reveal">
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
    <!-- Read status filter -->
    <div style="display:flex;gap:6px">
      <?php
      $readFilters = ['' => 'All', 'unread' => 'Unread', 'read' => 'Read'];
      foreach ($readFilters as $val => $lbl):
        $active = $filterRead === $val;
      ?>
      <a href="?type=<?= urlencode($filterType) ?>&read=<?= $val ?>"
         class="btn btn-sm <?= $active ? 'btn-primary' : 'btn-ghost' ?>">
        <?= $lbl ?>
        <?php if ($val === 'unread' && $unreadCount): ?><span style="background:rgba(255,255,255,0.3);border-radius:10px;padding:1px 6px;font-size:10px;margin-left:4px"><?= $unreadCount ?></span><?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>

    <div style="width:1px;height:24px;background:var(--border)"></div>

    <!-- Type filter -->
    <div style="display:flex;gap:6px;flex-wrap:wrap">
      <a href="?type=&read=<?= urlencode($filterRead) ?>" class="btn btn-sm <?= !$filterType ? 'btn-primary' : 'btn-ghost' ?>">All Types</a>
      <?php foreach ($typeConfig as $type => $cfg): ?>
      <a href="?type=<?= $type ?>&read=<?= urlencode($filterRead) ?>"
         class="btn btn-sm <?= $filterType === $type ? 'btn-primary' : 'btn-ghost' ?>">
        <?= $cfg['icon'] ?> <?= $cfg['label'] ?>
        <?php if (!empty($typeCounts[$type])): ?>
        <span style="background:rgba(255,255,255,0.25);border-radius:10px;padding:1px 5px;font-size:9px;margin-left:2px"><?= $typeCounts[$type] ?></span>
        <?php endif; ?>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</div>

<!-- Notifications list -->
<div class="card reveal">
  <div class="card-header">
    <div class="card-title">📋 Notification Center</div>
    <span class="badge badge-neutral"><?= $notifications->num_rows ?> shown</span>
  </div>

  <?php if ($notifications->num_rows === 0): ?>
  <div class="empty-state">
    <div class="empty-icon"><?= $filterType ? ($typeConfig[$filterType]['icon'] ?? '🔔') : '🎉' ?></div>
    <h3><?= $unreadCount === 0 && !$filterType && !$filterRead ? "You're all caught up!" : "No notifications found" ?></h3>
    <p><?= $filterType || $filterRead ? 'Try a different filter above' : 'New alerts about pests, weather and advisories will appear here.' ?></p>
    <?php if ($filterType || $filterRead): ?>
    <a href="notifications.php" class="btn btn-outline btn-sm">Clear Filters</a>
    <?php endif; ?>
  </div>
  <?php else: ?>

  <div style="display:flex;flex-direction:column;gap:10px">
  <?php while ($n = $notifications->fetch_assoc()):
    $cfg      = $typeConfig[$n['type']] ?? $typeConfig['system'];
    $isUnread = !$n['is_read'];
    $timeAgo  = timeAgo($n['created_at']);
  ?>
    <div style="
      display:flex;align-items:flex-start;gap:14px;
      padding:16px 18px;
      border-radius:var(--radius-sm);
      border:1.5px solid <?= $isUnread ? 'var(--accent)' : 'var(--border)' ?>;
      background:<?= $isUnread ? 'var(--accent-light)' : 'var(--surface2)' ?>;
      transition:all var(--transition);
      position:relative;
    "
    onmouseover="this.style.boxShadow='var(--shadow-sm)'"
    onmouseout="this.style.boxShadow='none'">

      <!-- Type icon -->
      <div style="
        width:42px;height:42px;flex-shrink:0;
        background:<?= $cfg['bg'] ?>;
        border-radius:var(--radius-sm);
        display:flex;align-items:center;justify-content:center;
        font-size:18px;
      "><?= $cfg['icon'] ?></div>

      <!-- Content -->
      <div style="flex:1;min-width:0">
        <div style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;margin-bottom:3px">
          <span class="badge <?= $cfg['badge'] ?>"><?= $cfg['label'] ?></span>
          <?php if ($isUnread): ?>
          <span class="badge badge-success" style="font-size:9px;padding:2px 6px">NEW</span>
          <?php endif; ?>
          <span style="font-size:11px;color:var(--text4);font-family:var(--font-mono);margin-left:auto"><?= $timeAgo ?></span>
        </div>
        <div style="font-family:var(--font-display);font-size:14px;font-weight:700;color:var(--text);margin-bottom:3px">
          <?= htmlspecialchars($n['title']) ?>
        </div>
        <?php if ($n['message']): ?>
        <div style="font-size:13px;color:var(--text2);line-height:1.55"><?= htmlspecialchars($n['message']) ?></div>
        <?php endif; ?>
        <div style="font-size:11px;color:var(--text4);margin-top:6px;font-family:var(--font-mono)">
          📅 <?= date('d M Y, g:i a', strtotime($n['created_at'])) ?>
        </div>
      </div>

      <!-- Actions -->
      <div style="display:flex;flex-direction:column;gap:5px;flex-shrink:0">
        <?php if ($isUnread): ?>
        <form method="post" style="margin:0">
          <input type="hidden" name="action" value="mark_read">
          <input type="hidden" name="id" value="<?= $n['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm" title="Mark as read" style="padding:6px 10px">✓</button>
        </form>
        <?php endif; ?>
        <form method="post" style="margin:0" onsubmit="return confirm('Delete this notification?')">
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $n['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm" title="Delete" style="padding:6px 10px;color:var(--danger)">🗑</button>
        </form>
      </div>

      <!-- Unread left border indicator -->
      <?php if ($isUnread): ?>
      <div style="position:absolute;left:0;top:0;bottom:0;width:3px;background:var(--accent);border-radius:var(--radius-sm) 0 0 var(--radius-sm)"></div>
      <?php endif; ?>
    </div>
  <?php endwhile; ?>
  </div>

  <?php endif; ?>
</div>

<?php
// Helper: human-readable time ago
function timeAgo($datetime) {
    $diff = time() - strtotime($datetime);
    if ($diff < 60)      return 'Just now';
    if ($diff < 3600)    return floor($diff/60) . 'm ago';
    if ($diff < 86400)   return floor($diff/3600) . 'h ago';
    if ($diff < 604800)  return floor($diff/86400) . 'd ago';
    return date('d M', strtotime($datetime));
}
?>

<?php include 'layout_end.php'; ?>
