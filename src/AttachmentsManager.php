<?php

namespace DigitSoft\Attachments;

use Illuminate\Http\File;
use GuzzleHttp\RequestOptions;
use Illuminate\Config\Repository;
use Illuminate\Http\UploadedFile;
use GuzzleHttp\Client as HttpClient;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\Testing\File as FileTest;

/**
 * Class AttachmentsManager
 */
class AttachmentsManager
{
    const STORAGE_PRIVATE = 'private';
    const STORAGE_PUBLIC  = 'public';

    const ROUTE_PRIVATE_UPLOAD          = 'attachments.upload.private';
    const ROUTE_PRIVATE_UPLOAD_MULTIPLE = 'attachments.upload.private.multiple';
    const ROUTE_PUBLIC_UPLOAD           = 'attachments.upload.public';
    const ROUTE_PUBLIC_UPLOAD_IMAGE     = 'attachments.upload.image';
    const ROUTE_PUBLIC_UPLOAD_MULTIPLE  = 'attachments.upload.public.multiple';
    const ROUTE_PRIVATE_OBTAIN          = 'attachments.obtain.private';
    const ROUTE_PRIVATE_DOWNLOAD        = 'attachments.download.private';
    const ROUTE_IMAGE_PRESET_DOWNLOAD   = 'attachments.image.preset';

    /**
     * @var Filesystem
     */
    protected $files;
    /**
     * @var Repository
     */
    protected $config;
    /**
     * For test purpose
     *
     * @var array
     */
    protected $storageDiskNames = [];
    /**
     * File size limits cache
     *
     * @var array
     */
    protected $fileSizeLimitsByExt;

