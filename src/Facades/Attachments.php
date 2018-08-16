<?php

namespace DigitSoft\Attachments\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Attachments
 * @package DigitSoft\Attachments\Facades
 * @method static \DigitSoft\Attachments\Attachment createFromFile(\Illuminate\Http\File|\Illuminate\Http\UploadedFile $file, $group, $private = false)
 * @method static array saveFile(\Illuminate\Http\UploadedFile $uploadedFile, $group, $private = false)
 * @method static void addUsage(\DigitSoft\Attachments\Attachment $attachment, $model_id, $model_type)
 * @method static void removeUsage(\DigitSoft\Attachments\Attachment $attachment, $model_id, $model_type)
 * @method static bool hasUsage(\DigitSoft\Attachments\Attachment $attachment, $model_id, $model_type)
 * @method static string getUrl(\DigitSoft\Attachments\Attachment $attachment)
 * @method static string getSavePath($type = 'public', $group = null, $full = false)
 * @method static void cleanUp($expire_time = null, $onlyDb = false)
 * @method static \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter getStoragePublic()
 * @method static \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter getStoragePrivate()
 * @see \DigitSoft\Attachments\AttachmentsManager
 */
class Attachments extends Facade
{
    /**
     * @inheritdoc
     */
    protected static function getFacadeAccessor()
    {
        return 'attachments';
    }
}