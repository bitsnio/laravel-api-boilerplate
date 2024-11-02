<?php

namespace Bitsnio\JsonToLaravelMigrations;

use Exception;
use Illuminate\Support\Facades\Validator;

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

        foreach($json['data'] as $table => $columns) {
            $schema[$table] = [];

            foreach($columns as $column => $parameters) {
                $parametersList = explode('|', $parameters);
                $parametersList = array_map(function($parameter) {
                    return explode(':', $parameter);
                }, $parametersList);

                $schema[$table][$column] = $parametersList;
            }
        }

        // return ['schema' => $schema, 'alter' => $json['alter']];
        return $schema;
    }

    /**
     * Load JSON from file
     *
     * @return array
     */
    public function get($get_module_info = false) {
        $json = file_get_contents($this->path);
        return $this->formatJson(json_decode($json, true), $get_module_info);
    }

    /**
     * Check if the path exists
     */
    private function exists() {
        if(!file_exists($this->path)) throw new \Exception("JSON Schema file does not exist. Path: ". $this->path);
    }

    private function formatJson($jsonData, $get_module_info = false){
        // $alter = (isset($jsonData['alter'])) ? $jsonData['alter'] : false;
        $json = [];
        $sub_module = pathinfo($this->path, PATHINFO_FILENAME);
        if($get_module_info) return $jsonData['main_module'];
        else{
            // if($alter){
            //     foreach($jsonData['properties'] as $key => $value){
            //         if($value['type'] == 'object'){
            //             foreach($value['properties'] as $k => $v){
            //                 if(isset($v['enum'])) $json[$sub_module][$k] = "enum:".implode(',',$v['enum']);
            //                 else $json[$sub_module][$k] = $v['type'];
            //             }
            //         }
            //         elseif ($value['type'] == 'array'){
            //             $json[$sub_module][$key] = 'foreign|nullable|constrained|onDelete';
            //             foreach($value['items']['properties'] as $k => $v){
            //                 if(isset($v['enum'])) $json[$key][$k] = 'enum:'.implode(',',$v['enum']);
            //                 else $json[$key][$k] = $v['type'];
            //             }
            //         }
            //         else {
            //             if(isset($value['enum'])) $json[$sub_module][$key] = 'enum:'.implode(',',$value['enum']);
            //             else $json[$sub_module][$key] = $value['type'];
            //         }
            //     }
            // }
            // else{
            //     foreach ($jsonData['properties'] as $key => $value) {
            //         if ($value['type'] == 'object') {
            //             foreach ($value['properties'] as $k => $v) {
            //                 if (isset($v['alter'])) {
            //                     if ($v['alter']['operation'] == 'add') {
            //                         if (isset($v['enum'])) $json[$sub_module][$k.'|add'] = 'enum:' . implode(',', $v['enum']);
            //                         else $json[$sub_module][$k.'|add'] = $v['type'];
            //                     }
            //                     if ($v['alter']['operation'] == 'update') {
            //                         if (isset($v['enum'])) $json[$sub_module][$k.'|update'] = 'enum:' . implode(',', $v['enum']);
            //                         else $json[$sub_module][$k.'|update'] = $v['type'];
            //                     }
            //                     if ($v['alter']['operation'] == 'rename') {
            //                         $json[$sub_module][$k.'|rename'] = $v['type'];
            //                     }
            //                     if ($v['alter']['operation'] == 'delete') {
            //                         $json[$sub_module][$k.'|delete'] = $v['type'];
            //                     }
            //                 }
            //             }
            //         } elseif ($value['type'] == 'array') {
            //             $json[$sub_module][$key] = 'foreign|nullable|constrained|onDelete';
            //             foreach ($value['items']['properties'] as $k => $v) {
            //                 if (isset($v['alter'])) {
            //                     if ($v['alter']['operation'] == 'add') {
            //                         if (isset($v['enum'])) $json[$sub_module][$k.'|add'] = 'enum:' . implode(',', $v['enum']);
            //                         else $json[$sub_module][$k.'|add'] = $v['type'];
            //                     }
            //                     if ($v['alter']['operation'] == 'update') {
            //                         if (isset($v['enum'])) $json[$sub_module][$k.'|update'] = 'enum:' . implode(',', $v['enum']);
            //                         else $json[$sub_module][$k.'|update'] = $v['type'];
            //                     }
            //                     if ($v['alter']['operation'] == 'rename') {
            //                         $json[$sub_module][$k.'|rename'] = $v['type'];
            //                     }
            //                     if ($v['alter']['operation'] == 'delete') {
            //                         $json[$sub_module][$k.'|delete'] = $v['type'];
            //                     }
            //                 }
            //             }
            //         } else {
            //             if (isset($value['alter'])) {
            //                 if ($value['alter']['operation'] == 'add') {
            //                     if (isset($value['enum'])) $json[$sub_module][$key.'|add'] = 'enum:' . implode(',', $value['enum']);
            //                     else $json[$sub_module][$key.'|add'] = $value['type'];
            //                 }
            //                 if ($value['alter']['operation'] == 'update') {
            //                     if (isset($value['enum'])) $json[$sub_module][$key.'|update'] = 'enum:' . implode(',', $value['enum']);
            //                     else $json[$sub_module][$key.'|update'] = $value['type'];
            //                 }
            //                 if ($value['alter']['operation'] == 'rename') {
            //                     $json[$sub_module][$key.'|rename'] = $value['type'];
            //                 }
            //                 if ($value['alter']['operation'] == 'delete') {
            //                     $json[$sub_module][$key.'|delete'] = $value['type'];
            //                 }
            //             }
            //         }
            //     }
            // }
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
            // return['data' => $json, 'alter' => $alter];
            return $json;
        }
    }
}
