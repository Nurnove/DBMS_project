<?php
require_once 'db.php';

$today = date('Y-m-d');

$sql = "
SELECT
    a.*,
    fc.planted_date,
    c.name AS crop_name
FROM activity_schedule a

JOIN farmer_crops fc
ON a.farmer_crop_id = fc.id

JOIN crops c
ON fc.crop_id = c.id
";

$result = $conn->query($sql);

while($row = $result->fetch_assoc()) {

    $notifyDate = date(
        'Y-m-d',
        strtotime(
            $row['planted_date']
            . " +".$row['day_number']." days"
        )
    );

    if($notifyDate == $today) {

        $uid = $row['user_id'];

        $title = "Farm Activity Reminder";

        $msg =
        "Today: ".$row['activity'].
        " for ".$row['crop_name'];

        /* avoid duplicate */

        $check = $conn->query("
        SELECT id
        FROM notifications
        WHERE user_id=$uid
        AND title='$title'
        AND message='$msg'
        AND DATE(created_at)=CURDATE()
        ");

        if($check->num_rows == 0) {

            $conn->query("
            INSERT INTO notifications
            (
                user_id,
                title,
                message,
                type
            )
            VALUES
            (
                $uid,
                '$title',
                '$msg',
                'activity'
            )
            ");
        }
    }
}