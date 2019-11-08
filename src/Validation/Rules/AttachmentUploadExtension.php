<?php

namespace DigitSoft\Attachments\Validation\Rules;

use Illuminate\Http\UploadedFile;
use Illuminate\Contracts\Validation\Rule;
use DigitSoft\Attachments\Traits\WithAttachmentsManager;

/**
 * Validation rule for `Attachment` upload process.
 *
 * Validates extension of the file.
 */
class AttachmentUploadExtension implements Rule
{
    const PRESET_ALL_PERMITTED              = 'all';
    const PRESET_IMAGES                     = 'images';
    const PRESET_MEDIA_VIDEO                = 'media-video';
    const PRESET_MEDIA_AUDIO                = 'media-audio';
    const PRESET_DOCUMENTS_ALL              = 'docs';
    const PRESET_DOCUMENTS_TEXT             = 'docs-doc';
    const PRESET_DOCUMENTS_TABLES           = 'docs-xls';
    const PRESET_DOCUMENTS_PRESENTATIONS    = 'docs-ppt';
    const PRESET_DOCUMENTS_OTHER            = 'docs-other';

    use WithAttachmentsManager;

    /**
     * Permitted extensions.
     *
     * @var string[]
     */
    protected $extensions = [];

    /**
     * List of presets.
     * @var array
     */
    protected static $presetsExtensions = [
        self::PRESET_IMAGES => ['jpg', 'jpeg', 'png', 'gif'],
        self::PRESET_DOCUMENTS_TEXT => ['doc', 'docx', 'rtf', 'odt', 'pdf'],
        self::PRESET_DOCUMENTS_TABLES => ['xls', 'xlsx', 'ods'],
        self::PRESET_DOCUMENTS_PRESENTATIONS => ['ppt', 'pptx'],
        self::PRESET_DOCUMENTS_OTHER => ['xml', 'txt'], // not included in DOCUMENTS_ALL
        self::PRESET_DOCUMENTS_ALL => [], // will be filled on class boot
        self::PRESET_MEDIA_VIDEO => ['mp4', 'mpg'],
        self::PRESET_MEDIA_AUDIO => ['aac', 'ogg', 'mp3', 'mp4'],
        self::PRESET_ALL_PERMITTED => [], // will be filled on class boot
    ];

    /**
     * Flag, determines whether class is fully booted.
     * @var bool
     */
    protected static $booted = false;

    /**
     * AttachmentUploadExtRule constructor.
     *
     * @param  array $extensions List of permitted file extensions (wo leading dot)
     * @param  array $presets    List of preset names to use to fill permitted extensions
     */
    public function __construct($extensions = [], $presets = [])
    {
        static::bootIfNotBooted();
        $this->extensions = is_array($extensions) ? $extensions : [$extensions];
        $this->addExtensionsFromPresets($presets);
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
        if (
            ! $value instanceof UploadedFile
            || ! is_string($ext = static::attachmentsManager()->getUploadedFileExtension($value))
        ) {
            return false;
        }

        return in_array($ext, $this->extensions, true);
    }

    /**
     * Get the validation error message.
     *
     * @return string|array
     */
    public function message()
    {
        // Add translation to message into your `resources/lang/{language}/validation.php`
        return trans('validation.attachment.ext-invalid', ['extensions' => $this->getExtensionsAsString()]);
    }

    /**
     * Add all image extensions.
     *
     * @return $this
     */
    public function withImages()
    {
        return $this->addExtensionsFromPresets(static::PRESET_IMAGES);
    }

    /**
     * Add all video extensions.
     *
     * @return $this
     */
    public function withVideos()
    {
        return $this->addExtensionsFromPresets(static::PRESET_MEDIA_VIDEO);
    }

    /**
     * Add all audio extensions.
     *
     * @return $this
     */
    public function withAudios()
    {
        return $this->addExtensionsFromPresets(static::PRESET_MEDIA_AUDIO);
    }

    /**
     * Add all `documents` extensions.
     *
     * @return $this
     */
    public function withDocuments()
    {
        return $this->addExtensionsFromPresets(static::PRESET_DOCUMENTS_ALL);
    }

    /**
     * Add all `text documents` extensions.
     *
     * @return $this
     */
    public function withDocumentsText()
    {
        return $this->addExtensionsFromPresets(static::PRESET_DOCUMENTS_TEXT);
    }

    /**
     * Add all `excel documents` extensions.
     *
     * @return $this
     */
    public function withDocumentsTables()
    {
        return $this->addExtensionsFromPresets(static::PRESET_DOCUMENTS_TABLES);
    }

    /**
     * Add all `presentation documents` extensions.
     *
     * @return $this
     */
    public function withDocumentsPresentations()
    {
        return $this->addExtensionsFromPresets(static::PRESET_DOCUMENTS_PRESENTATIONS);
    }

    /**
     * Get permitted extensions as string.
     *
     * @return string
     */
    protected function getExtensionsAsString()
    {
        $extensions = $this->extensions;
        sort($extensions);
        array_walk($extensions, function (&$ext) {
            $ext = '.' . $ext;
        });

        return implode(', ', $extensions);
    }

    /**
     * Add extensions from given preset(s).
     *
     * @param  string[]|string $presets
     * @return $this
     */
    protected function addExtensionsFromPresets($presets)
    {
        $presets = is_array($presets) ? $presets : [$presets];

        $extList = [];

        foreach ($presets as $presetName) {
            $extList[] = static::$presetsExtensions[$presetName] ?? [];
        }

        // No extensions = no merge
        if (empty($extList)) {
            return $this;
        }

        $this->extensions = array_unique(array_merge(
            $this->extensions,
            array_merge(...$extList),
        ));

        return $this;
    }

    /**
     * Boot class, perform some operations.
     */
    protected static function bootIfNotBooted()
    {
        if (static::$booted) {
            return;
        }

        // Collect office document extensions
        static::$presetsExtensions[static::PRESET_DOCUMENTS_ALL] = array_unique(array_merge(
            static::$presetsExtensions[static::PRESET_DOCUMENTS_ALL],
            static::$presetsExtensions[static::PRESET_DOCUMENTS_TEXT],
            static::$presetsExtensions[static::PRESET_DOCUMENTS_TABLES],
            static::$presetsExtensions[static::PRESET_DOCUMENTS_PRESENTATIONS],
        ));

        // Collect all permitted extensions
        $all = array_values(static::$presetsExtensions);
        static::$presetsExtensions[static::PRESET_ALL_PERMITTED] = array_unique(array_merge(...$all));

        static::$booted = true;
    }
}
