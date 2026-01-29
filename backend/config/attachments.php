<?php

return [
    'disk' => env('ATTACHMENTS_DISK', 'public'),
    'max_size_kb' => (int) env('ATTACHMENTS_MAX_SIZE_KB', 5120),
    'allowed_mimes' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
];
