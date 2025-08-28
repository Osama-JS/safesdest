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
    'token' => env('POSTMARK_TOKEN'),
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

  'mapbox' => [
    'token' => env('MAPBOX_TOKEN'),
    'style' => env('MAPBOX_STYLE'),
  ],

  'recaptcha' => [
    'site_key' => env('RECAPTCHA_SITE_KEY'),
    'secret_key' => env('RECAPTCHA_SECRET_KEY'),
    'min_score' => env('RECAPTCHA_MIN_SCORE', 0.5),
  ],

  'signit' => [
    'base_url'      => env('SIGNIT_BASE_URL'),
    'client_id'     => env('SIGNIT_CLIENT_ID'),
    'client_secret' => env('SIGNIT_CLIENT_SECRET'),
    'scope'         => env('SIGNIT_SCOPE'),
  ],

  'firebase' => [
    'project_id' => env('FIREBASE_PROJECT_ID'),
    'credentials' => env('FIREBASE_CREDENTIALS'),
    'database_url' => env('FIREBASE_DATABASE_URL'),
  ],

];
