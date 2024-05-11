<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;


class JsonSchemaController extends Controller
{
    public function createJsonSchema(Request $request){
        try{
            $data = $request->toArray();
            $validator = Validator::make($data, [
                'module' => 'string|required',
                'sub_module' => 'string|required',
                'schema' => 'array|required',
            ]);
            
            $fileName = $data['sub_module'].'.json';
            $path = 'Schema/' .$data['module'].'/';
            $module = 'Modules/' .$data['module'].'/';

            //Validations for module and sub-modules
            if($validator->fails()) return response()->json(['error' => implode(' ', $validator->messages()->all())]);
            if(!is_dir(base_path($module))) return response()->json(['error' => 'Module not found']);
            if(file_exists(base_path($path.$fileName))) return response()->json(['error' => 'Schema or sub module already exists']);
            if (!file_exists(base_path($path))) mkdir(base_path($path), 0777, true);

            file_put_contents(base_path($path.$fileName), json_encode($data['schema'], JSON_PRETTY_PRINT));
            return response()->json('Json schema created successfully');
        }
        catch(\Throwable $th){
            throw new \Exception($th->getMessage());
        }
    }

}
