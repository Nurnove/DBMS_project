<?php
require_once 'db.php';
requireLogin();
$pageTitle = 'My Fields';
$activeNav = 'fields';
$user = currentUser($conn);
$uid = (int)$user['id'];

$success = $error = '';

// DELETE
if (isset($_GET['delete'])) {
    $fid = (int)$_GET['delete'];
    $conn->query("DELETE FROM fields WHERE id=$fid AND user_id=$uid");
    $success = 'Field deleted.';
}

// ADD/EDIT
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name      = clean($conn, $_POST['name'] ?? '');
    $area      = (float)($_POST['area'] ?? 0);
    $soil_type = clean($conn, $_POST['soil_type'] ?? '');
    $loc_id    = (int)($_POST['location_id'] ?? $user['location_id'] ?? 1);
    $fid       = (int)($_POST['field_id'] ?? 0);

    if (!$name || !$soil_type) {
        $error = 'Name and soil type are required.';
    } else {
        if ($fid > 0) {
            $conn->query("UPDATE fields SET name='$name', area=$area, soil_type='$soil_type', location_id=$loc_id WHERE id=$fid AND user_id=$uid");
            $success = 'Field updated!';
        } else {
            $conn->query("INSERT INTO fields (user_id, name, area, soil_type, location_id) VALUES ($uid, '$name', $area, '$soil_type', $loc_id)");
            $success = 'Field added!';
        }
    }
}

$showForm = isset($_GET['action']) && $_GET['action'] === 'add';
$editField = null;
if (isset($_GET['edit'])) {
    $eid = (int)$_GET['edit'];
    $er = $conn->query("SELECT * FROM fields WHERE id=$eid AND user_id=$uid");
    $editField = $er ? $er->fetch_assoc() : null;
    if ($editField) $showForm = true;
}

$fields = $conn->query("SELECT f.*, l.division, l.district FROM fields f LEFT JOIN locations l ON f.location_id=l.id WHERE f.user_id=$uid ORDER BY f.id DESC");
$locs   = $conn->query("SELECT * FROM locations ORDER BY division, district");
$soils  = ['Sandy','Clay','Loamy','Silt','Peaty','Chalky'];

include 'layout.php';
?>

<?php if ($success): ?><div class="alert alert-success">✅ <?= $success ?></div><?php endif; ?>
<?php if ($error): ?><div class="alert alert-error">⚠️ <?= $error ?></div><?php endif; ?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px">
  <div></div>
  <a href="fields.php?action=add" class="btn btn-primary">+ Add Field</a>
</div>

<?php if ($showForm): ?>
<div class="card" style="margin-bottom:24px">
  <div class="card-title"><?= $editField ? '✏️ Edit Field' : '➕ Add New Field' ?></div>
  <form method="post">
    <input type="hidden" name="field_id" value="<?= $editField['id'] ?? 0 ?>">
    <div class="grid-2">
      <div class="form-group">
        <label>Field Name *</label>
        <input type="text" name="name" placeholder="e.g. North Plot" value="<?= htmlspecialchars($editField['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label>Area (in Decimal/Bigha)</label>
        <input type="number" name="area" step="0.01" placeholder="e.g. 2.5" value="<?= $editField['area'] ?? '' ?>">
      </div>
      <div class="form-group">
        <label>Soil Type *</label>
        <select name="soil_type" required>
          <option value="">— Select —</option>
          <?php foreach ($soils as $s): ?>
            <option value="<?= $s ?>" <?= ($editField['soil_type'] ?? '') === $s ? 'selected' : '' ?>><?= $s ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label>District</label>
        <select name="location_id">
          <?php $locs->data_seek(0); while ($loc = $locs->fetch_assoc()): ?>
            <option value="<?= $loc['id'] ?>" <?= ($editField['location_id'] ?? $user['location_id']) == $loc['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($loc['division'] . ' — ' . $loc['district']) ?>
            </option>
          <?php endwhile; ?>
        </select>
      </div>
    </div>
    <div style="display:flex;gap:10px">
      <button type="submit" class="btn btn-primary"><?= $editField ? 'Update Field' : 'Add Field' ?></button>
      <a href="fields.php" class="btn btn-outline">Cancel</a>
    </div>
  </form>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-title">🗺️ All Fields</div>
  <?php if ($fields->num_rows === 0): ?>
    <div class="empty-state">
      <div class="empty-icon">🗺️</div>
      <p>No fields added yet. Add your first field to get started.</p>
      <a href="fields.php?action=add" class="btn btn-primary">+ Add Field</a>
    </div>
  <?php else: ?>
    <div class="table-wrap">
    <table>
      <thead><tr><th>#</th><th>Name</th><th>Area</th><th>Soil</th><th>Location</th><th>Actions</th></tr></thead>
      <tbody>
      <?php $i=1; while ($f = $fields->fetch_assoc()): ?>
        <tr>
          <td><?= $i++ ?></td>
          <td><strong><?= htmlspecialchars($f['name']) ?></strong></td>
          <td><?= $f['area'] ? $f['area'] . ' dec' : '—' ?></td>
          <td><span class="badge badge-gray"><?= $f['soil_type'] ?></span></td>
          <td><?= htmlspecialchars(($f['district'] ?? '') . ($f['division'] ? ', '.$f['division'] : '')) ?></td>
          <td>
            <a href="fields.php?edit=<?= $f['id'] ?>" class="btn btn-outline btn-sm">✏️</a>
            <a href="fields.php?delete=<?= $f['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Delete this field?')">🗑️</a>
          </td>
        </tr>
      <?php endwhile; ?>
      </tbody>
    </table>
    </div>
  <?php endif; ?>
</div>

<?php include 'layout_end.php'; ?>