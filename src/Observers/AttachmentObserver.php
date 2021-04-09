<?php

namespace DigitSoft\Attachments\Observers;

use DigitSoft\Attachments\Attachment;
use Illuminate\Database\Eloquent\Model;
use DigitSoft\Attachments\AttachmentUsage;
use DigitSoft\Attachments\Traits\HasAttachments;

/**
 * Class AttachmentObserver
 * Observer for model events
 */
class AttachmentObserver
{
    /**
     *  Remove attachment usages when model is saving and attachments updated.
     *
     * @param  HasAttachments|Model $model
     * @return void
     */
    public function saving(Model $model)
    {
        if (! $model->exists) {
            return;
        }
        $oldAttachmentIds = [];
        // Regular attributes
        if (! empty($attachmentFields = $model->getAttachableFields())) {
            $oldAttachmentIds = $this->processNormalAttachableSaving($model, $attachmentFields);
            // Nested ones
        } elseif (! empty($attachmentFields = $model->getCollectableAttachableFields())) {
            $oldAttachmentIds = $this->processCollectedAttachableSaving($model, $attachmentFields);
        }

        // Remove usage from old attachments
        if (! empty($oldAttachmentIds)) {
            $model->attachmentUsages()
                ->whereIn('attachment_id', $oldAttachmentIds)
                ->delete();
        }
    }

    /**
     *  Save attachment when model is saved.
     *
     * @param  HasAttachments|Model $model
     * @return void
     */
    public function saved(Model $model)
    {
        $modelIds = [];
        $pivotAttributes = [];

        // Regular attributes
        if (! empty($attachmentFields = $model->getAttachableFields())) {
            [$modelIds, $pivotAttributes] = $this->processNormalAttachableSaved($model, $attachmentFields);
        } else
        // Nested ones
        if (! empty($attachmentFields = $model->getCollectableAttachableFields())) {
            [$modelIds, $pivotAttributes] = $this->processCollectedAttachableSaved($model, $attachmentFields);
        }

        // Add usage to newly saved attachments
        if (! empty($modelIds)) {
            // Make fake models instead of getting them from a DB
            $models = collect($modelIds)->map(function($id) {
                $mdl = (new Attachment)->forceFill(['id' => $id])->syncOriginal();
                $mdl->exists = true;
                return $mdl;
            })->keyBy('id');
            // Let it be here for the future ^_^
            // $models = Attachment::query()->whereIn('id', $modelIds)->get()->keyBy('id');
            $model->attachments()->saveMany($models, $pivotAttributes);
        }
    }

    /**
     * Remove attachment usage when model was deleted.
     *
     * @param  HasAttachments|Model $model
     * @return void
     */
    public function deleted(Model $model)
    {
        $model->attachmentUsages()->delete();
    }

    /**
     * Process callback for `saving` event and normal attachable.
     *
     * @param  \Illuminate\Database\Eloquent\Model|HasAttachments $model
     * @param  array                               $attributes
     * @return array|int[]
     */
    protected function processNormalAttachableSaving(Model $model, array $attributes)
    {
        $attachmentFields = array_combine($attributes, $attributes);
        $updatedAttachmentFields = array_intersect_key($model->getDirty(), $attachmentFields);
        $oldAttachmentIds = [];
        if (! empty($updatedAttachmentFields)) {
            foreach ($updatedAttachmentFields as $fieldName => $newId) {
                if (($oldId = $model->getOriginal($fieldName)) !== null) {
                    $oldAttachmentIds[] = $oldId;
                }
            }
        }

        return $oldAttachmentIds;
    }

    /**
     * Process callback for `saving` event and collected attachable.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  array                               $attributes
     * @return array|int[]
     */
    protected function processCollectedAttachableSaving(Model $model, array $attributes)
    {
        $oldAttachmentIds = [];

        foreach ($attributes as $attribute) {
            $new = AttachmentUsage::getAttributeValueNested($model, $attribute);
            $old = AttachmentUsage::getAttributeValueNested($model, $attribute, true);
            if ($old !== $new && is_numeric($old)) {
                $oldAttachmentIds[] = $old;
            }
        }

        return $oldAttachmentIds;
    }

    /**
     * Process callback for `saved` event and normal attachable.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  array                               $attributes
     * @param  bool                                $onlyChanged Get only changed attributes
     * @return array[]
     */
    protected function processNormalAttachableSaved(Model $model, array $attributes, bool $onlyChanged = true)
    {
        $modelIds = $pivotAttributes = [];
        foreach ($attributes as $attachableField) {
            if (($modelId = $model->{$attachableField}) !== null && (! $onlyChanged || $modelId !== $model->getOriginal($attachableField))) {
                $modelIds[] = $modelId;
                $pivotAttributes[$modelId] = ['tag' => $attachableField];
            }
        }

        return [$modelIds, $pivotAttributes];
    }

    /**
     * Process callback for `saved` event and collected attachable.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  array                               $attributes
     * @param  bool                                $onlyChanged Get only changed attributes
     * @return array[]
     */
    protected function processCollectedAttachableSaved(Model $model, array $attributes, bool $onlyChanged = true)
    {
        $modelIds = $pivotAttributes = [];
        foreach ($attributes as $attachableField) {
            $modelId = AttachmentUsage::getAttributeValueNested($model, $attachableField);
            if ($modelId !== null && (! $onlyChanged || $modelId !== AttachmentUsage::getAttributeValueNested($model, $attachableField, true))) {
                $modelIds[] = $modelId;
                $pivotAttributes[$modelId] = ['tag' => $attachableField];
            }
        }

        return [$modelIds, $pivotAttributes];
    }
}
