<?php

namespace DigitSoft\Attachments\Commands;

use Illuminate\Console\Command;
use DigitSoft\Attachments\AttachmentsManager;

/**
 * Cleanup unused attachments command
 *
 * @package DigitSoft\Attachments\Commands
 */
class CleanupImageCacheCommand extends Command
{
    protected $name = 'attachments:cleanup-presets';

    protected $description = 'Cleanup image cache for all presets';

    protected $signature = 'attachments:cleanup-presets
        {group_name : Attachments file group to cleanup}';

    /**
     * Handle command
     *
     * @throws \Exception
     */
    public function handle()
    {
        $groupName = $this->argument('group_name');
        $storage = $this->attachmentsManager()->getStoragePublic();
        $imgCachePath = config('attachments.image_cache_path');
        if (! $storage->exists($imgCachePath)) {
            return;
        }
        $presetDirs = $storage->directories($imgCachePath);
        $cnt = 0;
        foreach ($presetDirs as $presetDir) {
            $searchDir = $presetDir . DIRECTORY_SEPARATOR . $groupName;
            if ($storage->exists($searchDir)) {
                $cnt++;
                $storage->deleteDirectory($searchDir);
            }
        }

        $message = $cnt > 0 ? sprintf('Removed image cache for %d presets', $cnt) : 'Nothing removed';
        $this->getOutput()->success($message);
    }

    /**
     * Get attachments manager instance
     *
     * @return \DigitSoft\Attachments\AttachmentsManager
     */
    private function attachmentsManager(): AttachmentsManager
    {
        return app('attachments');
    }
}
