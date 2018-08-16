<?php

namespace DigitSoft\Attachments;

use Illuminate\Database\Eloquent\Model;

/**
 * DigitSoft\Attachments\AttachmentUsage
 *
 * @property int    $attachment_id Attachment
 * @property string $model_id Model
 * @property string $model_type Model
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereAttachmentId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereModelId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|AttachmentUsage whereModelType($value)
 * @mixin \Eloquent
 */
class AttachmentUsage extends Model
{
}