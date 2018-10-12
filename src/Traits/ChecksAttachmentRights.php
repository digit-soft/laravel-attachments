<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User;

trait ChecksAttachmentRights
{
    /**
     * Check that user can download attachment, which is used by selected model
     * @param User       $user
     * @param Model      $model
     * @param Attachment $attachment
     * @return bool
     */
    abstract public function privateDownload(User $user, Model $model, Attachment $attachment);
}