<?php

namespace DigitSoft\Attachments;

use Illuminate\Http\File;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Collection;
use DigitSoft\Attachments\Facades\Attachments;
use DigitSoft\Attachments\Traits\WithImageConversion;

/**
 * DigitSoft\Attachments\Attachment
 *
 * @property int                               $id            ID
 * @property int|null                          $user_id       Author id
 * @property string                            $name          File base name
 * @property string                            $name_original File base name original
 * @property string|null                       $group         File group and save path
 * @property bool                              $private       Private flag
 * @property \Carbon\Carbon|null               $created_at    File upload time
 * @property string                            $path          File relative path
 * @property string                            $pathFull      File full path
 * @property string                            $url           File URL
 * @property string                            $urlRelative   File relative URL
 *
 * @property-read int|null                     $imageWidth    Image width
 * @property-read int|null                     $imageHeight   Image height
 * @property-read AttachmentUsage[]|Collection $usages        Attachment usages
 * @property-read Model[]|Collection           $models        Models using this attachment
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereNameOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment wherePrivate($value)
 * @mixin \Eloquent
 */
class Attachment extends Model
{
    const UPDATED_AT = null;

    use WithImageConversion;

    protected $fillable = ['user_id', 'name', 'name_original', 'group', 'private', 'created_at'];

    protected $appends = ['url', 'urlRelative'];

    protected $hidden = ['created_at'];

    protected ?array $_imageDimensions = null;

    public static function boot()
    {
        parent::boot();
        // Delete file from storage
        static::deleting(function ($attachment) {
            /** @var static $attachment */
            $storage = $attachment->private ? Attachments::getStoragePrivate() : Attachments::getStoragePublic();
            $path = $attachment->path;
            if ($storage->exists($path)) {
                $storage->delete($path);
            }
        });
    }

    /**
     * @var File|null
     */
    protected $_file;

    /**
     * Get path.
     *
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->path(false);
    }

    /**
     * Get full path.
     *
     * @return string
     */
    public function getPathFullAttribute()
    {
        return $this->path(true);
    }

    /**
     * Get URL (absolute).
     *
     * @return string|null
     */
    public function getUrlAttribute()
    {
        return Attachments::getUrl($this, true);
    }

    /**
     * Get URL (relative).
     *
     * @return string|null
     */
    public function getUrlRelativeAttribute()
    {
        return Attachments::getUrl($this, false);
    }

    /**
     * Get file mime type
     *
     * @return string|null
     */
    public function mime(): ?string
    {
        $file = $this->file();

        return $file ? $file->getMimeType() : null;
    }

    /**
     * Check that mime type follows given pattern.
     *
     * @param  string $expression
     * @return bool
     */
    public function isMimeLike(string $expression = '*'): bool
    {
        $mime = $this->mime();

        return $mime !== null && Str::is($expression, $mime);
    }

    /**
     * Get file size.
     *
     * @return int|null
     */
    public function size(): ?int
    {
        $file = $this->file();

        return $file ? $file->getSize() : null;
    }

    /**
     * Get readable file size.
     *
     * @param  int $precision
     * @return string|null
     */
    public function sizeHuman(int $precision = 2): ?string
    {
        if (($size = $this->size()) === null) {
            return null;
        }

        return Attachments::fileSizeStringifyValue($size, $precision);
    }

    /**
     * Get file extension.
     *
     * @return string|null
     */
    public function extension(): ?string
    {
        $file = $this->file();

        return $file ? $file->getExtension() : null;
    }

    /**
     * Get file object.
     *
     * @param  bool $flush
     * @return \Illuminate\Http\File|null
     */
    public function file(bool $flush = false): ?File
    {
        if (($flush || $this->_file === null) && $this->name !== null) {
            $this->_file = new File($this->path(true));
        }

        return $this->_file;
    }

    /**
     * Get image width.
     *
     * @return int|null
     */
    public function getImageWidthAttribute()
    {
        [$width,] = $this->getImageDimensions();

        return $width;
    }

    /**
     * Get image height.
     *
     * @return int|null
     */
    public function getImageHeightAttribute()
    {
        [, $height] = $this->getImageDimensions();

        return $height;
    }

    /**
     * Get image dimensions array.
     *
     * @return array
     */
    protected function getImageDimensions(): array
    {
        if ($this->_imageDimensions === null) {
            $data = $this->isMimeLike('image/*') ? getimagesize($this->pathFull) : false;
            $this->_imageDimensions = ! empty($data) ? [$data[0], $data[1]] : [null, null];
        }

        return $this->_imageDimensions;
    }

    /**
     * Reset cached image dimensions.
     *
     * @return $this
     */
    public function resetImageDimensions(): static
    {
        $this->_imageDimensions = null;

        return $this;
    }

    /**
     * Get file path.
     *
     * @param  bool $full
     * @return string
     */
    public function path(bool $full = false): string
    {
        if (($filename = $this->name) === null) {
            return '';
        }
        $storageType = $this->private ? AttachmentsManager::STORAGE_PRIVATE : AttachmentsManager::STORAGE_PUBLIC;
        $dirPath = Attachments::getSavePath($storageType, $this->group, $full);

        return $dirPath . DIRECTORY_SEPARATOR . $filename;
    }

    /**
     * Get file store.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    public function storage()
    {
        return $this->private ? Attachments::getStoragePrivate() : Attachments::getStoragePublic();
    }

    /**
     * Get usages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usages()
    {
        return $this->hasMany(AttachmentUsage::class, 'attachment_id', 'id');
    }

    /**
     * Get models using this attachment
     *
     * @return \Illuminate\Support\Collection
     */
    public function getModelsAttribute(): \Illuminate\Support\Collection
    {
        $usages = $this->usages()->with(['model'])->get();

        return $usages->pluck('model')->filter(fn ($m) => $m !== null)->values();
    }

    /**
     * Find attachment by file path (relative to storage app)
     *
     * @param  string $filePath
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function whereFilePath(string $filePath)
    {
        $filePathArr = explode(DIRECTORY_SEPARATOR, $filePath);
        $fileName = array_pop($filePathArr);
        $fileGroup = ! empty($filePathArr) ? implode(DIRECTORY_SEPARATOR, $filePathArr) : null;

        return static::query()
            ->where('name', '=', $fileName)
            ->where('group', '=', $fileGroup);
    }
}
