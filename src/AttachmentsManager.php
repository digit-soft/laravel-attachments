<?php

namespace DigitSoft\Attachments;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Http\File;
use Illuminate\Http\Testing\File as FileTest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

/**
 * Class AttachmentsManager
 * @package DigitSoft\Attachments
 */
class AttachmentsManager
{
    const STORAGE_PRIVATE = 'private';
    const STORAGE_PUBLIC = 'public';

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
     * @var array
     */
    protected $storageDiskNames = [];

    /**
     * AttachmentsManager constructor.
     * @param Filesystem $files
     * @param Repository $config
     */
    public function __construct(Filesystem $files, Repository $config)
    {
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Add usage to attachment
     * @param Attachment $attachment
     * @param int|string $model_id
     * @param string     $model_type
     */
    public function addUsage(Attachment $attachment, $model_id, $model_type)
    {
        if ($this->hasUsage($attachment, $model_id, $model_type)) {
            return;
        }
        $attachment->usages()->create([
            'model_id' => $model_id,
            'model_type' => $model_type
        ]);
    }

    /**
     * Remove usages from attachment
     * @param Attachment $attachment
     * @param int|string $model_id
     * @param string     $model_type
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
     * @param Attachment $attachment
     * @param int|string $model_id
     * @param string     $model_type
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
     * @param Attachment $attachment
     * @param bool       $absolute
     * @return string
     */
    public function getUrl(Attachment $attachment, $absolute = true)
    {
        return $attachment->private ? $this->getUrlPrivateObtain($attachment, $absolute) : $this->getUrlPublic($attachment, $absolute);
    }

    /**
     * Get URL to download private attachment
     * @param Attachment $attachment
     * @return null|string
     */
    public function getUrlPrivate(Attachment $attachment)
    {
        $user = auth()->user();
        if (!$this->tokenManager()->canDownload($attachment, $user) || ($token = $this->tokenManager()->obtain($attachment, $user)) === null) {
            return null;
        }

        $route = $this->config->get('attachments.url.private.download');
        $url = $this->getUrlAbsoluteBase() . '/' . $route . '/' . $token;
        return $url;
    }

    /**
     * Create an attachment from file path
     * @param string      $filePath
     * @param string|null $group
     * @param bool        $private
     * @return Attachment
     */
    public function createFromPath($filePath, $group = null, $private = false)
    {
        $file = new File($filePath);
        return $this->createFromFile($file, $group, $private);
    }

    /**
     * Create attachment from file object
     * @param File|FileTest $file
     * @param string|null   $group
     * @param bool          $private
     * @return Attachment
     */
    public function createFromFile($file, $group = null, $private = false)
    {
        if ($file instanceof UploadedFile || !$this->isInSaveDir($file->getRealPath())) {
            list($nameOriginal, $file) = $this->saveFile($file, $group, $private);
        } else {
            $nameOriginal = $file->getFilename();
        }
        $data = [
            'name' => $file->getFilename(),
            'name_original' => $nameOriginal,
            'group' => $group,
            'private' => $private,
            'user_id' => auth()->id(),
        ];
        return new Attachment($data);
    }

    /**
     * Save uploaded file to storage
     * @param UploadedFile|File|FileTest $file
     * @param string            $group
     * @param bool              $private
     * @return array
     */
    public function saveFile($file, $group, $private = false)
    {
        $storageType = $private ? static::STORAGE_PRIVATE : static::STORAGE_PUBLIC;
        $nameOriginal = $file instanceof UploadedFile && $file->getClientOriginalName() ? $file->getClientOriginalName() : $file->getFilename();
        $storage = $this->getStorage($storageType);
        $savePath = $this->getSavePath($storageType, $group);
        $nameSaved = $storage->putFile($savePath, $file);
        if (!$nameSaved) {
            return [null, null];
        }
        $newFile = new File($this->convertPathToReal($nameSaved));
        return [$nameOriginal, $newFile];
    }

    /**
     * Cleanup DB from unused attachments
     * @param int|null $expire_time
     * @param bool     $onlyDb
     * @throws \Exception
     */
    public function cleanUp($expire_time = null, $onlyDb = false)
    {
        $expire_time = $expire_time ?? $this->config->get('attachments.expire_time');
        $timestamp = now()->subSeconds($expire_time);
        /** @var Attachment[] $attachments */
        $attachments = Attachment::query()
            ->whereDate('created_at', '<', $timestamp)
            ->doesntHave('usages')
            ->get();
        foreach ($attachments as $attachment) {
            $attachmentPath = $attachment->path();
            if (!$onlyDb && ($storage = $attachment->storage()) !== null && $storage->exists($attachmentPath)) {
                $storage->delete($attachmentPath);
            }
            $attachment->delete();
        }
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
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    public function getStoragePublic()
    {
        return $this->getStorage(static::STORAGE_PUBLIC);
    }

    /**
     * Get private storage
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    public function getStoragePrivate()
    {
        return $this->getStorage(static::STORAGE_PRIVATE);
    }

    /**
     * Get storage save path
     * @param string      $type
     * @param string|null $group
     * @param bool        $full
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
     * @param string $name
     * @param string $type
     */
    public function setStorage($name, $type = self::STORAGE_PUBLIC)
    {
        $this->storageDiskNames[$type] = $name;
    }

    /**
     * Get storage by type
     * @param string $type
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    protected function getStorage($type = self::STORAGE_PUBLIC)
    {
        $storageConfigKey = 'attachments.' . $type . '_storage';
        $storageDiskName = $this->storageDiskNames[$type] ?? $this->config->get($storageConfigKey, 'local');
        return Storage::disk($storageDiskName);
    }

    /**
     * Get public URL
     * @param Attachment $attachment
     * @param bool       $absolute
     * @return string
     */
    protected function getUrlPublic(Attachment $attachment, $absolute = true)
    {
        $path = $attachment->path();
        $prefix = $this->config->get('attachments.save_path_public');
        $path = strpos($path, $prefix) === 0 ? substr($path, strlen($prefix) + 1) : $path;

        $basePath = $this->config->get('attachments.url.base_path');
        $basePath = $basePath ? '/' . ltrim($basePath, '/') : '';
        if (!$absolute) {
            return $basePath . '/' . $path;
        }

        return $this->getUrlAbsoluteBase() . $basePath . '/' . $path;
    }

    /**
     * Get private URL
     * @param Attachment $attachment
     * @param bool       $absolute
     * @return \Illuminate\Contracts\Routing\UrlGenerator|string
     */
    protected function getUrlPrivateObtain(Attachment $attachment, $absolute = true)
    {
        $route = $this->config->get('attachments.url.private.obtain');
        return url($route, ['id' => $attachment->id]);
    }

    /**
     * Get absolute base URL
     * @return string
     */
    protected function getUrlAbsoluteBase()
    {
        $scheme = $this->config->get('attachments.url.scheme');
        $host = $this->config->get('attachments.url.host');
        $scheme = $scheme ?? Request::getScheme();
        $host = $host ?? Request::getHost();
        $url = $scheme . '://' . $host;
        return $url;
    }

    /**
     * Check that path is in save directory
     * @param string $path
     * @param bool   $private
     * @return bool
     */
    protected function isInSaveDir($path, $private = false)
    {
        $storageType = $private ? static::STORAGE_PRIVATE : static::STORAGE_PUBLIC;
        $savePath = $this->getSavePath($storageType, null, true);
        return strpos($path, $savePath) === 0;
    }

    /**
     * @param string $storagePath
     * @return string
     */
    protected function convertPathToReal($storagePath)
    {
        $ds = DIRECTORY_SEPARATOR;
        return app()->storagePath() . $ds . 'app' . $ds . ltrim($storagePath, $ds);
    }

    /**
     * @param string $realPath
     * @return string
     */
    protected function convertPathToStorage($realPath)
    {
        $pathStorage = app()->storagePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;
        return strpos($realPath, $pathStorage) === 0 ? substr($realPath, strlen($pathStorage)) : $realPath;
    }

    /**
     * Get token manager instance
     * @return TokenManager
     */
    protected function tokenManager()
    {
        return app('attachments.token');
    }
}