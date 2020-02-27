<?php

namespace DigitSoft\Attachments\Traits;

use Intervention\Image\Facades\Image;

trait WithImageConversion
{
    /**
     * Convert existing image to some other format.
     *
     * @param  string $format
     * @param  int    $quality
     * @return bool
     */
    public function convertAsImage(string $format, $quality = 75)
    {
        if (! $this->isMimeLike('image/*')) {
            return false;
        }
        $ext = $this->extension();
        $oldPath = $this->path(false);
        $oldPathFull = $this->path(true);
        $this->name = $ext !== null
            ? mb_substr($this->name, 0, -(mb_strlen($ext))) . $format
            : $this->name . '.' . $format;
        $newPath = $this->path(true);
        $newPathFull = $this->path(true);

        Image::make($oldPathFull)->encode($format, $quality)->save($newPathFull);

        /** @var \Illuminate\Contracts\Filesystem\Filesystem|\Illuminate\Filesystem\FilesystemAdapter $storage */
        $storage = $this->storage();

        if (! $this->save()) {
            $storage->delete($newPath);

            return false;
        }

        $storage->delete($oldPath);

        return true;
    }
}
