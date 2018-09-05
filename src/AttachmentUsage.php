<?php

namespace DigitSoft\Attachments;

use Illuminate\Database\Eloquent\Model;

/**
 * DigitSoft\Attachments\AttachmentUsage
 *
 * @property int             $id ID
 * @property int             $attachment_id Attachment
 * @property string          $model_id Model ID
 * @property string          $model_type Model type
 * @property-read Attachment $attachment
 * @property-read Model      $model
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereModelType($value)
 * @mixin \Eloquent
 */
class AttachmentUsage extends Model
{
    public $timestamps = false;

    protected $fillable = ['attachment_id', 'model_id', 'model_type'];

    /**
     * Get attachment
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attachment()
    {
        return $this->belongsTo(Attachment::class, 'attachment_id', 'id');
    }

    /**
     * Get model using attachment
     * @return \Illuminate\Database\Eloquent\Relations\MorphTo
     */
    public function model()
    {
        return $this->morphTo('model');
    }
}