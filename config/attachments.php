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
    | Path for saving image cache (resized images)
    |------------------------------------------------------
    | Relative to storage/app dir
    */
    'image_cache_path' => 'public/images',
    /*
    |------------------------------------------------------
    | Absolute URL settings
    |------------------------------------------------------
    | If null system settings will be used
    */
    'url' => [
        'host' => null,                             //host for file downloads
        'scheme' => null,                           //scheme for file download
        'base_path' => 'storage/attachments',       //base path for file download
        'private' => [
            'obtain' => 'attachments/obtain',       //route name for private files obtain url
            'download' => 'attachments/download',   //route name for private files download
        ],
    ],
    /*
    |------------------------------------------------------
    | Storage disk for public files
    |------------------------------------------------------
    */
    'public_storage' => 'local',
    /*
    |------------------------------------------------------
    | Storage disk for private files
    |------------------------------------------------------
    */
    'private_storage' => 'local',
    /*
    |------------------------------------------------------
    | Attachments expire time
    |------------------------------------------------------
    | Time after what attachments without usage will be deleted
    */
    'expire_time' => 10800,
    /*
    |------------------------------------------------------
    | Redis connection name
    |------------------------------------------------------
    | Connection name to redis for private files token generation
    */
    'redis_connection' => null,
    /*
    |------------------------------------------------------
    | Token expire time
    |------------------------------------------------------
    | Private file token expire time in seconds
    */
    'token_expire' => 3600,
    /*
    |------------------------------------------------------
    | User model class
    |------------------------------------------------------
    */
    'user_model' => 'App\Models\User',
];