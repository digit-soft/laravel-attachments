<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\Facades\Attachments;
use Illuminate\Support\Str;

/**
 * Trait HasAttachments
 * @package DigitSoft\Attachments\Traits
 * @property string       $primaryKey
 * @property Attachment[] $attachments
 */
trait HasAttachments
{
    /**
     * Custom model type name (must be registered for morphing)
     * @var string|null
     */
    protected $attachments_model_type;

    /**
     * Get used attachments
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function attachments()
    {
        return $this->morphMany(Attachment::class, 'attachment_usages', 'model_type', 'model_id');
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

    /**
     * Get group for usage
     * @return string
     */
    private function getUsageModelType()
    {
        if ($this->attachments_model_type !== null) {
            return $this->attachments_model_type;
        }
        $classNameArray = explode('\\', get_called_class());
        return Str::snake(array_pop($classNameArray));
    }

    /**
     * Get ID for usage
     * @return string|int
     */
    private function getUsageModelId()
    {
        $pk = $this->primaryKey;
        return $this->{$pk};
    }
}