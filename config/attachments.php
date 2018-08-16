<?php

return [
    /*
    |------------------------------------------------------
    | Path for saving public attachments
    |------------------------------------------------------
    | Relative to storage/app dir
    */
    'save_path_public' => 'public/attachments',
    /*
    |------------------------------------------------------
    | Path for saving private attachments
    |------------------------------------------------------
    | Relative to storage/app dir
    */
    'save_path_private' => 'private/attachments',
    /*
    |------------------------------------------------------
    | Absolute URL settings
    |------------------------------------------------------
    | If null system settings will be used
    */
    'url' => [
        'host' => null,
        'scheme' => null,
        'base_path' => 'storage/attachments',
        'private_route' => 'attachments/download'
    ],
    'public_storage' => 'local',
    'private_storage' => 'local',
    'expire_time' => 10800,
];