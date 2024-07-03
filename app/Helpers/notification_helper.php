<?php

use App\Models\User;
use App\Services\CachingService;

function send_notification($user, $title, $body, $type, $customData = []) {
    $FcmToken = User::where('fcm_id', '!=', '')->whereIn('id', $user)->get()->pluck('fcm_id');

    $cache = app(CachingService::class);

    $url = 'https://fcm.googleapis.com/fcm/send';
    $serverKey = $cache->getSystemSettings('fcm_server_key');

    $notification_data1 = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        "title"        => $title,
        "body"         => $body,
        "type"         => $type,
        ...$customData

    ];

    $notification_data2 = [
        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
        "type"         => $type,
        ...$customData
    ];

    $data = [
        "registration_ids" => $FcmToken,
        "notification"     => $notification_data1,
        "data"             => $notification_data2,
        "priority"         => "high"
    ];
    $encodedData = json_encode($data);

    $headers = [
        'Authorization:key=' . $serverKey,
        'Content-Type: application/json',
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);

    // Disabling SSL Certificate support temporarily
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $encodedData);

    // Execute post
    if (!curl_exec($ch)) {
        die('Curl failed: ' . curl_error($ch));
    }
    // dd($result);

    // Close connection
    curl_close($ch);
}
