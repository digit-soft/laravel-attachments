<?php

namespace DigitSoft\Attachments\Traits;

/**
 * Trait provides quick access to `AttachmentsManager` instance for the class.
 */
trait WithAttachmentsManager
{
    public static $_attachmentsManagerInstance;

    /**
     * Get `AttachmentsManager` instance.
     *
     * @return \DigitSoft\Attachments\AttachmentsManager
     */
    protected static function attachmentsManager()
    {
        return self::$_attachmentsManagerInstance ?? self::$_attachmentsManagerInstance = app('attachments');
    }
}
