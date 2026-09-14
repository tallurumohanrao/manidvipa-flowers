<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'google_maps' => [
        'key' => env('GOOGLE_MAPS_API_KEY'),
        'referer' => env('GOOGLE_MAPS_REFERER'),
    ],

    'store_origin' => [
        'address' => env(
            'STORE_ORIGIN_ADDRESS',
            '8-3-241/21, Srinivasa Colony, Vengal Rao Nagar, SR Nagar, Hyderabad, Telangana 500038, India'
        ),
        'latitude' => env('STORE_ORIGIN_LATITUDE', 17.4356),
        'longitude' => env('STORE_ORIGIN_LONGITUDE', 78.4467),
        'default_hyderabad_distance_km' => env('DEFAULT_HYDERABAD_DELIVERY_DISTANCE_KM', 12),
    ],

];
