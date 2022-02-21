<?php

namespace DigitSoft\Attachments;

use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphPivot;

/**
 * DigitSoft\Attachments\AttachmentUsage
 *
 * @property int                                           $id            ID
 * @property int                                           $attachment_id Attachment
 * @property string                                        $model_id      Model ID
 * @property string                                        $model_type    Model type
 * @property string                                        $tag           Tag name
 * @property-read \Illuminate\Database\Eloquent\Model|null $model         Model instance
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

    public $timestamps = false;

    protected $table = 'attachment_usages';

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
     * Get the value of nested attribute.
     *
     * @param  Model|\DigitSoft\Attachments\Traits\HasAttachments $model
     * @param  string                                             $attribute
     * @param  bool                                               $useOriginal
     * @return mixed
     */
    public static function getAttributeValueNested(Model $model, string $attribute, bool $useOriginal = false)
    {
        $keys = explode('.', $attribute);
        $attributeFirst = array_shift($keys);
        // Additional casting for original attribute
        if ($useOriginal) {
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

        return Arr::accessible($attributeValue) ? static::getWithDottedKeys($attributeValue, implode('.', $keys)) : null;
    }

    /**
     * Set value for nested attribute.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  string                              $attribute
     * @param  mixed                               $value
     * @return void
     */
    public static function setAttributeValueNested(Model $model, string $attribute, $value)
    {
        $keys = explode('.', $attribute);
        $attributeFirst = array_shift($keys);
        $attributeValue = $model->{$attributeFirst};
        if (empty($keys) || ! is_array($attributeValue)) {
            $model->{$attribute} = $value;
            return;
        }

        // TODO: Doesn't work good with dotted keys
        Arr::set($attributeValue, implode('.', $keys), $value);
        $model->{$attributeFirst} = $attributeValue;
    }

    /**
     * Get an item from an array using "dot" notation.
     *
     * Works with last key in "dotted" syntax.
     *
     * @param  array|\Illuminate\Contracts\Support\Arrayable|mixed $array
     * @param  string|null                                         $key
     * @param  mixed                                               $default
     * @return mixed
     */
    private static function getWithDottedKeys($array, ?string $key, $default = null)
    {
        if (! Arr::accessible($array)) {
            return value($default);
        }

        if (is_null($key)) {
            return $array;
        }

        if (Arr::exists($array, $key)) {
            return $array[$key];
        }

        if (strpos($key, '.') === false) {
            return $array[$key] ?? value($default);
        }

        $keysExploded = explode('.', $key);
        while (! empty($keysExploded)) {
            $segment = array_shift($keysExploded);
            if (Arr::accessible($array) && Arr::exists($array, $segment)) {
                $array = $array[$segment];
            } elseif(
                ! empty($keysExploded)
                && ($segmentLong = implode('.', array_merge([$segment], $keysExploded)))
                && Arr::exists($array, $segmentLong)
            ) {
                $array = $array[$segmentLong];
                break;
            } else {
                return value($default);
            }
        }

        return $array;
    }
}
