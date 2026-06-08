<?php

return [
    'dist_cache_path' => env('DIST_CACHE_PATH') ?: storage_path('app/private/dists'),
    'git_clone_path' => env('GIT_CLONE_PATH') ?: storage_path('app/private/repos'),
    'proxy_cache_path' => env('PROXY_CACHE_PATH') ?: storage_path('app/private/proxy-cache'),

    'audit' => [
        'mail_to' => env('AUDIT_MAIL_TO'),
        'slack_channel' => env('AUDIT_SLACK_CHANNEL'),
    ],
];
