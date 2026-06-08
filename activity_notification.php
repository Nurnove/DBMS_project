<?php
require_once 'db.php';

$today = date('Y-m-d');

/* =========================================
   GET ALL SCHEDULED ACTIVITIES WITH CROP INFO
========================================= */

$result = $conn->query("
    SELECT
        a.*,
        fc.planted_date,
        fc.user_id,
        c.name AS crop_name,
        u.location_id
    FROM activity_schedule a
    JOIN farmer_crops fc ON a.farmer_crop_id = fc.id
    JOIN crops c ON fc.crop_id = c.id
    JOIN users u ON fc.user_id = u.id
");

while ($row = $result->fetch_assoc()) {

    $uid      = (int)$row['user_id'];
    $locId    = (int)($row['location_id'] ?? 0);
    $cropName = $row['crop_name'];

    $activityDate = date(
        'Y-m-d',
        strtotime($row['planted_date'] . " +" . $row['day_number'] . " days")
    );

    if ($activityDate !== $today) continue;

    /* =========================================
       GET WEATHER FOR THIS USER'S LOCATION
    ========================================= */

    $weather = null;
    if ($locId) {
        $w = $conn->query("
            SELECT * FROM weather_data
            WHERE location_id = $locId
            ORDER BY recorded_at DESC LIMIT 1
        ");
        if ($w) $weather = $w->fetch_assoc();
    }

    /* =========================================
       BUILD NOTIFICATION MESSAGE
    ========================================= */

    $activity = $row['activity'];
    $extra    = '';

    if ($weather) {
        $rain  = (float)$weather['rain_probability'];
        $humid = (float)$weather['humidity'];
        $temp  = (float)$weather['temperature'];

        if (stripos($activity, 'irrigation') !== false && $rain >= 70) {
            $extra = " ⚠ Heavy rain expected — consider skipping irrigation.";
        }
        if (stripos($activity, 'fertilizer') !== false && $temp >= 38) {
            $extra = " ⚠ High temperature — apply early morning only.";
        }
        if ((stripos($activity, 'monitoring') !== false || stripos($activity, 'inspection') !== false) && $humid >= 85) {
            $activity = "🚨 URGENT: " . $activity;
            $extra    = " High humidity — disease risk elevated.";
        }
    }

    $title = "🌾 Farm Activity Reminder";
    $msg   = "Day {$row['day_number']}: {$activity} for {$cropName}.{$extra}";

    /* Avoid duplicate per user per day */
    $safeTitle = $conn->real_escape_string($title);
    $safeMsg   = $conn->real_escape_string($msg);

    $check = $conn->query("
        SELECT id FROM notifications
        WHERE user_id = $uid
        AND title = '$safeTitle'
        AND message = '$safeMsg'
        AND DATE(created_at) = CURDATE()
        LIMIT 1
    ");

    if ($check->num_rows === 0) {
        $conn->query("
            INSERT INTO notifications (user_id, title, message, type)
            VALUES ($uid, '$safeTitle', '$safeMsg', 'activity')
        ");
        echo "✅ Notified user #{$uid}: {$activity} ({$cropName})<br>";
    } else {
        echo "⏭ Already notified: user #{$uid} ({$cropName})<br>";
    }
}

echo "<hr>✅ Notification run complete — " . date('Y-m-d H:i:s');
?>