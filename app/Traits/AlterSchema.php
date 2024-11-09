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
    private $originalSchema = [];
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
            $this->current_table = $table;
            $this->captureSchemaState($table);
            $this->validate($table);
            // dd(3334);
            Schema::table($table, function (Blueprint $table) {
                // Add new columns
                if(!empty($this->new_columns)){
                    foreach($this->new_columns as $new_column){
                        // dd($new_column);
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
                        $table->integer('new_column')->default(0)->after('id');
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
            $this->reverseSchemaChange();
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
        $this->originalSchema = [];
    }

    private function updateFile($file){
        $timestamp = date('Y-m-d_H-i-s');
        $path = dirname($file->getRealPath()).'/';
        $renameFile = $path.$timestamp.'_alter_'.$file->getFilename();
        File::move($path.$file->getFilename(), $renameFile);
    }

    public function captureSchemaState($tableName)
    {
        if (!Schema::hasTable($tableName)) {
            throw new Exception("Table '{$tableName}' does not exist.");
        }

        $this->originalSchema[$tableName] = $this->getColumnDetails($tableName);
    }


    protected function getColumnDetails($tableName)
    {
        $columns = DB::select("SHOW COLUMNS FROM `{$tableName}`");
        $columnDetails = [];

        foreach ($columns as $column) {
            $columnDetails[$column->Field] = [
                'type' => $column->Type,
                'default' => $column->Default,
            ];
        }

        return $columnDetails;
    }


    public function reverseSchemaChange()
    {
        $tableName = $this->current_table;
        if (!isset($this->originalSchema[$tableName])) {
            throw new Exception("No recorded schema state to reverse for table '{$tableName}'.");
        }

        $currentColumns = $this->getColumnDetails($tableName);

        // Drop columns that were added after schema capture
        Schema::table($tableName, function (Blueprint $table) use ($currentColumns, $tableName) {
            foreach ($currentColumns as $column => $details) {
                if (!isset($this->originalSchema[$tableName][$column])) {
                    $table->dropColumn($column); // Drop if it's a newly added column
                }
            }
        });

        // Check for renamed columns or modified types by comparing original schema
        foreach ($this->originalSchema[$tableName] as $columnName => $originalDetails) {
            if (isset($currentColumns[$columnName])) {
                $currentDetails = $currentColumns[$columnName];
                
                // Check if the type has changed; if so, revert it
                if ($currentDetails['type'] !== $originalDetails['type']) {
                    Schema::table($tableName, function (Blueprint $table) use ($columnName, $originalDetails) {
                        $table->dropColumn($columnName);
                        $table->addColumn($originalDetails['type'], $columnName);
                    });
                }
            } else {
                // If the original column is missing in the current columns, it was likely renamed
                throw new Exception("Column '{$columnName}' appears to have been renamed or removed; manual intervention required to reverse.");
            }
        }
    }


    private function validate($table){
        foreach($this->new_columns as $new_column){
            $this->validateColumnType($table, $new_column['title'], $new_column['type'], null, true);
        }
    }

    function validateColumnType($tableName, $columnName, $columnType, $is_new_column,  $enumOptions = null)
    {
        if (!Schema::hasTable($tableName)) {
            throw new Exception("Table '{$tableName}' does not exist.");
        }
        
        if (Schema::hasColumn($tableName, $columnName)) {
            if($is_new_column){
                $this->reverseSchemaChange();
                throw new Exception("Column '{$columnName}' already exist in table '{$tableName}'.");
            }
            elseif($this->isForeignKey($tableName, $columnName)) {
                throw new Exception("Cannot rename/delete column '{$columnName}' as it is a foreign key.");
            }
        }

        // $columnType = $column->getType()->getName();
        
        $validTypes = [
            'integer' => 'integer',
            'smallint' => 'numeric',
            'bigint' => 'numeric',
            'string' => 'string',
            'text' => 'string',
            'boolean' => 'boolean',
            'datetime' => 'date',
            'date' => 'date',
            'timestamp' => 'date',
            'float' => 'numeric',
            'decimal' => 'numeric',
        ];
    
        // Check if column type is valid or handle `enum` type specifically
        if ($columnType === 'enum') {
            if (empty($value)) {
                throw new Exception("Enum values not defined for column '{$columnName}'.");
            }
            
            // Define validation rule for enum as 'in' with the list of allowed values
            $rules = ['value' => 'in:' . implode(',', $enumOptions)];
        } elseif (array_key_exists($columnType, $validTypes)) {
            // Use standard validation rule for recognized types
            $rules = ['value' => $validTypes[$columnType]];
        } else {
            // Unsupported type
            $this->reverseSchemaChange();
            throw new Exception("Unsupported column type '{$columnType}' for column '{$columnName}'.");
        }
    
        // Perform validation
        $validator = validator(['value' => $columnType], $rules);
    
        if ($validator->fails()) {
            throw new Exception("The value provided is invalid for column '{$columnName}' of type '{$columnType}'.");
        }
        return true;
    }

    protected function isForeignKey($tableName, $columnName)
    {
        // Query to check foreign key constraints on the specified column
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE TABLE_NAME = ? 
            AND COLUMN_NAME = ? 
            AND TABLE_SCHEMA = DATABASE()
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ", [$tableName, $columnName]);

        return !empty($foreignKeys);
    }
}