    /**
     * AttachmentsManager constructor.
     *
     * @param  Filesystem $files
     * @param  Repository $config
     */
    public function __construct(Filesystem $files, Repository $config)
    {
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Add usage to attachment
     *
     * @param  Attachment $attachment
     * @param  int|string $model_id
     * @param  string     $model_type
     * @param  string     $tag
     */
    public function addUsage(Attachment $attachment, $model_id, $model_type, $tag = AttachmentUsage::TAG_DEFAULT)
    {
        if ($this->hasUsage($attachment, $model_id, $model_type)) {
            return;
        }
        $attachment->usages()->create([
            'model_id' => $model_id,
            'model_type' => $model_type,
            'tag' => $tag,
        ]);
    }

    /**
     * Remove usages from attachment
     *
     * @param  Attachment $attachment
     * @param  int|string $model_id
     * @param  string     $model_type
     */
    public function removeUsage(Attachment $attachment, $model_id, $model_type)
    {
        $attachment->usages()
            ->where('model_id', '=', $model_id)
            ->where('model_type', '=', $model_type)
            ->delete();
    }

    /**
     * Check that attachment has usage by model
     *
     * @param  Attachment $attachment
     * @param  int|string $model_id
     * @param  string     $model_type
     * @return bool
     */
    public function hasUsage(Attachment $attachment, $model_id, $model_type)
    {
        return $attachment->usages()
            ->where('model_id', '=', $model_id)
            ->where('model_type', '=', $model_type)
            ->exists();
    }

    /**
     * Get attachment URL
     *
     * @param  Attachment $attachment
     * @param  bool       $absolute
     * @return string
     */
    public function getUrl(Attachment $attachment, $absolute = true)
    {
        return $attachment->private ? $this->getUrlPrivateObtain($attachment, $absolute) : $this->getUrlPublic($attachment, $absolute);
    }

    /**
     * Get URL to download private attachment
     *
     * @param  Attachment $attachment
     * @return null|string
     */
    public function getUrlPrivate(Attachment $attachment)
    {
        $user = auth()->user();
        if (! $this->tokenManager()->canDownload($attachment, $user) || ($token = $this->tokenManager()->obtain($attachment, $user)) === null) {
            return null;
        }

        $relUrl = url()->route(static::ROUTE_PRIVATE_DOWNLOAD, ['token' => $token], false);

        return $this->getUrlAbsoluteBase() . '/' . ltrim($relUrl, '/');
    }

    /**
     * Get attachment by token string
     *
     * @param  string $token
     * @return Attachment|null
     */
    public function getAttachmentByToken(string $token)
    {
        [$attachmentId] = $this->tokenManager()->get($token);

        return $attachmentId !== null ? Attachment::whereId($attachmentId)->first() : null;
    }

    /**
     * Create an attachment from remote file URL
     *
     * @param  string      $fileUrl
     * @param  string|null $group
     * @param  bool        $private
     * @param  int|null    $creatorId
     * @return Attachment|null
     */
    public function createFromUrl($fileUrl, $group = null, $private = false, $creatorId = null)
    {
        $client = new HttpClient(['verify' => false]);
        $tmpFileName = tempnam(sys_get_temp_dir(), 'attDl');
        $tmpFileStream = fopen($tmpFileName, 'w+');
        try {
            $client->get($fileUrl, [RequestOptions::SINK => $tmpFileStream]);
        } catch (GuzzleException $exception) {
            return null;
        }
        $baseName = basename($fileUrl);
        if (! preg_match('/^[a-zA-Z0-9_-]+\.[a-zA-Z0-9]+$/i', $baseName)) {
            $baseName = '';
        }
        $uploadedFile = new UploadedFile($tmpFileName, $baseName);

        return $this->createFromFile($uploadedFile, $group, $private, $creatorId);
    }

    /**
     * Create an attachment from file path
     *
     * @param  string      $filePath
     * @param  string|null $group
     * @param  bool        $private
     * @param  int|null    $creatorId
     * @return Attachment
     */
    public function createFromPath($filePath, $group = null, $private = false, $creatorId = null)
    {
        $file = new File($filePath);

        return $this->createFromFile($file, $group, $private, $creatorId);
    }

    /**
     * Create attachment from file object
     *
     * @param  File|UploadedFile|FileTest $file
     * @param  string|null                $group
     * @param  bool                       $private
     * @param  int|null                   $creatorId
     * @return Attachment
     */
    public function createFromFile($file, $group = null, $private = false, $creatorId = null)
    {
        if ($file instanceof UploadedFile || ! $this->isInSaveDir($file->getRealPath())) {
            [$nameOriginal, $file] = $this->saveFile($file, $group, $private);
        } else {
            $nameOriginal = $file->getFilename();
        }
        $data = [
            'name' => $file->getFilename(),
            'name_original' => $nameOriginal,
            'group' => $group,
            'private' => $private,
            'user_id' => $creatorId,
        ];

        return new Attachment($data);
    }

    /**
     * Save uploaded file to storage
     *
     * @param  UploadedFile|File|FileTest $file
     * @param  string                     $group
     * @param  bool                       $private
     * @return array
     */
    public function saveFile($file, $group, $private = false)
    {
        $storageType = $private ? static::STORAGE_PRIVATE : static::STORAGE_PUBLIC;
        $nameOriginal = $file instanceof UploadedFile && $file->getClientOriginalName() ? $file->getClientOriginalName() : $file->getFilename();
        $storage = $this->getStorage($storageType);
        $savePath = $this->getSavePath($storageType, $group);
        $nameSaved = $storage->putFileAs($savePath, $file, $this->getFileSaveName($file));
        if (! $nameSaved) {
            return [null, null];
        }
        $realPath = $this->convertPathToReal($nameSaved);
        chmod($realPath, 0666);
        $newFile = new File($realPath);

        return [$nameOriginal, $newFile];
    }

    /**
     * Save resource to storage
     *
     * @param  resource $resource
     * @param  string   $fileName
     * @param  string   $group
     * @param  bool     $private
     * @return array
     */
    public function saveResource($resource, $fileName, $group, $private = false)
    {
        $storageType = $private ? static::STORAGE_PRIVATE : static::STORAGE_PUBLIC;
        $storage = $this->getStorage($storageType);
        $savePath = $this->getSavePath($storageType, $group);
        rewind($resource);
        $nameSaved = $storage->put($savePath, $resource);
        if (! $nameSaved) {
            return [null, null];
        }
        $realPath = $this->convertPathToReal($nameSaved);
        chmod($realPath, 0666);
        $newFile = new File($realPath);

        return [$fileName, $newFile];
    }

    /**
     * Cleanup DB from unused attachments
     *
     * @param  int|null $expire_time Expire time for attachment
     * @param  bool     $onlyDb      Remove only from DB
     * @param  int|null $batchSize   Size of batch for query results
     * @return int Removed count
     * @throws \Exception
     */
    public function cleanup($expire_time = null, $onlyDb = false, $batchSize = 200)
    {
        $removedCount = 0;
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $expire_time = $expire_time ?? $this->config->get('attachments.expire_time');
        $timestamp = now()->subSeconds($expire_time);
        /** @var Attachment[] $attachments */
        $query = Attachment::query()
            ->where(function ($queryDates) use ($timestamp) {
                /** @var \Illuminate\Database\Eloquent\Builder $queryDates */
                $queryDates->whereDate('created_at', '<', $timestamp)
                    ->orWhere(function ($queryDatesEq) use ($timestamp) {
                        /** @var \Illuminate\Database\Eloquent\Builder $queryDatesEq */
                        $queryDatesEq->whereDate('created_at', '=', $timestamp)
                            ->whereTime('created_at', '<', $timestamp);
                    });
            })
            ->whereDoesntHave('usages')
            ->oldest();
        $query->each(function ($attachment) use ($onlyDb, &$removedCount) {
            /** @var Attachment $attachment */
            $attachmentPath = $attachment->path();
            if (! $onlyDb && ($storage = $attachment->storage()) !== null && $storage->exists($attachmentPath)) {
                $storage->delete($attachmentPath);
            }
            $attachment->delete();
            $removedCount++;
        }, $batchSize);

        return $removedCount;
    }

    /**
     * Example of routes
     */
    public function routes()
    {
        $attachmentsCtrl = '\DigitSoft\Attachments\Controllers\AttachmentsController';
        $imagesCtrl = '\DigitSoft\Attachments\Controllers\ImagesController';
        Route::post('attachments/upload/{group?}/{name?}', $attachmentsCtrl . '@uploadFile');
        Route::post('attachments/upload-private/{group?}/{name?}', $attachmentsCtrl . '@uploadFile');
        Route::post('attachments/upload-multiple/{group?}', $attachmentsCtrl . '@uploadFiles');
        Route::post('attachments/upload-multiple-private/{group?}', $attachmentsCtrl . '@uploadFilesPrivate');
        Route::get('attachments/download/{id}', $attachmentsCtrl . '@downloadFile');
        Route::get('attachments/url/{id}', $attachmentsCtrl . '@urlFile');

        Route::get('images/{preset}/{file}', $imagesCtrl . '@imagePreset')
            ->where('file', '[A-Za-z0-9\.\/_-]+')
            ->where('preset', '[a-z0-9]+-([a-z0-9])?+(-c)?');
    }

    /**
     * Get public storage
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    public function getStoragePublic()
    {
        return $this->getStorage(static::STORAGE_PUBLIC);
    }

    /**
     * Get private storage
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    public function getStoragePrivate()
    {
        return $this->getStorage(static::STORAGE_PRIVATE);
    }

    /**
     * Get storage save path
     *
     * @param  string      $type
     * @param  string|null $group
     * @param  bool        $full
     * @return string
     */
    public function getSavePath($type = self::STORAGE_PUBLIC, $group = null, $full = false)
    {
        $saveConfigKey = 'attachments.save_path_' . $type;
        $path = $this->config->get($saveConfigKey);
        $path = $group !== null ? $path . DIRECTORY_SEPARATOR . $group : $path;
        if ($full) {
            $path = $this->convertPathToReal($path);
        }

        return $path;
    }

    /**
     * Set storage name for particular storage type
     *
     * @param  string $name
     * @param  string $type
     */
    public function setStorage($name, $type = self::STORAGE_PUBLIC)
    {
        $this->storageDiskNames[$type] = $name;
    }

    /**
     * Get token manager instance
     *
     * @return TokenManager
     */
    public function tokenManager()
    {
        return app('attachments.token');
    }

    /**
     * Get file group rules.
     *
     * @param  string|null $fileGroup
     * @param  bool        $addBail
     * @return array
     * @throws null
     */
    public function getFileGroupRules($fileGroup = null, $addBail = true)
    {
        $fileGroup = is_string($fileGroup) ? $fileGroup : '';
        $fileGroup = preg_replace('/[_\s]+/', '-', $fileGroup);

        $rules = [];
        $rulesRaw = $this->config->get('attachments.group_rules.' . $fileGroup, null);
        $rulesRaw = $rulesRaw ?? $this->config->get('attachments.group_rules.*', []);

        foreach ($rulesRaw as $ruleData) {
            // String rule ot instance
            if (is_string($ruleData) || $ruleData instanceof Rule) {
                $rules[] = $ruleData;
            }
            // Config array for rule creation
            if (is_array($ruleData) && is_string($ruleData[0])) {
                $params = $ruleData[1] ?? [];
                $rules[] = app()->make($ruleData[0], $params);
            }
        }

        // Add bail into rules array
        if ($addBail && ! empty($rules) && ! in_array('bail', $rules, true)) {
            $rules[] = 'bail';
        }

        return $rules;
    }

    /**
     * Get file size limit(s).
     *
     * @param  string|null $extension
     * @return array|int|null
     */
    public function fileSizeGetLimitByExt($extension = null)
    {
        if ($this->fileSizeLimitsByExt === null) {
            $limitsRaw = $this->config->get('attachments.file_size_limits', []);
            foreach ($limitsRaw as $row) {
                [$ext, $limit] = $row;
                $ext = is_array($ext) ? $ext : [$ext];
                $limit = $this->fileSizeNormalizeValue($limit);
                foreach ($ext as $extValue) {
                    $this->fileSizeLimitsByExt[$extValue] = $limit;
                }
            }
        }

        // Look for value by extension
        if ($extension !== null) {
            $extension = mb_strtolower($extension);

            return $this->fileSizeLimitsByExt[$extension] ?? $this->fileSizeLimitsByExt['*'] ?? null;
        }

        return $this->fileSizeLimitsByExt;
    }

    /**
     * Normalize file size given is string format to count of bytes.
     *
     * @param  int|string $size
     * @return float|int
     */
    public function fileSizeNormalizeValue($size)
    {
        if (is_numeric($size)) {
            return $size;
        }

        // Parse string data like 150M or 20MB
        if (is_string($size) && preg_match('/^((?:\d+(?:\s+)?))([KMGB])?B?$/i', $size, $matches)) {
            $sizeNum = (int)preg_replace('/\D+/', '', $matches[1]);
            $pref = mb_strtoupper($matches[2]);
            $powers = ['B', 'K', 'M', 'G', 'T', 'P', 'E', 'Z', 'Y']; // power for given suffix
            if (($power = array_search($pref, $powers, true)) !== false) {
                // If power = 0, then do nothing the size is already in bytes
                $sizeNum = $power > 0 ? $sizeNum * (1024 ** $power) : $sizeNum;
            }

            return $sizeNum;
        }

        return (int)$size;
    }

    /**
     * Format file size.
     *
     * @param  int $size
     * @param  int $precision
     * @return string
     */
    public function fileSizeStringifyValue(int $size, int $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

        $bytes = max($size, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Get uploaded file extension.
     *
     * @param  UploadedFile $file
     * @return string
     */
    public function getUploadedFileExtension($file)
    {
        $ext = method_exists($file, 'getClientOriginalExtension') ? $file->getClientOriginalExtension() : '';
        $ext = $ext === '' && method_exists($file, 'clientExtension') ? $file->clientExtension() : $ext;
        $ext = $ext === '' ? $file->extension() : $ext;

        return is_string($ext) ? mb_strtolower($ext) : '';
    }

    /**
     * Get storage by type.
     *
     * @param  string $type
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    protected function getStorage($type = self::STORAGE_PUBLIC)
    {
        $storageConfigKey = 'attachments.' . $type . '_storage';
        $storageDiskName = $this->storageDiskNames[$type] ?? $this->config->get($storageConfigKey, 'local');

        return Storage::disk($storageDiskName);
    }

    /**
     * Get public URL.
     *
     * @param  Attachment $attachment
     * @param  bool       $absolute
     * @return string
     */
    protected function getUrlPublic(Attachment $attachment, $absolute = true)
    {
        $path = $attachment->path();
        $prefix = $this->config->get('attachments.save_path_public');
        $path = strpos($path, $prefix) === 0 ? substr($path, strlen($prefix) + 1) : $path;

        $basePath = $this->config->get('attachments.url.base_path');
        $basePath = $basePath ? '/' . ltrim($basePath, '/') : '';
        if (! $absolute) {
            return $basePath . '/' . $path;
        }

        return $this->getUrlAbsoluteBase() . $basePath . '/' . $path;
    }

    /**
     * Get private URL.
     *
     * @param  Attachment $attachment
     * @param  bool       $absolute
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    protected function getUrlPrivateObtain(Attachment $attachment, $absolute = true)
    {
        return url()->route(static::ROUTE_PRIVATE_OBTAIN, ['id' => $attachment->id], $absolute);
    }

    /**
     * Get absolute base URL.
     *
     * @return string
     */
    protected function getUrlAbsoluteBase()
    {
        $scheme = $this->config->get('attachments.url.scheme');
        $host = $this->config->get('attachments.url.host');
        $scheme = $scheme ?? Request::getScheme();
        $host = $host ?? Request::getHost();

        return $scheme . '://' . $host;
    }

    /**
     * Check that path is in save directory
     *
     * @param  string $path
     * @param  bool   $private
     * @return bool
     */
    protected function isInSaveDir($path, $private = false)
    {
        $storageType = $private ? static::STORAGE_PRIVATE : static::STORAGE_PUBLIC;
        $savePath = $this->getSavePath($storageType, null, true);

        return strpos($path, $savePath) === 0;
    }

    /**
     * @param  string $storagePath
     * @return string
     */
    protected function convertPathToReal($storagePath)
    {
        $ds = DIRECTORY_SEPARATOR;

        return app()->storagePath() . $ds . 'app' . $ds . ltrim($storagePath, $ds);
    }

    /**
     * @param  string $realPath
     * @return string
     */
    protected function convertPathToStorage($realPath)
    {
        $pathStorage = app()->storagePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

        return strpos($realPath, $pathStorage) === 0 ? substr($realPath, strlen($pathStorage)) : $realPath;
    }

    /**
     * Get file name for save into the storage.
     *
     * @param  UploadedFile $file
     * @return string
     */
    protected function getFileSaveName($file)
    {
        $hashName = $file->hashName();
        if (($ext = $this->getUploadedFileExtension($file)) === '') {
            return $hashName;
        }

        if (count($nameExploded = explode('.', $hashName)) >= 2) {
            array_pop($nameExploded);
        }
        $nameExploded[] = $ext;

        return implode('.', $nameExploded);
    }
}
