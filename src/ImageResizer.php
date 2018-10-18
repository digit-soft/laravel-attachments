<?php

namespace DigitSoft\Attachments;

use DigitSoft\Attachments\Jobs\ImageResizeJob;

class ImageResizer
{
    /**
     * @var string
     */
    public $filePath;
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
     * @var ImagePreset
     */
    protected $_preset;

    /**
     * ImagePreset constructor.
     * @param string $filePath
     * @param int    $width
     * @param int    $height
     * @param bool   $crop
     */
    public function __construct($filePath, $width, $height, $crop = false)
    {
        $this->filePath = $filePath;
        $this->width = $width;
        $this->height = $height;
        $this->crop = $crop;
    }

    /**
     * Resize image now
     * @return bool
     */
    public function now()
    {
        return $this->execute();
    }

    /**
     * Create queued resize job
     */
    public function enqueue()
    {
        dispatch(new ImageResizeJob($this));
    }

    /**
     * Execute resizing
     * @return bool
     */
    protected function execute()
    {
        $preset = $this->getPreset();
        return $preset->executeForFile($this->filePath, true);
    }

    /**
     * Get image preset
     * @return ImagePreset
     */
    protected function getPreset()
    {
        if ($this->_preset === null) {
            $this->_preset = new ImagePreset($this->width, $this->height, $this->crop);
        }
        return $this->_preset;
    }
}