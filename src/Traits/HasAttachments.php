<?php

namespace DigitSoft\Attachments\Traits;

use Illuminate\Foundation\Auth\User;
use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\AttachmentUsage;
use DigitSoft\Attachments\Facades\Attachments;
use DigitSoft\Attachments\Observers\AttachmentObserver;

/**
 * Trait HasAttachments
 *
 * @property string                                                     $primaryKey
 * @property Attachment[]|\Illuminate\Database\Eloquent\Collection      $attachments
 * @property AttachmentUsage[]|\Illuminate\Database\Eloquent\Collection $attachmentUsages
 */
trait HasAttachments
{
    /**
     * Get fields related to attachments.
     *
     * @return array
     */
    abstract public function getAttachableFields();

    /**
     * Get fields related to attachments, which should be collected, not really model attributes.
     *
     * For example nested ones: ['blocks.test.attachment_id']
     *
     * @return array
     */
    public function getCollectableAttachableFields()
    {
        return [];
    }

    /**
     *  Register Model observer.
     *
     * @return void
     */
    public static function bootHasAttachments()
    {
        static::observe(new AttachmentObserver());
    }

    /**
     * Get used attachments.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function attachments()
    {
        return $this->morphToMany(Attachment::class, 'model', (new AttachmentUsage)->getTable())
            ->using(AttachmentUsage::class)
            ->withPivot(['tag'])
            ->orderBy((new Attachment)->qualifyColumn('id'));
    }

    /**
     * Get attachment usages.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function attachmentUsages()
    {
        return $this->morphMany(AttachmentUsage::class, 'model');
    }

    /**
     * Add usage to attachment.
     *
     * @param  Attachment|int $attachment
     * @param  string         $tag
     */
    public function attachmentUse($attachment, $tag = AttachmentUsage::TAG_DEFAULT): void
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $attachment = $attachment instanceof Attachment ? $attachment : Attachment::find($attachment);
        $id = $this->getKey();
        $type = $this->getUsageModelType();
        // Add usage if all data is present
        if ($attachment !== null && $id !== null) {
            Attachments::addUsage($attachment, $id, $type, $tag);
        }
    }

    /**
     * Remove attachment usage from this model.
     *
     * @param Attachment|int $attachment
     */
    public function attachmentForgetUsage($attachment): void
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $attachment = $attachment instanceof Attachment ? $attachment : Attachment::find($attachment);
        $id = $this->getKey();
        $type = $this->getUsageModelType();
        // Remove usage if all data is present
        if ($attachment !== null && $id !== null) {
            Attachments::removeUsage($attachment, $id, $type);
        }
    }

    /**
     * Fallback private attachment download check if model has no Policy.
     *
     * @param  User       $user
     * @param  Attachment $attachment
     * @return bool
     */
    public function attachmentCanDownload(User $user, Attachment $attachment): bool
    {
        return false;
    }

    /**
     * Restore attachments (IDs) in model.
     */
    public function restoreAttachmentsInModelByTags(): void
    {
        $attachments = $this->attachments;
        foreach ($attachments as $attachment) {
            /** @var \DigitSoft\Attachments\AttachmentUsage|null $usage */
            if (($usage = $attachment->pivot) === null || ($tagName = $usage->tag) === AttachmentUsage::TAG_DEFAULT) {
                continue;
            }

            AttachmentUsage::setAttributeValueNested($this, $tagName, $attachment->getKey());
        }
    }

    /**
     * Get group for usage.
     *
     * @return string
     */
    private function getUsageModelType(): string
    {
        $useMorphMap = config('attachments.use_morph_map', false);

        return $useMorphMap ? $this->getMorphClass() : get_called_class();
    }
}
