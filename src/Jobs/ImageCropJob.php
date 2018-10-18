<?php

namespace DigitSoft\Attachments\Jobs;

use DigitSoft\Attachments\ImageCropper;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Class ImageCropJob
 * @package DigitSoft\Attachments\Jobs
 */
class ImageCropJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ImageCropper
     */
    protected $cropper;

    /**
     * ImageCropJob constructor.
     * @param ImageCropper $cropper
     */
    public function __construct(ImageCropper $cropper)
    {
        $this->cropper = $cropper;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        /** @var Filesystem $files */
        $files = app('files');
        if ($files->exists($this->cropper->filePath)) {
            $this->cropper->now();
        }
    }

}