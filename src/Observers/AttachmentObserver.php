<?php

namespace DigitSoft\Attachments\Observers;

use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\Traits\HasAttachments;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

/**
 * Class AttachmentObserver
 * Observer for model events
 * @package DigitSoft\Attachments\Observers
 */
class AttachmentObserver
{
    /**
     *  Save attachment when model is saved.
     *
     * @param HasAttachments|Model $model
     * @return void
     */
    public function saved(Model $model)
    {
        $models = [];
        foreach ($model->getAttachableFields() as $attachableField) {

            if (Schema::hasColumn($model->getTable(), $attachableField)) {

                if ($attachment = Attachment::find($model->{$attachableField})) {

                    $models[] = $attachment;

                }
            }
        }

        if (!empty($models)) {
            $model->attachments()->saveMany($models);
        }
    }

    /**
     *  Delete attachment when model is deleted.
     *
     * @param HasAttachments|Model $model
     * @return void
     */
    public function deleted(Model $model)
    {
        $model->attachmentUsages()->delete();
    }
}
