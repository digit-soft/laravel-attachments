<?php

namespace DigitSoft\Attachments\Validation\Rules;

use DigitSoft\Attachments\Attachment;
use Illuminate\Contracts\Validation\Rule;
use DigitSoft\Attachments\Traits\WithAttachmentsManager;

class AttachmentSizeRule implements Rule
{
    use WithAttachmentsManager;
    /**
     * @var float|int
     */
    protected $maxSize;

    /**
     * AttachmentSizeRule constructor.
     *
     * @param int|string $maxSize
     */
    public function __construct($maxSize)
    {
        $this->maxSize = static::attachmentsManager()->fileSizeNormalizeValue($maxSize);
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value) || ($model = Attachment::find($value)) === null) {
            return false;
        }

        return (int)$model->size() <= $this->maxSize;
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
            'max' => static::attachmentsManager()->fileSizeStringifyValue($this->maxSize),
        ]);
    }
}
