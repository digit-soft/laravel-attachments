<?php

namespace DigitSoft\Attachments\Observers;

use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\Contracts\ModelAttacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class AttachmentObserver
{


    /**
     *  Save attachment when model is saved.
     *
     * @param ModelAttacher $model
     * @return void
     */
    public function saved(ModelAttacher $model)
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
     * @param ModelAttacher $model
     * @return void
     */
    public function deleted(ModelAttacher $model)
    {
        $model->attachmentUsages()->delete();
    }
}