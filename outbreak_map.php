<?php
require_once 'db.php';
requireLogin();

$pageTitle = 'Smart Outbreak Heatmap';
$activeNav = 'outbreak';


function distanceKM($lat1, $lon1, $lat2, $lon2)
{
    $earthRadius = 6371;

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a =
        sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) *
        cos(deg2rad($lat2)) *
        sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c;
}

$crop_id = (int)($_GET['crop_id'] ?? 0);
$pest_id = (int)($_GET['pest_id'] ?? 0);
$days    = (int)($_GET['days'] ?? 30);

$dateFilter = date('Y-m-d', strtotime("-$days days"));


$crops = $conn->query("SELECT * FROM crops ORDER BY name");
$pests = $conn->query("SELECT * FROM pests ORDER BY name");


$sql = "
SELECT
    pr.*,
    c.name AS crop_name,
    p.name AS pest_name

FROM pest_reports pr
JOIN crops c ON pr.crop_id = c.id
JOIN pests p ON pr.pest_id = p.id

WHERE
    pr.created_at >= '$dateFilter'
    AND pr.latitude IS NOT NULL
    AND pr.longitude IS NOT NULL
";

if ($crop_id > 0) {
    $sql .= " AND pr.crop_id = $crop_id ";
}

if ($pest_id > 0) {
    $sql .= " AND pr.pest_id = $pest_id ";
}

$sql .= " ORDER BY pr.created_at DESC ";

$reports = $conn->query($sql);



$clusters = [];

while ($r = $reports->fetch_assoc()) {

    $groupKey =
        strtolower(trim($r['district'])) . '_' .
        $r['crop_id'] . '_' .
        $r['pest_id'];

    if (!isset($clusters[$groupKey])) {
        $clusters[$groupKey] = [];
    }

    $added = false;

    foreach ($clusters[$groupKey] as &$cluster) {

        $dist = distanceKM(
            $cluster['lat'],
            $cluster['lng'],
            $r['latitude'],
            $r['longitude']
        );

        if ($dist <= 25) {

            $cluster['reports'][] = $r;
            $cluster['count']++;

            if ($r['severity'] == 'High') $cluster['high']++;
            if ($r['severity'] == 'Medium') $cluster['medium']++;
            if ($r['severity'] == 'Low') $cluster['low']++;

            /* update center */
            $cluster['lat'] =
                ($cluster['lat'] + $r['latitude']) / 2;

            $cluster['lng'] =
                ($cluster['lng'] + $r['longitude']) / 2;

            $added = true;
            break;
        }
    }

    if (!$added) {

        $clusters[$groupKey][] = [

            'district' => $r['district'] ?? 'Unknown',

            'crop' => $r['crop_name'],
            'pest' => $r['pest_name'],

            'crop_id' => $r['crop_id'],
            'pest_id' => $r['pest_id'],

            'lat' => $r['latitude'],
            'lng' => $r['longitude'],

            'count' => 1,

            'high' =>
                ($r['severity'] == 'High') ? 1 : 0,

            'medium' =>
                ($r['severity'] == 'Medium') ? 1 : 0,

            'low' =>
                ($r['severity'] == 'Low') ? 1 : 0,

            'reports' => [$r]
        ];
    }
}



$finalClusters = [];

foreach ($clusters as $group) {
    foreach ($group as $c) {
        $finalClusters[] = $c;
    }
}



$districtOutbreaks = [];

foreach ($finalClusters as $c) {

    if ($c['count'] < 10) continue;

    $key =
        strtolower(trim($c['district'])) . '_' .
        $c['crop_id'] . '_' .
        $c['pest_id'];

    if (!isset($districtOutbreaks[$key])) {

        $districtOutbreaks[$key] = [

            'district' => $c['district'],
            'crop' => $c['crop'],
            'pest' => $c['pest'],

            'clusters' => 0,

            'lat' => $c['lat'],
            'lng' => $c['lng']
        ];
    }

    $districtOutbreaks[$key]['clusters']++;
}

foreach ($districtOutbreaks as $k => $d) {

    if ($d['clusters'] < 3) {
        unset($districtOutbreaks[$k]);
    }
}

include 'layout.php';
?>

<link rel="stylesheet"
href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"/>

<div class="card">

<div class="card-title">
🌍 Smart Pest Outbreak Intelligence
</div>

<form method="GET"
style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:20px;">

    <select name="crop_id">

        <option value="0">All Crops</option>

        <?php while($c = $crops->fetch_assoc()): ?>

            <option value="<?= $c['id'] ?>"
                <?= $crop_id == $c['id'] ? 'selected' : '' ?>>

                <?= htmlspecialchars($c['name']) ?>

            </option>

        <?php endwhile; ?>

    </select>

    <select name="pest_id">

        <option value="0">All Pests</option>

        <?php while($p = $pests->fetch_assoc()): ?>

            <option value="<?= $p['id'] ?>"
                <?= $pest_id == $p['id'] ? 'selected' : '' ?>>

                <?= htmlspecialchars($p['name']) ?>

            </option>

        <?php endwhile; ?>

    </select>

    <select name="days">

        <option value="7" <?= $days==7?'selected':'' ?>>
            7 Days
        </option>

        <option value="15" <?= $days==15?'selected':'' ?>>
            15 Days
        </option>

        <option value="30" <?= $days==30?'selected':'' ?>>
            30 Days
        </option>

        <option value="60" <?= $days==60?'selected':'' ?>>
            60 Days
        </option>

    </select>

    <button class="btn btn-primary">
        Apply
    </button>

