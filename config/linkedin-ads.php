<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Access Token
    |--------------------------------------------------------------------------
    |
    | The OAuth bearer token used to authenticate requests to the LinkedIn
    | Marketing API. See:
    | https://learn.microsoft.com/en-us/linkedin/shared/authentication/authorization-code-flow
    |
    */
    'access_token' => env('LINKEDIN_ADS_ACCESS_TOKEN'),

    /*
    |--------------------------------------------------------------------------
    | API Version
    |--------------------------------------------------------------------------
    |
    | The LinkedIn Marketing API version segment used in request URLs.
    |
    */
    'version' => env('LINKEDIN_ADS_API_VERSION', 'v2'),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The number of seconds to wait for a response before giving up.
    |
    */
    'timeout' => (int) env('LINKEDIN_ADS_TIMEOUT', 8),
];
