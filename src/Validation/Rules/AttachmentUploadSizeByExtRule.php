<?php

namespace DigitSoft\Attachments\Validation\Rules;

use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Validation\Rule;
use DigitSoft\Attachments\Traits\WithAttachmentsManager;

class AttachmentUploadSizeByExtRule implements Rule
{
    use WithAttachmentsManager;

    /**
     * Upload file size limit.
     * @var int
     */
    protected $limit = 0;

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (! $value instanceof UploadedFile) {
            return false;
        }
        $ext = mb_strtolower($value->getExtension());
        $this->limit = static::attachmentsManager()->fileSizeGetLimitByExt($ext);

        return $value->getSize() <= $this->limit;
    }

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        // Add translation to message into your `resources/lang/{language}/validation.php`
        return trans('validation.attachment.size-to-big', [
            'max' => static::attachmentsManager()->fileSizeStringifyValue($this->limit),
        ]);
    }
}
