<?php

return [
    'max_kb' => (int) env('WORK_FILES_MAX_KB', 10240),

    'allowed_mimes' => [
        'image/jpeg',
        'image/png',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain',
        'text/csv',
    ],

    'blocked_extensions' => [
        'php',
        'phtml',
        'phar',
        'pl',
        'py',
        'sh',
        'bash',
        'bat',
        'cmd',
        'com',
        'exe',
        'cgi',
        'js',
        'jsp',
        'asp',
        'aspx',
    ],
];

