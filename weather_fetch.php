<?php

require_once 'db.php';

/* =========================================
   API KEY
========================================= */

$apiKey = getenv('API_KEY');

/* =========================================
   GET LOCATIONS
========================================= */

$locations = $conn->query("
    SELECT *
    FROM locations
    WHERE latitude IS NOT NULL
    AND longitude IS NOT NULL
");

/* =========================================
   LOOP
========================================= */

while($loc = $locations->fetch_assoc()) {

    $location_id = (int)$loc['id'];

    $district = $loc['district'];

    $lat = trim($loc['latitude']);
    $lon = trim($loc['longitude']);

    /* =====================================
       API URL
    ===================================== */

    $url = "https://api.openweathermap.org/data/2.5/weather?lat={$lat}&lon={$lon}&appid={$apiKey}&units=metric";

    /* =====================================
       CURL (BETTER THAN file_get_contents)
    ===================================== */

    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    curl_close($ch);

    /* =====================================
       FAILED
    ===================================== */

    if($httpCode != 200 || !$response) {

        echo "❌ Failed: {$district}<br>";
        continue;
    }

    /* =====================================
       JSON
    ===================================== */

    $data = json_decode($response, true);

    if(!isset($data['main'])) {

        echo "⚠ Invalid data: {$district}<br>";
        continue;
    }

    /* =====================================
       WEATHER VALUES
    ===================================== */

    $temp = $data['main']['temp'];

    $humidity = $data['main']['humidity'];

    $rainfall = 0;

    if(isset($data['rain']['1h'])) {
        $rainfall = $data['rain']['1h'];
    }

    /* =====================================
       RAIN PROBABILITY
    ===================================== */

    $rain_probability = 0;

    if($humidity >= 85) {
        $rain_probability = 80;
    }
    elseif($humidity >= 70) {
        $rain_probability = 60;
    }
    elseif($humidity >= 50) {
        $rain_probability = 35;
    }
    else {
        $rain_probability = 10;
    }

    /* =====================================
       INSERT
    ===================================== */

    $conn->query("
        INSERT INTO weather_data
        (
            location_id,
            temperature,
            rainfall,
            humidity,
            rain_probability
        )
        VALUES
        (
            $location_id,
            '$temp',
            '$rainfall',
            '$humidity',
            '$rain_probability'
        )
    ");

    echo "✅ Updated: {$district}<br>";

    /* =====================================
       DELAY (IMPORTANT)
    ===================================== */

    sleep(1);
}

echo "<hr>🌦 Weather update complete.";

?>