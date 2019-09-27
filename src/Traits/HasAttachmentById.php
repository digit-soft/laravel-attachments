<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;

/**
 * Trait HasAttachmentById.
 * Use this trait if you have attachment ID field in your model.
 *
 * @property      string     $attachment_id_attribute Attachment ID db attribute (use it if you want to override attribute with attachment ID)
 * @property-read Attachment $attachment
 */
trait HasAttachmentById
{
    /**
     * Get used attachment (one) by ID field
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function attachment()
    {
        $idAttribute = $this->attachment_id_attribute ?? 'attachment_id';

        return $this->belongsTo(Attachment::class, $idAttribute, 'id');
    }
}
