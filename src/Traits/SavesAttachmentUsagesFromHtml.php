<?php

namespace DigitSoft\Attachments\Traits;

use DigitSoft\Attachments\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Request;
use Illuminate\Database\Eloquent\Builder;
use DigitSoft\Attachments\AttachmentUsage;

/**
 * Trait SavesAttachmentUsagesFromHtml.
 *
 * Saves usage of attachments parsed from HTML attributes.
 * WARNING! Parses HTML DOM!
 */
trait SavesAttachmentUsagesFromHtml
{
    /**
     * Get list of attributes with HTML markup to parse attachments used.
     *
     * @return string[]
     */
    abstract protected function getAttributesToParseHtmlAttachments(): array;

    /**
     * Boot trait.
     */
    protected static function bootSavesAttachmentUsagesFromHtml()
    {
        static::saved(function (Model $model) {
            /** @var Model|\DigitSoft\Attachments\Traits\HasAttachments|\DigitSoft\Attachments\Traits\SavesAttachmentUsagesFromHtml $model */
            $tagName = 'html-parsed-attachment';
            $attachmentKeys = static::getAttachmentsFromHtml($model, $model->getAttributesToParseHtmlAttachments());

            // Remove old attachments
            $model->attachmentUsages()->where('tag', $tagName)->delete();

            // Add usage to newly saved attachments
            if (! empty($attachmentKeys)) {
                $pivotAttributes = array_fill_keys($attachmentKeys, ['tag' => $tagName]);
                // Make fake models instead of getting them from a DB
                $models = collect($attachmentKeys)->map(function($id) {
                    $mdl = (new Attachment)->forceFill(['id' => $id])->syncOriginal();
                    $mdl->exists = true;
                    return $mdl;
                })->keyBy('id');
                // Let it be here for the future ^_^
                $model->attachments()->saveMany($models, $pivotAttributes);
            }
        });
    }

    /**
     * Get attachments used in some HTML attributes of the model.
     *
     * @param  \Illuminate\Database\Eloquent\Model $model
     * @param  array                               $attributes
     * @return array
     */
    protected static function getAttachmentsFromHtml(Model $model, array $attributes): array
    {
        $baseUrl = static::getAttachmentsBaseUrl();
        $regEx = '/^' . addcslashes($baseUrl, ':./-*+') . '\/(.*?)\/((?:[^\/]+)\.[a-z0-9]{1,6})$/iu';
        $attachmentPossible = [];
        foreach ($attributes as $attribute) {
            $attributeVal = AttachmentUsage::getAttributeValueNested($model, $attribute);
            if (! is_string($attributeVal) || empty($attributeVal = trim($attributeVal))) {
                continue;
            }

            try {
                $dom = new \DOMDocument('1.0', 'utf8');
                if (
                    ! $dom->loadHTML($attributeVal)
                    || ($images = $dom->getElementsByTagName('img'))->count() <= 0
                ) {
                    continue;
                }
            } catch (\Throwable $e) {
                continue;
            }

            foreach ($images as $img) {
                /** @var \DOMNode $img */
                $matches = [];
                if (
                    ($srcAttr = $img->attributes->getNamedItem('src')) !== null
                    && ! empty($srcVal = $srcAttr->value)
                    && preg_match($regEx, $srcVal, $matches)
                ) {
                    $attachmentPossible[] = ['group' => $matches[1], 'name' => $matches[2]];
                }
            }
        }

        // Nothing found
        if (empty($attachmentPossible)) {
            return [];
        }

        // Produces query like:
        // select * from "attachments" where ((("group" = ? and "name" = ?)) or (("group" = ? and "name" = ?)) or (("group" = ? and "name" = ?)))
        $query = Attachment::query()
            ->where(function (Builder $q) use ($attachmentPossible) {
                foreach ($attachmentPossible as $whereClause) {
                    $q->orWhere(function (Builder $q) use ($whereClause) {
                        $q->where($whereClause);
                    });
                }
            });

        return $query->pluck('id')->all();
    }

    /**
     * Get absolute base URL.
     *
     * @return string
     */
    private static function getAttachmentsBaseUrl(): string
    {
        $scheme = config('attachments.url.scheme', 'https');
        $host = config('attachments.url.host');
        $scheme = $scheme ?? Request::getScheme();
        $host = $host ?? Request::getHost();

        return $scheme . '://' . $host;
    }
}
