<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Traits\AlterSchema;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class JsonSchemaController extends Controller
{
    use AlterSchema;

    public function createJsonSchema(Request $request)
    {
        DB::beginTransaction();
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
            $response = $this->alterSchema();
            if($response) response()->json('successfully altered');
            dd(4432);
            dd('TERMINATED');

            file_put_contents(base_path($path . $fileName), json_encode($data['schema'], JSON_PRETTY_PRINT));
            return response()->json('Json schema created successfully');
        } catch (\Throwable $th) {
            throw new \Exception($th->getMessage());
        }
    }
}
