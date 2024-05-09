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
    protected $signature = 'schema:read-jsons';

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
        $files = File::files('Schema');
        foreach ($files as $file) {
            $jsonData = file_get_contents('Schema/'.$file->getFilename());
            $data = json_decode($jsonData, true);
            Artisan::call('module:make-All '.$data['class'].' '.$data['module']);
            $this->info('Files created for class '.$data['class'].'.....');
        }
        //
    }
}
