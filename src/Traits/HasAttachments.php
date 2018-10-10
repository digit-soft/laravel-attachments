<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\AttachmentUsage;
use DigitSoft\Attachments\Contracts\ModelAttacher;
use DigitSoft\Attachments\Facades\Attachments;

/**
 * Trait HasAttachments
 * @package DigitSoft\Attachments\Traits
 * @property string       $primaryKey
 * @property Attachment[] $attachments
 */
trait HasAttachments
{


    /**
     * Get attachment usages
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function attachmentUsages()
    {
        return $this->morphMany(AttachmentUsage::class, 'model');
    }

    /**
     * Add usage to attachment
     * @param Attachment|int $attachment
     */
    public function attachmentUse($attachment)
    {
        $attachment = $attachment instanceof Attachment ? $attachment : Attachment::find($attachment);
        $id = $this->getUsageModelId();
        $type = $this->getUsageModelType();
        if ($id === null || $attachment === null) {
            return;
        }
        Attachments::addUsage($attachment, $id, $type);
    }

}