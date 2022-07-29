<?php

namespace DigitSoft\Attachments\Validation\Rules;

use DigitSoft\Attachments\Attachment;

/**
 * Validates passed attachment ID for permitted extensions.
 */
class AttachmentExtension extends AttachmentUploadExtension
{
    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        $id = (int)$value;
        /** @var Attachment|null $model */
        if ($id <= 0 || ($model = Attachment::find($id)) === null) {
            return false;
        }

        $ext = $model->extension();

        return is_string($ext) && in_array(mb_strtolower($ext), $this->extensions, true);
    }
}
