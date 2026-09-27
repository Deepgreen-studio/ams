<?php

return [
    /*
    | Refresh an OAuth access token when it expires within this many seconds.
    */
    'oauth_refresh_skew_seconds' => (int) env('INTEGRATION_OAUTH_REFRESH_SKEW', 300),

    /*
    | Skip a health check when the integration was checked more recently than this.
    */
    'health_check_interval_minutes' => (int) env('INTEGRATION_HEALTH_INTERVAL', 15),
];
