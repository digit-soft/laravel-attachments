<?php

namespace DigitSoft\Attachments\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\ImagePreset;
use DigitSoft\Attachments\Facades\Attachments;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class ImagesController
 * @package DigitSoft\Attachments\Controllers
 */
class ImagesController extends Controller
{
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
        if (! $attachment->isMimeLike('image/*') || ($preset = ImagePreset::createFromEncoded($presetName)) === null) {
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
     *
     * @param  string|null $filePath
     * @return \DigitSoft\Attachments\Attachment
     */
    protected function findAttachmentByFilePath(?string $filePath): Attachment
    {
        if ($filePath === null) {
            throw (new ModelNotFoundException())->setModel(Attachment::class);
        }
        /** @noinspection PhpIncompatibleReturnTypeInspection */
        return Attachment::whereFilePath($filePath)->firstOrFail();
    }
}
