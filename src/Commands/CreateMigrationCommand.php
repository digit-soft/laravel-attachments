<?php

namespace DigitSoft\Attachments\Commands;

use DigitSoft\Attachments\Migrations\MigrationCreator;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

class CreateMigrationCommand extends Command
{
    protected $name = 'attachments:table';

    protected $description = 'Create attachments tables migration';

    /**
     * @var Filesystem
     */
    protected $files;
    /**
     * @var MigrationCreator|null
     */
    protected $migrationCreator;


    /**
     * CreateMigrationCommand constructor.
     * @param Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Handle command
     * @throws \Exception
     */
    public function handle()
    {
        $table = 'attachments';
        $migrationName = 'create_' . $table . '_table';
        $this->getMigrationCreator()->create($migrationName, $this->laravel->databasePath() . '/migrations', $table);
        $this->info("Migration for table ${table} created");
    }

    /**
     * Get migration creator instance
     * @return MigrationCreator
     */
    protected function getMigrationCreator()
    {
        if ($this->migrationCreator === null) {
            $this->migrationCreator = new MigrationCreator($this->files);
        }
        return $this->migrationCreator;
    }
}