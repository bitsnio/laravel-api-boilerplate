<?php

namespace Bitsnio\JsonToLaravelMigrations;

use Illuminate\Support\ServiceProvider;
use Bitsnio\JsonToLaravelMigrations\Console\MakeMigrations;

class JsonToLaravelMigrationsServiceProvider extends ServiceProvider {
    public function register() {

    }

    public function boot() {
        if($this->app->runningInConsole()) {
            $this->commands([
                MakeMigrations::class
            ]);
        }
    }
}