<?php

namespace DigitSoft\Attachments\Jobs;

use DigitSoft\Attachments\ImageResizer;
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
class ImageResizeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var ImageResizer
     */
    protected $resizer;

    /**
     * ImageCropJob constructor.
     * @param ImageResizer $resizer
     */
    public function __construct(ImageResizer $resizer)
    {
        $this->resizer = $resizer;
    }

    /**
     * Execute the job.
     *
     * @return void
     * @throws \Exception
     */
    public function handle()
    {
        /** @var Filesystem $files */
        $files = app('files');
        if ($files->exists($this->resizer->filePath)) {
            if (!$this->resizer->now()) {
                throw new \Exception(strtr("Can't resize image {path}", ['{path}' => $this->resizer->filePath]));
            }
        } else {
            throw new \Exception(strtr("File {path} not exists", ['{path}' => $this->resizer->filePath]));
        }
    }

}
