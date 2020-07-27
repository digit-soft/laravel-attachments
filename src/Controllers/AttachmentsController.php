<?php

namespace DigitSoft\Attachments\Controllers;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Route;
use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\AttachmentsManager;
use DigitSoft\Attachments\Facades\Attachments;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use DigitSoft\Attachments\Validation\Rules\AttachmentUploadMaxSizeByExt;

/**
 * Class AttachmentsController.
 * Example of controller
 * @package DigitSoft\Attachments\Controllers
 */
class AttachmentsController extends Controller
{
    /**
     * AttachmentController constructor.
     */
    public function __construct()
    {
        $this->middleware('auth:api')
            ->except('downloadPrivate');
    }

    /**
     * Upload one public file
     * @param Request     $request
     * @param string|null $group
     * @param string      $name
     * @return \Illuminate\Http\Response
     * @throws BadRequestHttpException
     */
    public function uploadFile(Request $request, $group = null, $name = 'file')
    {
        $attachment = $this->uploadFileGeneral($request, $group, $name, false);
        if ($attachment === null) {
            throw new BadRequestHttpException("File in request not found");
        }
        return response()->success($attachment->toArray());
    }

    /**
     * Upload one private file
     * @param Request     $request
     * @param string|null $group
     * @param string      $name
     * @return \Illuminate\Http\Response
     * @throws BadRequestHttpException
     */
    public function uploadFilePrivate(Request $request, $group = null, $name = 'file')
    {
        $attachment = $this->uploadFileGeneral($request, $group, $name, true);
        if (!$attachment) {
            throw new BadRequestHttpException("File in request not found");
        }
        return response()->success($attachment->toArray());
    }

    /**
     * Upload multiple public files
     * @param Request     $request
     * @param string|null $group
     * @return \Illuminate\Http\Response
     * @throws BadRequestHttpException
     */
    public function uploadFiles(Request $request, $group = null)
    {
        $data = $this->uploadFilesGeneral($request, $group, false);
        if (empty($data)) {
            throw new BadRequestHttpException("Files in request not found");
        }
        return response()->success($data);
    }

    /**
     * Upload multiple private files
     * @param Request     $request
     * @param string|null $group
     * @return \Illuminate\Http\Response
     * @throws BadRequestHttpException
     */
    public function uploadFilesPrivate(Request $request, $group = null)
    {
        $data = $this->uploadFilesGeneral($request, $group, true);
        if (empty($data)) {
            throw new BadRequestHttpException("Files in request not found");
        }
        return response()->success($data);
    }

    /**
     * Get link to private file
     * @param Request $request
     * @param int     $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function linkPrivate(Request $request, int $id)
    {
        $attachment = Attachment::findOrFail($id);
        $url = Attachments::getUrlPrivate($attachment);
        if (!$url) {
            abort(403);
        }
        return response()->success($url);
    }

    /**
     * Download private file
     * @param  Request $request
     * @param  string  $token
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function downloadPrivate(Request $request, $token)
    {
        $attachment = Attachments::getAttachmentByToken($token);
        if (!$attachment || !$attachment->private) {
            abort(404);
        }
        config()->set('debugbar.enabled', false);
        return $attachment->storage()->download($attachment->path, $attachment->name_original);
    }

    /**
     * Upload file helper
     * @param Request $request
     * @param string  $group
     * @param string  $name
     * @param bool    $private
     * @return Attachment|null
     */
    protected function uploadFileGeneral(Request $request, $group = null, $name = 'file', $private = false)
    {
        $file = $request->file($name);
        if (! $file || ! $this->validateUploadSize($file)) {
            return null;
        }
        $attachment = Attachments::createFromFile($file, $group, $private, auth()->id());
        $success = $attachment->save();
        return $success ? $attachment : null;
    }

    /**
     * Upload multiple files helper
     * @param Request $request
     * @param string  $group
     * @param bool    $private
     * @return Attachment[]
     */
    protected function uploadFilesGeneral(Request $request, $group = null, $private = false)
    {
        $files = $request->allFiles();
        if (empty($files)) {
            return [];
        }
        $filesData = [];
        foreach ($files as $key => $file) {
            if (!$this->validateUploadSize($file)) {
                $filesData[$key] = null;
                continue;
            }
            $attachment = Attachments::createFromFile($file, $group, $private, auth()->id());
            $filesData[$key] = $attachment->save() ? $attachment->toArray() : null;
        }
        return $filesData;
    }

    /**
     * Register controller routes
     */
    public static function registerRoutes()
    {
        $self = '\\' . static::class;
        Route::post('attachments/upload/{group}/{name?}', $self . '@uploadFile')->name(AttachmentsManager::ROUTE_PUBLIC_UPLOAD);
        Route::post('attachments/upload-private/{group}/{name?}', $self . '@uploadFilePrivate')->name(AttachmentsManager::ROUTE_PRIVATE_UPLOAD);
        Route::post('attachments/upload-multiple/{group}', $self . '@uploadFiles')->name(AttachmentsManager::ROUTE_PUBLIC_UPLOAD_MULTIPLE);
        Route::post('attachments/upload-multiple-private/{group}', $self . '@uploadFilesPrivate')->name(AttachmentsManager::ROUTE_PRIVATE_UPLOAD_MULTIPLE);
        Route::get('attachments/obtain/{id}', $self . '@linkPrivate')->name(AttachmentsManager::ROUTE_PRIVATE_OBTAIN);
    }

    /**
     * Validate uploaded file size
     *
     * @param  UploadedFile $file
     * @return bool
     */
    protected function validateUploadSize($file)
    {
        $rule = new AttachmentUploadMaxSizeByExt();

        return $rule->passes('file', $file);
    }
}
