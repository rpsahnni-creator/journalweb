<?php

return [
    'disk' => 'manuscripts',

    'manuscript' => [
        'extensions' => ['pdf', 'doc', 'docx'],
        'mimetypes' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'max_kilobytes' => 20480,
    ],

    'cover_letter' => [
        'extensions' => ['pdf', 'doc', 'docx'],
        'mimetypes' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'max_kilobytes' => 5120,
    ],

    'supplementary' => [
        'extensions' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'zip', 'png', 'jpg', 'jpeg'],
        'mimetypes' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/csv',
            'text/plain',
            'application/zip',
            'application/x-zip-compressed',
            'image/png',
            'image/jpeg',
        ],
        'max_kilobytes' => 15360,
    ],
];
