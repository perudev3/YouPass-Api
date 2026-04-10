<?php

return [

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'factiliza' => [
        'token' => env('FACTILIZA_TOKEN'),
        'instance' => env('FACTILIZA_INSTANCE'),
    ],

    'flow' => [
        'api_key'       => env('FLOW_API_KEY'),
        'secret_key'    => env('FLOW_SECRET_KEY'),
        'api_url'       => env('FLOW_API_URL'),
        'api_url_prod'  => env('FLOW_API_URL_PROD'),
    ],

];
