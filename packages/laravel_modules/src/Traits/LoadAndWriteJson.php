<?php
namespace Bitsnio\Modules\Traits;

use Illuminate\Support\Facades\File;
trait LoadAndWriteJson{

    /**
     * Read json file coming from frent end
     * convert json data into a specific format for creating migration
     * Create Template.json file for with formated data for migration
     */
    public function createJsonTemplete($className){
        $data = $this ->readJson($className);
        $this->updateJsonFile('Template.json', $data);
    }

    //function to read json file coming from frent end and conver in a specific format to create migration
    private function readJson($name){
        $filePath = base_path('Schema/'.$name.'.json');
        $jsonData = file_get_contents($filePath);
        $data = json_decode($jsonData, true);
        $json = [];
        foreach($data['properties'] as $key => $value){
            if($value['type'] == 'object'){
                foreach($value['properties'] as $k => $v){
                    if(isset($v['enum'])) $json[$name][$k] = "enum:".implode(',',$v['enum']);
                    else $json[$name][$k] = $v['type'];
                }
            }
            elseif ($value['type'] == 'array'){
                foreach($value['items']['properties'] as $k => $v){
                    if(isset($v['enum'])) $json[$key][$k] = 'enum:'.implode(',',$v['enum']);
                    else $json[$key][$k] = $v['type'];
                }
            }
            else {
                if(isset($value['enum'])) $json[$name][$key] = 'enum:'.implode(',',$value['enum']);
                else $json[$name][$key] = $value['type'];
            }
        }
        return $json;
    }

    /**
     * Extrect Fillables for models from the formated data
     */
    public function getFillables($className){
        $data = $this->readJson($className);
        return  collect($data[$className])->keys()->toArray();;
    }

    /**
     * Create Formated Template.json file used for creating migration
     */
    private function updateJsonFile($fileName, $data) {
        $this->info('creating JSON Template for migration.........');
        if(file_exists(base_path($fileName))) file_put_contents($fileName, '');
        else file_put_contents(base_path($fileName), json_encode(''));

        $contents = File::get($fileName);
        if (!empty($contents)) File::put($fileName, '');
        File::put($fileName, json_encode($data, JSON_PRETTY_PRINT));
    }
}
