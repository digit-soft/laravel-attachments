<?php

namespace DigitSoft\Attachments\Controllers;

use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\Facades\Attachments;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Http\Request;

/**
 * Class AttachmentsController.
 * Example of controller
 * @package DigitSoft\Attachments\Controllers
 */
class AttachmentsController
{
    /**
     * @param Request $request
     * @param string  $group
     * @param string  $name
     * @return \Illuminate\Http\JsonResponse
     * @throws FileNotFoundException
     */
    public function uploadFile(Request $request, $group, $name = 'file')
    {
        $attachment = $this->uploadFileGeneral($request, $group, $name, false);
        if (!$attachment) {
            throw new FileNotFoundException();
        }
        return response()->json($attachment->toArray());
    }

    /**
     * @param Request $request
     * @param string  $group
     * @param string  $name
     * @return \Illuminate\Http\JsonResponse
     * @throws FileNotFoundException
     */
    public function uploadFilePrivate(Request $request, $group, $name = 'file')
    {
        $attachment = $this->uploadFileGeneral($request, $group, $name, true);
        if (!$attachment) {
            throw new FileNotFoundException();
        }
        return response()->json($attachment->toArray());
    }

    /**
     * @param Request $request
     * @param string  $group
     * @return \Illuminate\Http\JsonResponse
     * @throws FileNotFoundException
     */
    public function uploadFiles(Request $request, $group = 'default')
    {
        $data = $this->uploadFilesGeneral($request, $group, false);
        if (empty($data)) {
            throw new FileNotFoundException();
        }
        return response()->json($data);
    }

    /**
     * @param Request $request
     * @param string  $group
     * @return \Illuminate\Http\JsonResponse
     * @throws FileNotFoundException
     */
    public function uploadFilesPrivate(Request $request, $group = 'default')
    {
        $data = $this->uploadFilesGeneral($request, $group, false);
        if (empty($data)) {
            throw new FileNotFoundException();
        }
        return response()->json($data);
    }

    /**
     * @param Request $request
     * @param int     $id
     */
    public function downloadFile(Request $request, $id)
    {
        $attachment = Attachment::findOrFail($id);
        $attachment->storage()->download($attachment->path(), $attachment->name_original);
    }

    /**
     * @param Request $request
     * @param int     $id
     * @return string
     */
    public function urlFile(Request $request, $id)
    {
        $attachment = Attachment::findOrFail($id);
        return Attachments::getUrl($attachment);
    }

    /**
     * @param Request $request
     * @param string  $group
     * @param string  $name
     * @param bool    $private
     * @return Attachment|null
     */
    protected function uploadFileGeneral(Request $request, $group, $name = 'file', $private = false)
    {
        $file = $request->file($name);
        if (!$file) {
            return null;
        }
        $attachment = Attachments::createFromFile($file, $group, $private);
        $success = $attachment->save();
        return $success ? $attachment : null;
    }

    /**
     * @param Request $request
     * @param string  $group
     * @param bool    $private
     * @return Attachment[]
     */
    protected function uploadFilesGeneral(Request $request, $group, $private = false)
    {
        $files = $request->allFiles();
        if (empty($files)) {
            return [];
        }
        $filesData = [];
        foreach ($files as $key => $file) {
            $attachment = Attachments::createFromFile($file, $group, $private);
            $filesData[$key] = $attachment->save() ? $attachment->toArray() : null;
        }
        return $filesData;
    }
}