<?php

    $url = 'https://api.worldota.net/api/b2b/v3/hotel/info/?data={"id":"beach_studio_apartment_3_komi","language":"en"}';
    $keyId = '7788';
    $apiKey = 'e6a79dc0-c452-48e0-828d-d37614165e39';

    $credentials = base64_encode($keyId . ':' . $apiKey);
    $headers = array(
        'Authorization: Basic ' . $credentials,
    );

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification

    $response = curl_exec($ch);
    $http_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if ($http_status === 200) {
        echo 'Response: ' . $response . PHP_EOL;
        $responseArray = json_decode($response, true);
        if ($responseArray !== null) {
            foreach ($responseArray as $element) {
                var_dump($element);
            }
        } else {
            echo 'Error decoding JSON response.' . PHP_EOL;
        }
    } else {
        echo 'Error: HTTP status code ' . $http_status . PHP_EOL;
        echo 'Error message: ' . curl_error($ch) . PHP_EOL;
    }

    curl_close($ch);

?>