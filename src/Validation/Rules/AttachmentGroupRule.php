<?php

namespace DigitSoft\Attachments\Validation\Rules;

use DigitSoft\Attachments\Attachment;
use Illuminate\Contracts\Validation\Rule;

class AttachmentGroupRule implements Rule
{
    protected $groups;

    /**
     * AttachmentGroupRule constructor.
     *
     * @param string[]|string $groups
     */
    public function __construct($groups)
    {
        $this->groups = is_array($groups) ? $groups : [$groups];
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
        /** @var Attachment|null $model */
        if (! is_int($value) || ($model = Attachment::find($value)) === null) {
            return false;
        }

        return in_array($model->group, $this->groups, true);
    }

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        return trans('validation.attachment.group-invalid', ['groups' => $this->getGroupsAsString()]);
    }

    /**
     * Get permitted groups list as string.
     *
     * @return string
     */
    protected function getGroupsAsString()
    {
        $groups = $this->groups;
        sort($groups);
        array_walk($groups, function (&$group) {
            $group = '.' . $group;
        });

        return implode(', ', $groups);
    }
}
