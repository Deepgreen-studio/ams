<?php

return [
    /*
    |--------------------------------------------------------------------------
    | API Request Logging
    |--------------------------------------------------------------------------
    */
    'log_api_requests' => env('AUDIT_LOG_API_REQUESTS', true),

    'log_api_in_tests' => env('AUDIT_LOG_API_IN_TESTS', false),

    /*
    |--------------------------------------------------------------------------
    | Exception Persistence
    |--------------------------------------------------------------------------
    */
    'log_exceptions' => env('AUDIT_LOG_EXCEPTIONS', true),
];
