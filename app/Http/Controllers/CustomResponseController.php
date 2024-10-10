<?php

namespace App\Http\Controllers;

use App\Http\Requests\CustomFormRequest;
use App\Models\CustomResponse;
use App\Services\ModelService;
use Illuminate\Http\Request;
use Modules\HMS\App\Models\CheckIn;

class CustomResponseController extends Controller
{
    protected $modelService;

    public function __construct(CustomResponse $model)
    {
        $this->modelService = new ModelService($model);
    }

    // 1. Create
    public function store(CustomFormRequest $request)
    {
        return $this->modelService->handleRequest($request, 'create');
    }

    // 2. Update
    public function update(CustomFormRequest $request, $id)
    {
        return $this->modelService->handleRequest($request, 'update', $id);
    }

    // 3. Get All
    public function index(Request $request)
    {
        return $this->modelService->handleRequest($request, 'getAll');
    }

    // 4. Get Single Record
    public function show($id)
    {
        return $this->modelService->handleRequest(request(), 'get', $id);
    }

    // 5. Soft Delete
    public function destroy($id)
    {
        return $this->modelService->handleRequest(request(), 'delete', [], $id);
    }

    // 6. Restore Soft Deleted
    public function restore($id)
    {
        return $this->modelService->handleRequest(request(), 'restore', [], $id);
    }

    // 7. Permanently Delete
    public function forceDelete($id)
    {
        return $this->modelService->handleRequest(request(), 'forceDelete', [], $id);
    }
}
