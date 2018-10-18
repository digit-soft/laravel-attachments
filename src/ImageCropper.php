<?php

namespace DigitSoft\Attachments;

use DigitSoft\Attachments\Jobs\ImageCropJob;
use Intervention\Image\Facades\Image;

class ImageCropper
{
    public $x = 0;
    public $y = 0;
    public $width;
    public $height;
    public $filePath;

    /**
     * ImageCropper constructor.
     * @param string $filePath
     * @param int    $x
     * @param int    $y
     * @param int    $width
     * @param int    $height
     */
    public function __construct($filePath, $x, $y, $width, $height)
    {
        $this->filePath = $filePath;
        $this->x = $x;
        $this->y = $y;
        $this->width = $width;
        $this->height = $height;
    }

    /**
     * Crop image now
     */
    public function now()
    {
        $this->execute();
    }

    /**
     * Create queued crop job
     */
    public function enqueue()
    {
        dispatch(new ImageCropJob($this))->delay(10);
    }

    /**
     * Execute cropping
     */
    protected function execute()
    {
        $img = $this->getImage($this->filePath);
        $img->crop($this->width, $this->height, $this->x, $this->y);
        $img->save($this->filePath);
    }

    /**
     * Create Image object
     * @param string $filePath
     * @return \Intervention\Image\Image
     */
    protected function getImage($filePath)
    {
        return Image::make($filePath);
    }
}