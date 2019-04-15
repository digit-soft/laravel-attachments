<?php

namespace DigitSoft\Attachments\Commands;

use Illuminate\Console\Command;

/**
 * Cleanup unused attachments command
 * @package DigitSoft\Attachments\Commands
 */
class CleanupAttachmentsCommand extends Command
{
    protected $name = 'attachments:cleanup';

    protected $description = 'Cleanup unused attachments';

    /**
     * CreateMigrationCommand constructor.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Handle command
     * @throws \Exception
     */
    public function handle()
    {
        $removedCount = $this->attachmentsManager()->cleanup();
        if ($removedCount) {
            $this->info(sprintf('Removed %d attachments', $removedCount));
        } else {
            $this->info('No attachments were removed');
        }
    }

    /**
     * Get attachments manager instance
     * @return \DigitSoft\Attachments\AttachmentsManager
     */
    private function attachmentsManager()
    {
        return app('attachments');
    }
}
