<?php

namespace DigitSoft\Attachments\Traits;

use Illuminate\Foundation\Auth\User;
use DigitSoft\Attachments\Attachment;
use DigitSoft\Attachments\AttachmentUsage;
use DigitSoft\Attachments\Facades\Attachments;
use Illuminate\Database\Eloquent\Relations\Relation;
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
    private static $useMorphMap;

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
            ->withPivot(['tag']);
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
    public function attachmentUse($attachment, $tag = AttachmentUsage::TAG_DEFAULT)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $attachment = $attachment instanceof Attachment ? $attachment : Attachment::find($attachment);
        $id = $this->getUsageModelId();
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
    public function attachmentForgetUsage($attachment)
    {
        /** @noinspection CallableParameterUseCaseInTypeContextInspection */
        $attachment = $attachment instanceof Attachment ? $attachment : Attachment::find($attachment);
        $id = $this->getUsageModelId();
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
    public function attachmentCanDownload(User $user, Attachment $attachment)
    {
        return false;
    }

    /**
     * Restore attachments (IDs) in model.
     */
    public function restoreAttachmentsInModelByTags()
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
    private function getUsageModelType()
    {
        $useMorphMap = static::$useMorphMap ?? config('attachments.use_morph_map', false);
        return $useMorphMap ? $this->getUsageMorphAliasForClass(get_called_class()) : get_called_class();
    }

    /**
     * Get alias for class using morphs.
     *
     * @param  string $className
     * @return string
     */
    private function getUsageMorphAliasForClass(string $className)
    {
        $alias = array_search($className, Relation::$morphMap, true);

        return $alias !== false ? $alias : $className;
    }

    /**
     * Get ID for usage.
     *
     * @return string|int
     */
    private function getUsageModelId()
    {
        return $this->getKey();
    }
}
