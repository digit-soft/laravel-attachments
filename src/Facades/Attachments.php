<?php

namespace DigitSoft\Attachments\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class Attachments
 * @package DigitSoft\Attachments\Facades
 * @method static \DigitSoft\Attachments\Attachment createFromUrl(string $fileUrl, $group = null, $private = false)
 * @method static \DigitSoft\Attachments\Attachment createFromPath(string $filePath, $group = null, $private = false)
 * @method static \DigitSoft\Attachments\Attachment createFromFile(\Illuminate\Http\File|\Illuminate\Http\UploadedFile $file, $group = null, $private = false)
 * @method static array saveFile(\Illuminate\Http\UploadedFile $uploadedFile, $group, $private = false)
 * @method static void addUsage(\DigitSoft\Attachments\Attachment $attachment, $model_id, $model_type)
 * @method static void removeUsage(\DigitSoft\Attachments\Attachment $attachment, $model_id, $model_type)
 * @method static bool hasUsage(\DigitSoft\Attachments\Attachment $attachment, $model_id, $model_type)
 * @method static string getUrl(\DigitSoft\Attachments\Attachment $attachment, $absolute = true)
 * @method static string getUrlPrivate(\DigitSoft\Attachments\Attachment $attachment)
 * @method static string getSavePath($type = 'public', $group = null, $full = false)
 * @method static \DigitSoft\Attachments\Attachment|null getAttachmentByToken(string $token)
 * @method static int cleanup($expire_time = null, $onlyDb = false, $batchSize = 200)
 * @method static \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter getStoragePublic()
 * @method static \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter getStoragePrivate()
 * @method static void routes()
 * @method static \DigitSoft\Attachments\TokenManager tokenManager()
 * @method static string getFileGroupRules(string $fileGroup, $addBail = true)
 * @method static string fileSizeStringifyValue(int $size, int $precision = 2) Format file size for human
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
