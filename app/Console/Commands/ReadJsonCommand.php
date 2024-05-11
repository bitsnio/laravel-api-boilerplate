<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ReadJsonCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'schema:read-jsons {module}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Read all files from "Schema" directory';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Reading directory for json files.....');
        $path = 'Schema/'.$this->argument('module').'/';
        if(!is_dir($path)) $this->error('Module '.$this->argument('module').' not found');

        $files = File::files($path);

        $filteredFiles = array_filter($files, function ($file) {
            // Get the file name
            $fileName = pathinfo($file, PATHINFO_FILENAME);
        
            // Check if the file name does not start with a timestamp (YYYY-MM-DD_HH-MM-SS)
            return !preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}_/', $fileName);
        });

        foreach ($filteredFiles as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            Artisan::call('module:make-All '.$name.' '.$this->argument('module'));
            $this->info('Files created for sub-module '.$name.'.....');
            $timestamp = date('Y-m-d_H-i-s');
            $renameFile = $path.$timestamp.'_'.$file->getFilename();
            File::move($path.$file->getFilename(), $renameFile);
        }
        
    }
}
