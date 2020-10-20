<?php

namespace DigitSoft\Attachments;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * DigitSoft\Attachments\AttachmentUsage
 *
 * @property int                                      $id            ID
 * @property int                                      $attachment_id Attachment
 * @property string                                   $model_id      Model ID
 * @property string                                   $model_type    Model type
 * @property string                                   $tag           Tag name
 * @property-read \Illuminate\Database\Eloquent\Model $model         Model instance
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage query()
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereModelType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereTag($value)
 * @mixin \Eloquent
 */
class AttachmentUsage extends MorphPivot
{
    const TAG_DEFAULT = 'default';

    protected $table = 'attachment_usages';

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = ['attachment_id', 'model_id', 'model_type', 'tag'];

    /**
     * Get model morphed to
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo();
    }

    /**
     * @param  Model|\DigitSoft\Attachments\Traits\HasAttachments      $model
     * @param        $attribute
     * @param  false $original
     * @return array|\ArrayAccess|mixed|null
     */
    public static function getAttributeValueNested($model, $attribute, $original = false)
    {
        $keys = explode('.', $attribute);
        $attributeFirst = array_shift($keys);
        // Additional casting for original attribute
        if ($original) {
            $attributeValue = $model->getOriginal($attributeFirst);
            // Laravel 7+ already casts original attributes
            if (version_compare(app()->version(), 7, '<')) {
                $attributeValue = $attributeValue !== null ? $model->castAttribute($attributeFirst, $attributeValue) : null;
            }
        } else {
            $attributeValue = $model->{$attributeFirst};
        }
        // Not a nested one
        if (empty($keys)) {
            return $attributeValue;
        }

        return Arr::accessible($attributeValue) ? Arr::get($attributeValue, implode('.', $keys)) : null;
    }

    public static function setAttributeValueNested($model, $attribute, $value)
    {
        $keys = explode('.', $attribute);
        $attributeFirst = array_shift($keys);
        $attributeValue = $model->{$attributeFirst};
        if (empty($keys) || ! is_array($attributeValue)) {
            $model->{$attribute} = $value;
            return;
        }

        Arr::set($attributeValue, implode('.', $keys), $value);
        $model->{$attributeFirst} = $attributeValue;
    }
}
