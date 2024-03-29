<?php

namespace DigitSoft\Attachments\Commands;

use Illuminate\Console\Command;
use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;

class CreateDirectoriesCommand extends Command
{
    protected $name = 'attachments:directories';

    protected $description = 'Create save and publish directories for attachments';

    protected Filesystem $files;

    protected Repository $config;

    /**
     * CreateMigrationCommand constructor.
     *
     * @param  Filesystem $files
     * @param  Repository $config
     */
    public function __construct(Filesystem $files, Repository $config)
    {
        parent::__construct();
        $this->files = $files;
        $this->config = $config;
    }

    /**
     * Handle command
     *
     * @throws \Exception
     */
    public function handle()
    {
        $ds = DIRECTORY_SEPARATOR;
        $storagePath = app()->storagePath() . $ds . 'app' . $ds;
        $savePublicPath = $storagePath . ltrim($this->config->get('attachments.save_path_public'), $ds);
        $savePrivatePath = $storagePath . ltrim($this->config->get('attachments.save_path_private'), $ds);
        $imageCachePath = $storagePath . ltrim($this->config->get('attachments.image_cache_path'), $ds);
        $this->createDir($savePublicPath);
        $this->createDir($savePrivatePath);
        $this->createDir($imageCachePath);
        $this->info("Do not forget to make symlink to 'storage/app/public' via 'storage:link' command");
    }

    /**
     * Create directory
     *
     * @param  string $path
     */
    protected function createDir(string $path): void
    {
        if ($this->files->exists($path)) {
            $this->error("Path {$path} already exists");

            return;
        }
        $this->files->makeDirectory($path, 0755, true, true);
        $this->info("Directory {$path} created");
    }

    /**
     * Create symlink
     *
     * @param  string $target
     * @param  string $link
     */
    protected function createSymlink(string $target, string $link): void
    {
        if ($this->files->exists($link)) {
            $this->error("Link path {$link} already exists");

            return;
        }
        if (! $this->files->exists($target)) {
            $this->error("Target path {$target} doesn't exist");

            return;
        }
        $this->files->link($target, $link);
        $this->info("Link {$link} to {$target} created");
    }
}
