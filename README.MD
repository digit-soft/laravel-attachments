## Attach files to Eloquent models
This package gives you models, traits and some examples for attaching files to any Eloquent model
and write down those usages.
Also provides use of private files (downloaded through framework with user token).

Possible flow:
1. You upload a file
2. Create `Attachment` model for this file
3. Write down usage of this attachment by your model

If you delete this model, usage will be deleted.
If you reSave your model with another attachment - usage will be deleted.

Unused attachments can be deleted by running cleanup (@see `Facades/Attachments`).

Uploaded images can be automatically resized to max size given in config, there is a `job` for that.

See `AttachmentManager` for further usage.

Made for `Rest API` projects.
Ideally must be controller for file upload and another one for download (private files).
Controller examples are in `src/Controllers` directory.

There is also image processing controller example, which uses hexadecimal encoded width/height data, it is resizing images on the fly and puts resized images to the cache folder.
You can use NGINX, for example, to download cached image next time.


### Traits
You can found usable model traits in `src/Traits` directory. They use an observer for save/delete events.
* `HasAttachments` - General trait for use withing model
* `HasAttachmentById` - trait for model with single `attachment_id` field

### Console
#### Create migration
You can create DB migration for `Attachment` models by running artisan command:
```bash
php artisan attachments:tables
```
#### Create directories fo files
Command will create directories for attachments save/publishing.
```bash
php artisan attachments:directories
```
