<?php
namespace App\Traits;

use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Throwable;

// use Illuminate\Support\Facades\File;

trait AlterSchema{
    private $current_table;
    private $new_columns = [];
    private $columns_to_update = [];
    private $columns_to_delete = [];
    private $files = [];

    public function alterSchema(){
        // DB::beginTransaction();
        $schemaPath = base_path('Schema');
        // Check if the Schema directory exists
        if (File::isDirectory($schemaPath)) {
            // Get all subdirectories within the Schema directory
            $directories = File::directories($schemaPath);

            // Iterate through each subdirectory
            foreach ($directories as $dir) {
                $alterPath = $dir . DIRECTORY_SEPARATOR . 'Alter';

                // Check if the 'alter' subdirectory exists in each subdirectory
                if (File::isDirectory($alterPath)) {
                    // Get all files within the 'alter' directory
                    $this->files = array_merge($this->files, File::allFiles($alterPath));
                }
            }

            $this->files = array_filter($this->files, function ($file) {
                // Get the file name
                $fileName = pathinfo($file, PATHINFO_FILENAME);
            
                // Check if the file name does not start with a timestamp (YYYY-MM-DD_HH-MM-SS)
                return !preg_match('/^\d{4}-\d{2}-\d{2}_\d{2}-\d{2}-\d{2}/', $fileName);
            });
        }

        foreach($this->files as $file){
            $this->loadFile($file);
            $this->alter($file);
            $this->updateFile($file);
            $this->reset();
        }
        return true;
    }


    public function alter($file){
        try{
            $table = pathinfo($file, PATHINFO_FILENAME);
            $table = 'companies';
            Schema::table($table, function (Blueprint $table) {
    
                // Add new columns
                if(!empty($this->new_columns)){
                    foreach($this->new_columns as $new_column){
                        if(isset($new_column['length']) && isset($new_column['default'])) {
                            $table->{$new_column['type']}($new_column['title'], trim($new_column['length'], '"\'', ))->default($new_column['default'])->after('id');
                        }
                        elseif(isset($new_column['length'])){
                            $table->{$new_column['type']}($new_column['title'], trim($new_column['length'], '"\'', ))->after('id');
                        }
                        elseif(isset($new_column['default'])){
                            $table->{$new_column['type']}($new_column['title'])->default($new_column['default'])->after('id');
                        }
                        else{
                            $table->{$new_column['type']}($new_column['title'])->befor('id');
                        }
                        // $table->integer('new_column')->default(0)->after('id');
                    }
                }
    
                // Delete columns
                if(!empty($this->columns_to_delete)){
                    foreach($this->columns_to_delete as $delete){
                        $table->dropColumn($delete);
                    }
                }
    
                //Update columns
                if(!empty($this->columns_to_update)){
                    foreach($this->columns_to_update as $index => $update){
                        if(isset($update['type'])){
                            $table->{$update['type']}($index)->change();
                        }
                        if(isset($update['length'])){
                            $table->{$update['type']}($index, trim($update['length'], '"\'', ))->change();
                        }
                        if(isset($update['default'])){
                            $table->{$update['type']}($index)->default($update['default'])->change();
                        }
                        if(isset($update['rename'])){
                            $table->renameColumn($index, $update['title']);
                        }
                    }
                }
            });
        }
        catch(Throwable $th){
            DB::rollBack();
            throw new Exception($th->getMessage());
        }
    }

    public function loadFile($file){
        // $table = pathinfo($file, PATHINFO_FILENAME);
        $this->current_table = 'companies';

        $file_content = json_decode(file_get_contents($file), true);

        if(!empty($file_content)){
            if(isset($file_content['delete'])) $this->columns_to_delete = $file_content['delete'];

            if(isset($file_content['add'])) $this->new_columns = $file_content['add'];

            if(isset($file_content['update'])) $this->new_columns = $file_content['update'];
        }
    }

    private function reset(){
        $this->current_table = null;
        $this->new_columns = [];
        $this->columns_to_delete = [];
        $this->columns_to_update = [];
    }

    private function updateFile($file){
        $timestamp = date('Y-m-d_H-i-s');
        $path = dirname($file->getRealPath()).'/';
        $renameFile = $path.$timestamp.'_alter_'.$file->getFilename();
        File::move($path.$file->getFilename(), $renameFile);
    }

    private function validate($attributes){
        $types = [
            "integer",
            "string",
            "float",
            "enum",
            "double",
        ];

        $lengths = [
            "integer" => 11,  
            "string" => 255, 
            "float" => [8, 2], 
            "enum" => null, 
            "double" => [15, 8]
        ];
    }

}