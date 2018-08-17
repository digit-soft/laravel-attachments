<?php

namespace DigitSoft\Attachments\Controllers;

use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\Facades\Attachments;
use DigitSoft\Attachments\ImagePreset;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ImagesController
 * @package DigitSoft\Attachments\Controllers
 */
class ImagesController extends Controller
{
    const MAX_DIMENSION = 3000;

    /**
     * Perform image transformation
     * @param Request $request
     * @param string  $presetName
     * @param string  $fileName
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     * @throws NotFoundHttpException
     */
    public function imagePreset(Request $request, $presetName, $fileName)
    {
        $attachment = $this->findAttachmentByFilePath($fileName);
        $preset = ImagePreset::createFromEncoded($presetName);
        if ($preset === null) {
            throw new NotFoundHttpException();
        }
        $srcPath = $attachment->path(true);
        $preset->executeForFile($srcPath);
        $resizedPath = $preset->dstPath($srcPath);
        $storage = Attachments::getStoragePublic();
        if (!$storage->exists($resizedPath)) {
            throw new NotFoundHttpException();
        }
        return $storage->response($resizedPath);
    }

    /**
     * Find attachment by relative file path
     * @param string $filePath
     * @return Attachment
     */
    protected function findAttachmentByFilePath($filePath)
    {
        return Attachment::whereFilePath($filePath)->firstOrFail();
    }
}