</form>

<div id="map"
style="height:750px;border-radius:14px;">
</div>

</div>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

<script>

var map = L.map('map').setView([23.6850, 90.3563], 7);

L.tileLayer(
'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
{
    attribution: '&copy; OpenStreetMap'
}).addTo(map);



const clusters = [

<?php foreach($finalClusters as $c): ?>

{
    district: "<?= addslashes($c['district']) ?>",

    crop: "<?= addslashes($c['crop']) ?>",
    pest: "<?= addslashes($c['pest']) ?>",

    crop_id: <?= $c['crop_id'] ?>,
    pest_id: <?= $c['pest_id'] ?>,

    lat: <?= $c['lat'] ?>,
    lng: <?= $c['lng'] ?>,

    total: <?= $c['count'] ?>,

    high: <?= $c['high'] ?>,
    medium: <?= $c['medium'] ?>,
    low: <?= $c['low'] ?>
},

<?php endforeach; ?>

];



const districtOutbreaks = [

<?php foreach($districtOutbreaks as $d): ?>

{
    district: "<?= addslashes($d['district']) ?>",

    crop: "<?= addslashes($d['crop']) ?>",
    pest: "<?= addslashes($d['pest']) ?>",

    clusters: <?= $d['clusters'] ?>,

    lat: <?= $d['lat'] ?>,
    lng: <?= $d['lng'] ?>
},

<?php endforeach; ?>

];



clusters.forEach(item => {

    let color = 'green';

    if (item.total >= 15) {
        color = 'red';
    }
    else if (item.total >= 10) {
        color = 'orange';
    }

    L.circleMarker([item.lat, item.lng], {

        radius: 8 + item.total,

        color: '#fff',
        weight: 2,

        fillColor: color,
        fillOpacity: 0.8

    })
    .addTo(map)

    .bindPopup(`

        <div style="min-width:260px">

            <h3>
                📍 ${item.district}
            </h3>

            <b>Crop:</b>
            ${item.crop}<br>

            <b>Pest:</b>
            ${item.pest}<br><br>

            Total Reports:
            <b>${item.total}</b><br>

            🔴 High:
            ${item.high}<br>

            🟠 Medium:
            ${item.medium}<br>

            🟢 Low:
            ${item.low}<br><br>

            ${
                item.total >= 15
                ? '🔥 HIGH CLUSTER'
                : item.total >= 10
                ? '⚠ MEDIUM CLUSTER'
                : '🌿 LOW CLUSTER'
            }

        </div>

    `);
});



districtOutbreaks.forEach(item => {

    L.circle([item.lat, item.lng], {

        color: 'red',
        fillColor: 'red',

        fillOpacity: 0.12,

        radius: 35000

    })
    .addTo(map)

    .bindPopup(`

        <div style="min-width:260px">

            <h2 style="color:red">
                🚨 DISTRICT OUTBREAK
            </h2>

            <hr>

            <b>District:</b>
            ${item.district}<br>

            <b>Crop:</b>
            ${item.crop}<br>

            <b>Pest:</b>
            ${item.pest}<br><br>

            <b>
                ${item.clusters}
                strong clusters detected
            </b><br><br>

            🔥 District-wide spread detected

        </div>

    `);
});



function getDistance(lat1, lon1, lat2, lon2)
{
    const R = 6371;

    const dLat =
        (lat2 - lat1) * Math.PI / 180;

    const dLon =
        (lon2 - lon1) * Math.PI / 180;

    const a =
        Math.sin(dLat/2) *
        Math.sin(dLat/2) +

        Math.cos(lat1 * Math.PI / 180) *
        Math.cos(lat2 * Math.PI / 180) *

        Math.sin(dLon/2) *
        Math.sin(dLon/2);

    const c =
        2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));

    return R * c;
}

navigator.geolocation.getCurrentPosition(function(pos){

    let userLat = pos.coords.latitude;
    let userLng = pos.coords.longitude;

    /* user marker */
    L.marker([userLat, userLng])
    .addTo(map)
    .bindPopup("📍 Your Location");

    map.setView([userLat, userLng], 9);

    clusters.forEach(item => {

        let dist = getDistance(
            userLat,
            userLng,
            item.lat,
            item.lng
        );

        if (dist <= 25) {

            L.circle([item.lat, item.lng], {

                color: 'red',
                fillColor: 'red',

                fillOpacity: 0.15,

                radius: 2500

            })
            .addTo(map)

            .bindPopup(`

                🚨 NEARBY OUTBREAK ALERT<br><br>

                📍 ${item.district}<br>

                🌾 ${item.crop}<br>

                🐛 ${item.pest}<br><br>

                📏 Distance:
                ${dist.toFixed(2)} km

            `);
        }
    });

});

</script>

<?php include 'layout_end.php'; ?>