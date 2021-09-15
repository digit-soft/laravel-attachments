<?php

namespace DigitSoft\Attachments;

use Intervention\Image\Constraint;
use Intervention\Image\Facades\Image;
use DigitSoft\Attachments\Facades\Attachments;

class ImagePreset
{
    const MAX_DIMENSION = 3000;

    /**
     * @var int
     */
    public $width;
    /**
     * @var int
     */
    public $height;
    /**
     * @var bool
     */
    public $crop = false;

    /**
     * ImagePreset constructor.
     * @param int  $width
     * @param int  $height
     * @param bool $crop
     */
    public function __construct($width, $height, $crop = false)
    {
        $this->width = $width;
        $this->height = $height;
        $this->crop = $crop;
    }

    /**
     * Execute transformation for file
     *
     * @param  string $fileSourcePath
     * @param  bool   $overwriteSource
     * @return bool
     */
    public function executeForFile($fileSourcePath, $overwriteSource = false)
    {
        $storage = Attachments::getStoragePublic();
        if (! $storage->exists($this->convertPathToStorage($fileSourcePath))) {
            return false;
        }
        $fileDstPath = $overwriteSource
            ? $fileSourcePath
            : $this->dstPath($fileSourcePath, true);
        $dstDirPath = $this->convertPathToStorage(dirname($fileDstPath));
        $img = $this->getImage($fileSourcePath);
        $img->orientate();
        $resizeCallback = function ($constraint) {
            /** @var Constraint $constraint */
            $constraint->aspectRatio();
        };
        if ($this->crop) {
            $img->fit($this->width, $this->height);
        } else {
            $img->resize($this->width, $this->height, $resizeCallback);
        }
        if (! $storage->exists($dstDirPath)) {
            $storage->makeDirectory($dstDirPath, 0777, true);
        }
        try {
            $img->save($fileDstPath);
        } catch (\Intervention\Image\Exception\NotWritableException $exception) {
            return false;
        }

        return true;
    }

    /**
     * Get destination path
     *
     * @param  string $sourcePath
     * @param  bool   $full
     * @return string
     */
    public function dstPath($sourcePath, $full = false)
    {
        $ds = DIRECTORY_SEPARATOR;
        $sourcePath = $this->convertPathToAttachments($sourcePath);
        $sourcePathArr = explode($ds, $sourcePath);
        $fileName = array_pop($sourcePathArr);
        $fileGroup = ! empty($sourcePathArr) ? $ds . implode($ds, $sourcePathArr) : '';
        $presetName = $this->nameEncoded();
        $imgCachePath = config('attachments.image_cache_path');
        $dstPath = $imgCachePath . $ds . $presetName . $fileGroup . $ds . $fileName;

        return $full ? Attachments::getStoragePublic()->path($dstPath) : $dstPath;
    }

    /**
     * Create Image object
     *
     * @param  string $filePath
     * @return \Intervention\Image\Image
     */
    protected function getImage($filePath)
    {
        return Image::make($filePath);
    }

    /**
     * Convert full path => public storage relative path
     *
     * @param  string $fullPath
     * @return string
     */
    protected function convertPathToStorage($fullPath)
    {
        $storagePath = app()->storagePath() . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR;

        return strpos($fullPath, $storagePath) === 0 ? substr($fullPath, strlen($storagePath)) : $fullPath;
    }

    /**
     * Convert full path => Attachments path.
     *
     * @param  string $fullPath
     * @return string
     */
    protected function convertPathToAttachments($fullPath)
    {
        $attachmentsPath = Attachments::getSavePath(AttachmentsManager::STORAGE_PUBLIC, null, true) . DIRECTORY_SEPARATOR;

        return strpos($fullPath, $attachmentsPath) === 0 ? substr($fullPath, strlen($attachmentsPath)) : $fullPath;
    }

    /**
     * Get name encoded
     * @return string
     */
    protected function nameEncoded()
    {
        return static::encodeName($this->width, $this->height, $this->crop);
    }

    /**
     * Validate given dimensions
     * @param int|null $width
     * @param int|null $height
     * @param bool     $crop
     * @return bool
     */
    protected static function validateDimensions($width, $height, $crop = false)
    {
        $wIsNumeric = is_numeric($width);
        $hIsNumeric = is_numeric($height);
        if (! $wIsNumeric && ! $hIsNumeric) {
            return false;
        }

        if ($crop && (!$wIsNumeric || !$hIsNumeric)) {
            return false;
        }

        if (
            ($wIsNumeric && ($width <= 0 || $width > static::MAX_DIMENSION))
            || ($hIsNumeric && ($height <= 0 || $height > static::MAX_DIMENSION))
        ) {
            return false;
        }

        return true;
    }

    /**
     * Get encoded name from width, height and crop data
     *
     * @param  int|null $width
     * @param  int|null $height
     * @param  bool     $crop
     * @return string|null
     */
    public static function encodeName($width = null, $height = null, $crop = false)
    {
        if (! static::validateDimensions($width, $height, $crop)) {
            return null;
        }
        $wStr = $width !== null ? dechex($width) : '';
        $hStr = $height !== null ? dechex($height) : '';
        $cropStr = $crop ? '-c' : '';

        return $wStr . '-' . $hStr . $cropStr;
    }

    /**
     * Create object from encoded preset name
     *
     * @param  string $nameEncoded
     * @return ImagePreset|null
     */
    public static function createFromEncoded($nameEncoded)
    {
        $nameArr = explode('-', $nameEncoded);
        if ($nameEncoded === "" || empty($nameArr)) {
            return null;
        }
        $width = ! empty($nameArr[0]) ? hexdec($nameArr[0]) : null;
        $height = ! empty($nameArr[1]) ? hexdec($nameArr[1]) : null;
        $crop = isset($nameArr[2]) && $nameArr[2] === 'c';
        if (! static::validateDimensions($width, $height, $crop)) {
            return null;
        }

        return new static($width, $height, $crop);
    }
}
