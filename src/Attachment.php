<?php

namespace DigitSoft\Attachments;

use DigitSoft\Attachments\Facades\Attachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\File;

/**
 * DigitSoft\Attachments\Attachment
 *
 * @mixin \Eloquent
 * @property int                 $id ID
 * @property int|null            $user_id Author id
 * @property string              $name File base name
 * @property string              $name_original File base name original
 * @property string|null         $group File group and save path
 * @property bool                $private Private flag
 * @property \Carbon\Carbon|null $created_at File upload time
 * @property string              $path File relative path
 * @property string              $pathFull File full path
 * @property string              $url File URL
 *
 * @property AttachmentUsage[]   $usages Attachment usages
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereNameOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment wherePrivate($value)
 */
class Attachment extends Model
{
    const UPDATED_AT = null;

    protected $fillable = ['user_id', 'name', 'name_original', 'group', 'private', 'created_at'];

    protected $appends = ['url', 'urlRelative'];

    protected $hidden = ['id', 'created_at'];

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
     * Get path
     * @return string
     */
    public function getPathAttribute()
    {
        return $this->path(false);
    }

    /**
     * Get full path
     * @return string
     */
    public function getPathFullAttribute()
    {
        return $this->path(true);
    }

    /**
     * Get URL
     * @return null|string
     */
    public function getUrlAttribute()
    {
        return Attachments::getUrl($this);
    }

    /**
     * Get URL
     * @return null|string
     */
    public function getUrlRelativeAttribute()
    {
        return Attachments::getUrl($this, false);
    }

    /**
     * Get file mime type
     * @return null|string
     */
    public function mime()
    {
        $file = $this->file();
        return $file ? $file->getMimeType() : null;
    }

    /**
     * Get file size
     * @return int|null
     */
    public function size()
    {
        $file = $this->file();
        return $file ? $file->getSize() : null;
    }

    /**
     * Get file object
     * @param bool $flush
     * @return File|null
     */
    public function file($flush = false)
    {
        if (($flush || $this->_file === null) && $this->name !== null) {
            $this->_file = new File($this->path(true));
        }
        return $this->_file;
    }

    /**
     * Get file path
     * @param bool $full
     * @return string
     */
    public function path($full = false)
    {
        $storageType = $this->private ? AttachmentsManager::STORAGE_PRIVATE : AttachmentsManager::STORAGE_PUBLIC;
        $dirPath = Attachments::getSavePath($storageType, $this->group, $full);
        return $dirPath . DIRECTORY_SEPARATOR . $this->name;
    }

    /**
     * Get file store
     * @return \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter
     */
    public function storage()
    {
        return $this->private ? Attachments::getStoragePrivate() : Attachments::getStoragePublic();
    }

    /**
     * Get usages
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function usages()
    {
        return $this->hasMany(AttachmentUsage::class, 'attachment_id', 'id');
    }

    /**
     * Find attachment by file path (relative to storage app)
     * @param string $filePath
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function whereFilePath($filePath)
    {
        $filePathArr = explode(DIRECTORY_SEPARATOR, $filePath);
        $fileName = array_pop($filePathArr);
        $fileGroup = !empty($filePathArr) ? implode(DIRECTORY_SEPARATOR, $filePathArr) : null;
        return static::query()
            ->where('name', '=', $fileName)
            ->where('group', '=', $fileGroup);
    }
}