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

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    /*
    | CrossRef DOI registration. Leave CROSSREF_PREFIX empty until the journal
    | has a real member prefix (10.NNNN). A placeholder such as 10.XXXX is
    | rejected and no DOI is written.
    */
    'crossref' => [
        'prefix' => env('CROSSREF_PREFIX'),
        'username' => env('CROSSREF_USERNAME'),
        'password' => env('CROSSREF_PASSWORD'),
        'depositor_name' => env('CROSSREF_DEPOSITOR_NAME', 'SRT Journal of Multidisciplinary Research'),
        'depositor_email' => env('CROSSREF_DEPOSITOR_EMAIL', 'journal@srtc.ac.in'),
        'deposit_url' => env('CROSSREF_DEPOSIT_URL', 'https://doi.crossref.org/servlet/deposit'),
    ],

];
