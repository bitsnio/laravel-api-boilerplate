<?php

namespace Bitsnio\JsonToLaravelMigrations;

use Bitsnio\Modules\Commands\MakeAllfilesCommand;
use Illuminate\Support\Str;


class MigrationCreator {
    /**
     * Migration methods
     */
    protected $methods;

    /**
     * Create an instance of the Migration Creator
     *
     * @param array $methods
     * @return void
     */
    public function __construct(Array $methods) {
        $this->methods = $methods;
    }

    public function create($destinationPath) {
        foreach($this->methods as $table => $methods) {
            $this->createMigration($table, $methods, $destinationPath);
            // So migrations get created in order
            sleep(1);
        }
    }

    private function createMigration($table, $methods, $destinationPath) {
        $filename = $this->generateFileName($table);
        $name     = $this->generateName($table);
        $stub     = $this->createStub($name,Str::plural(strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $table))), $methods);
        $path     = $this->getPath($filename, $destinationPath);

        file_put_contents($path, $stub);
    }

    private function generateName($table) {
        return Str::studly(
            sprintf("create_%s_table", $table)
        );
    }

    private function generateFileName($table) {
        return sprintf('%s_create_%s_table.php', date('Y_m_d_His'), Str::plural(strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', $table))));
    }

    private function createStub($className, $tableName, $methods) {
        $stub = $this->getStub();
        $stub = str_replace("{{migrationName}}", $className, $stub);
        $stub = str_replace("{{tableName}}", $tableName, $stub);
        $stub = str_replace("{{methods}}", implode("\n\t\t\t", $methods), $stub);
        return $stub;
    }

    private function getStub() {
        return file_get_contents(__DIR__ . '/stubs/migration.stub');
    }

    private function getPath($filename, $destinationPath) {
        if (!file_exists($destinationPath)) mkdir($destinationPath, 0777, true);
        return $destinationPath . $filename;
    }
}
