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
     *  Remove attachment usages when model is saving and attachments updated.
     *
     * @param HasAttachments|Model $model
     * @return void
     */
    public function saving(Model $model)
    {
        if ($model->exists) {
            $attachmentFields = array_combine($model->getAttachableFields(), $model->getAttachableFields());
            $updatedAttachmentFields = array_intersect_key($model->getDirty(), $attachmentFields);
            $oldAttachmentIds = [];
            if (!empty($updatedAttachmentFields)) {
                foreach ($updatedAttachmentFields as $fieldName => $newId) {
                    if (($oldId = $model->getOriginal($fieldName)) !== null) {
                        $oldAttachmentIds[] = $oldId;
                    }
                }
            }
            if (!empty($oldAttachmentIds)) {
                $model->attachmentUsages()
                    ->whereIn('attachment_id', $oldAttachmentIds)
                    ->delete();
            }
        }
    }

    /**
     *  Save attachment when model is saved.
     *
     * @param HasAttachments|Model $model
     * @return void
     */
    public function saved(Model $model)
    {
        $modelIds = [];
        foreach ($model->getAttachableFields() as $attachableField) {
            if (Schema::hasColumn($model->getTable(), $attachableField) && $model->{$attachableField} !== null) {
                $modelIds[] = $model->{$attachableField};
            }
        }

        if (!empty($modelIds)) {
            $models = Attachment::query()->whereIn('id', $modelIds)->get();
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
