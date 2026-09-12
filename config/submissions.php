<?php

return [
    'disk' => 'submissions',
    'review_due_days' => 21,

    'manuscript' => [
        'extensions' => ['pdf', 'docx'],
        'mimetypes' => [
            'application/pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'max_kilobytes' => 10240,
        'abstract_min_words' => 150,
        'abstract_max_words' => 250,
        'keywords_min' => 4,
        'keywords_max' => 6,
    ],
];
