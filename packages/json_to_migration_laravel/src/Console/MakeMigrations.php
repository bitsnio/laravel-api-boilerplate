<?php

namespace Bitsnio\JsonToLaravelMigrations\Console;

use Illuminate\Console\Command;
use Bitsnio\JsonToLaravelMigrations\JsonToMigration;

class MakeMigrations extends Command {
    protected $signature = 'json:migrate {file} {path}';

    protected $description = "Create migrations from JSON file.";

    public function handle() {
        $this->info("Creating migrations...");
        new JsonToMigration($this->argument('file'), $this->argument('path'));
        $this->info("Migrations created!");
    }

}