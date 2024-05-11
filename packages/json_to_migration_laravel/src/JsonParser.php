<?php

namespace Bitsnio\JsonToLaravelMigrations;

class JsonParser {
    /**
     * Path of the JSON schema
     * 
     * @var string
     */
    protected $path;

    /**
     * Create a new JSON Parser instance
     * 
     * @param string $path
     * @return void
     */
    public function __construct(String $path) {
        $this->path = $path;
        $this->exists();
    }

    /**
     * Parse the JSON file into array
     * 
     * @return array
     */
    public function parse() {
        $json = $this->get();
        $schema = [];

        foreach($json as $table => $columns) {
            $schema[$table] = [];
            
            foreach($columns as $column => $parameters) {
                $parametersList = explode('|', $parameters);
                $parametersList = array_map(function($parameter) {
                    return explode(':', $parameter);
                }, $parametersList);

                $schema[$table][$column] = $parametersList;
            }
        }

        return $schema;
    }

    /**
     * Load JSON from file
     * 
     * @return array
     */
    public function get() {
        $json = file_get_contents($this->path);
        return $this->formatJson(json_decode($json, true));
    }

    /**
     * Check if the path exists
     */
    private function exists() {
        if(!file_exists($this->path)) throw new \Exception("JSON Schema file does not exist. Path: ". $this->path);
    }

    private function formatJson($jsonData){
        $json = [];
        $sub_module = pathinfo($this->path, PATHINFO_FILENAME);;
        foreach($jsonData['properties'] as $key => $value){
            if($value['type'] == 'object'){
                foreach($value['properties'] as $k => $v){
                    if(isset($v['enum'])) $json[$sub_module][$k] = "enum:".implode(',',$v['enum']);
                    else $json[$sub_module][$k] = $v['type'];
                }
            }
            elseif ($value['type'] == 'array'){
                $json[$sub_module][$key] = 'foreign|nullable|constrained|onDelete';
                foreach($value['items']['properties'] as $k => $v){
                    if(isset($v['enum'])) $json[$key][$k] = 'enum:'.implode(',',$v['enum']);
                    else $json[$key][$k] = $v['type'];
                }
            }
            else {
                if(isset($value['enum'])) $json[$sub_module][$key] = 'enum:'.implode(',',$value['enum']);
                else $json[$sub_module][$key] = $value['type'];
            }
        }
        return $json;
    }
}