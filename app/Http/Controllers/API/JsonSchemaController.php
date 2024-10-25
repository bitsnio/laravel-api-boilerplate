<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;


class JsonSchemaController extends Controller
{
    public function createJsonSchema(Request $request)
    {
        try {
            $data = $request->toArray();
            $validator = Validator::make($data, [
                'module' => 'string|required',
                'sub_module' => 'string|required',
                'schema' => 'array|required',
                'schema.main_module' => 'required|array',
                'schema.main_module.id' => 'required|integer',
                'schema.main_module.icon' => 'required|string',
                'schema.main_module.menu_order' => 'required|integer|gt:0'
            ]);

            $fileName = str_replace(' ', '', $data['sub_module']) . '.json';
            $path = 'Schema/' . $data['module'] . '/';
            $module = 'Modules/' . $data['module'] . '/';

            $data['schema']['main_module']['sub_module'] = $data['sub_module'];

            //Validations for module and sub-modules
            if ($validator->fails()) return response()->json(['error' => implode(' ', $validator->messages()->all())]);
            if (!is_dir(base_path($module))) return response()->json(['error' => 'Module not found']);
            if (file_exists(base_path($path . $fileName))) return response()->json(['error' => 'Schema or sub module already exists']);
            if (!file_exists(base_path($path))) mkdir(base_path($path), 0777, true);
            $this->debug($data['schema']);
            dd($data['schema']);
            dd('TERMINATED');

            file_put_contents(base_path($path . $fileName), json_encode($data['schema'], JSON_PRETTY_PRINT));
            return response()->json('Json schema created successfully');
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }
    private function debug($array)
    {
        $sub_module = 'Alter';
        $array = json_decode(file_get_contents(base_path('Schema_Alter.json')), true);
        // dd($array);
        foreach ($array['properties'] as $key => $value) {
            if ($value['type'] == 'object') {
                foreach ($value['properties'] as $k => $v) {
                    if (isset($v['alter'])) {
                        if ($v['alter']['operation'] == 'add') {
                            if (isset($v['enum'])) $json[$sub_module][$k.'|add'] = 'enum:' . implode(',', $v['enum']);
                            else $json[$sub_module][$k.'|add'] = $v['type'];
                        }
                        if ($v['alter']['operation'] == 'update') {
                            if (isset($v['enum'])) $json[$sub_module][$k.'|update'] = 'enum:' . implode(',', $v['enum']);
                            else $json[$sub_module][$k.'|update'] = $v['type'];
                        }
                        if ($v['alter']['operation'] == 'rename') {
                            $json[$sub_module][$k.'|rename'] = $v['type'];
                        }
                        if ($v['alter']['operation'] == 'delete') {
                            $json[$sub_module][$k.'|delete'] = $v['type'];
                        }
                    }
                }
            } elseif ($value['type'] == 'array') {
                $json[$sub_module][$key] = 'foreign|nullable|constrained|onDelete';
                foreach ($value['items']['properties'] as $k => $v) {
                    if (isset($v['alter'])) {
                        if ($v['alter']['operation'] == 'add') {
                            if (isset($v['enum'])) $json[$sub_module][$k.'|add'] = 'enum:' . implode(',', $v['enum']);
                            else $json[$sub_module][$k.'|add'] = $v['type'];
                        }
                        if ($v['alter']['operation'] == 'update') {
                            if (isset($v['enum'])) $json[$sub_module][$k.'|update'] = 'enum:' . implode(',', $v['enum']);
                            else $json[$sub_module][$k.'|update'] = $v['type'];
                        }
                        if ($v['alter']['operation'] == 'rename') {
                            $json[$sub_module][$k.'|rename'] = $v['type'];
                        }
                        if ($v['alter']['operation'] == 'delete') {
                            $json[$sub_module][$k.'|delete'] = $v['type'];
                        }
                    }
                }
            } else {
                if (isset($value['alter'])) {
                    if ($value['alter']['operation'] == 'add') {
                        if (isset($value['enum'])) $json[$sub_module][$key.'|add'] = 'enum:' . implode(',', $value['enum']);
                        else $json[$sub_module][$key.'|add'] = $value['type'];
                    }
                    if ($value['alter']['operation'] == 'update') {
                        if (isset($value['enum'])) $json[$sub_module][$key.'|update'] = 'enum:' . implode(',', $value['enum']);
                        else $json[$sub_module][$key.'|update'] = $value['type'];
                    }
                    if ($value['alter']['operation'] == 'rename') {
                        $json[$sub_module][$key.'|rename'] = $value['type'];
                    }
                    if ($value['alter']['operation'] == 'delete') {
                        $json[$sub_module][$key.'|delete'] = $value['type'];
                    }
                }
            }
        }
    }
}
