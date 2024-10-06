<?php
namespace App\Services;

use App\Models\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\HMS\App\Models\CheckIn;

class ModelService
{
    protected $model;
    protected $default;
    protected $custom_data;

    public function __construct(Model $model)
    {
        $this->model = $model;
    }

    /**
     * Handle CRUD and soft delete operations dynamically
     *
     * @param Request $request
     * @param string $action
     * @param array $validationRules
     * @param string|null $id
     * @return array
     */
    public function handleRequest(Request $request, string $action,  $id = null, $default = true, $custom_data = [])
    {
        $this->default = $default;
        $this->custom_data = $custom_data;
        switch ($action) {
            case 'create':
                return $this->create($request);
            case 'update':
                return $this->update($request, $id);
            case 'delete':
                return $this->softDelete($id);
            case 'forceDelete':
                return $this->forceDelete($id);
            case 'restore':
                return $this->restore($id);
            case 'get':
                return $this->get($id);
            case 'getAll':
                return $this->getAll($request);
            default:
                return $this->formatResponse(false, 'Invalid action');
        }
    }

    // Handle fetching a single record
    protected function get($id)
    {
        if($this->default){
            $model = $this->model->findOrFail($id);
            return $this->formatResponse(true, 'Record found', $model);
        }
        else $this->formatResponse(true, 'Record found', $this->custom_data);
    }

    // Handle fetching all records
    protected function getAll(Request $request)
    {
        if($this->default){
            $models = $this->model->when($request->with_trashed, function ($query) {
                return $query->withTrashed();
            })->get();

            return $this->formatResponse(true, 'All records fetched', $models);
        }
        else return $this->formatResponse(true, 'All records fetched', $this->custom_data);
    }

    // Handle create operation
    protected function create(Request $request)
    {
        if($this->default){
            $payload = $request->validate();
            $model = $this->model->create($payload);
            return $this->formatResponse(true, 'Created successfully', $model);
        }
        else return $this->formatResponse(true, 'Created successfuly', $this->custom_data);
    }

    // Handle update operation
    protected function update(Request $request, $id)
    {
        if($this->default){
            $payload = $request->validate();

            $model = $this->model->findOrFail($id);
            $model->update($payload);
            return $this->formatResponse(true, 'Updated successfully', $model);
        }
        else return $this->formatResponse(true, 'Updated successfully', $this->custom_data);
    }

    // Handle soft delete operation
    protected function softDelete($id)
    {
        $model = $this->model->findOrFail($id);
        $model->delete();
        return $this->formatResponse(true, 'Soft deleted successfully', $model);
    }

    // Handle permanent delete
    protected function forceDelete($id)
    {
        $model = $this->model->withTrashed()->findOrFail($id);
        $model->forceDelete();
        return $this->formatResponse(true, 'Permanently deleted', $model);
    }

    // Handle restore operation
    protected function restore($id)
    {
        $model = $this->model->withTrashed()->findOrFail($id);
        $model->restore();
        return $this->formatResponse(true, 'Restored successfully', $model);
    }

    //Dynamic validation rules
    protected function validateModelRules($module, $model, $method, $payload){
        $validationRules = ValidationRule::where('model', $model)->where('module', $module)->where('method', $method)->pluck('rules', 'field_name')->toArray();
        $validator = Validator::make($payload, $validationRules);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ]);
        }
        return true;
    }


    // Format the response structure
    protected function formatResponse($success, $message, $data = null)
    {
        return [
            'success' => $success,
            'message' => $message,
            'model_name' => get_class($this->model),
            'data' => $data,
            'method' => request()->method(),
            'route' => request()->route()->getName(),
        ];
    }
}
