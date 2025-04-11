<?php

namespace Modules\TestModule\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Bitsnio\Modules\Json;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Js;

class MasteritemController extends Controller
{
    public array $data = [];

    /**
     * Display a listing of the resource.
     */
    public function index():JsonResponse
    {
        return response()->json([
            'status' => 'success',
            'data' => [] // Your data here
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        //

        return response()->json($this->data);
    }

    /**
     * Show the specified resource.
     */
    public function show($id): JsonResponse
    {
        //
        return response()->json([
            'id' => $id,
            'data' => $this->data,
        ]);
        return response()->json($this->data);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        //

        return response()->json($this->data);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        //

        return response()->json($this->data);
    }
}
