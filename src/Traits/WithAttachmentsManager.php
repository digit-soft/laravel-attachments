<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\AttachmentsManager;

/**
 * Trait provides quick access to `AttachmentsManager` instance for the class.
 */
trait WithAttachmentsManager
{
    public static ?AttachmentsManager $_attachmentsManagerInstance = null;

    /**
     * Get `AttachmentsManager` instance.
     *
     * @return \DigitSoft\Attachments\AttachmentsManager
     */
    protected static function attachmentsManager(): AttachmentsManager
    {
        return self::$_attachmentsManagerInstance ?? self::$_attachmentsManagerInstance = app('attachments');
    }
}
