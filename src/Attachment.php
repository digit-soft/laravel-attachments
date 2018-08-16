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
 * @property string              $name File base name
 * @property string              $name_original File base name original
 * @property string|null         $group File group and save path
 * @property bool                $private Private flag
 * @property \Carbon\Carbon|null $created_at File upload time
 *
 * @property AttachmentUsage[]   $usages Attachment usages
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereGroup($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment whereNameOriginal($value)
 * @method static \Illuminate\Database\Eloquent\Builder|Attachment wherePrivate($value)
 */
class Attachment extends Model
{
    protected $fillable = ['id', 'name', 'name_original', 'group', 'private', 'created_at'];

    /**
     * @var File|null
     */
    protected $_file;

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
}