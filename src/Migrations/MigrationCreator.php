<?php

namespace DigitSoft\Attachments\Migrations;

use Illuminate\Filesystem\Filesystem;

/**
 * Class MigrationCreator with own stubs
 *
 * @package DigitSoft\LaravelRbac\Migrations
 */
class MigrationCreator extends \Illuminate\Database\Migrations\MigrationCreator
{
    public function __construct(Filesystem $files)
    {
        parent::__construct($files, __DIR__ . DIRECTORY_SEPARATOR . 'stubs');
    }

    /**
     * @inheritdoc
     */
    protected function getStub($table, $create): string
    {
        $stubPath = $this->stubPath() . '/' . $table . '.stub';
        if ($this->files->exists($stubPath)) {
            return $this->files->get($stubPath);
        }

        return parent::getStub($table, $create);
    }

    /**
     * @inheritdoc
     */
    public function stubPath(): string
    {
        return __DIR__ . '/stubs';
    }
}
