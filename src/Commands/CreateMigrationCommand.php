<?php

namespace DigitSoft\Attachments\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use DigitSoft\Attachments\Migrations\MigrationCreator;

class CreateMigrationCommand extends Command
{
    protected $name = 'attachments:tables';

    protected $description = 'Create attachments tables migration';

    /**
     * @var Filesystem
     */
    protected $files;
    /**
     * @var MigrationCreator
     */
    protected $migrationCreator;

    /**
     * CreateMigrationCommand constructor.
     *
     * @param  Filesystem $files
     */
    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    /**
     * Handle command
     *
     * @throws \Exception
     */
    public function handle()
    {
        $migrationsData = $this->getMigrationsWithStubs();
        foreach ($migrationsData as $row) {
            [$stubName, $migrationName] = $row;

            try {
                $this->getMigrationCreator()->create($migrationName, $this->laravel->databasePath() . '/migrations', $stubName);
            } catch (\Throwable $exception) {
                $this->error(get_class($exception) . ': ' . $exception->getMessage());
                continue;
            }
            $this->info("Migration from sub '${stubName}' created");
            // Pause for migration timestamp uniqueness
            sleep(1);
        }
    }

    /**
     * Get migration creator instance
     *
     * @return MigrationCreator
     */
    protected function getMigrationCreator()
    {
        if ($this->migrationCreator === null) {
            $this->migrationCreator = new MigrationCreator($this->files);
        }

        return $this->migrationCreator;
    }

    /**
     * Get list of migrations to create.
     *
     * @return string[][]
     */
    protected function getMigrationsWithStubs()
    {
        return [
            // [ 'STUB_NAME', 'MIGRATION_NAME' ]
            ['attachments', 'create_attachments_table'],
            ['attachments_upd_1', 'add_tag_column_to_attachments_usage_table'],
        ];
    }
}
