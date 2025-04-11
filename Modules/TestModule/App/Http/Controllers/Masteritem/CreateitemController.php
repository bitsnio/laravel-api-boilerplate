<?php

namespace Modules\TestModule\App\Http\Controllers\Masteritem;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Bitsnio\Modules\Facades\Menu;
use Bitsnio\Modules\Facades\Permission;

class CreateitemController extends Controller
{
    public array $data = [];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): JsonResponse
    {
        //

        // return response()->json([
        //     'menus' => Menu::getMenus('Inventory')['menus']
        // ]);

        $config = [
            'name' => 'inventory_admin',
            'modules' => [
                'Inventory'
            ]
        ];

        try{

            Permission::defineRoleWithPermissions($config);
            return response()->json([
                'status' => 'success',
                'message' => 'Permissions defined successfully'
            ]);
        }
        catch (\Exception $e){
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }

        $route = $request->path();
            $method = $request->method();

            $allMenus = Menu::getAllMenus();
            $allPermissions = $allMenus['permissions'] ?? [];

            $permissions = [];
        
        foreach ($allPermissions as $module => $perms) {
            foreach ($perms as $section => $actions) {
                $permissions = array_merge($permissions, array_values($actions));
            }
        }
    
            // Menu::syncPermissions($allPermissions);

            // Count existing permissions before sync
            // $existingCount = Permission::count();

            $permission = Menu::getRequiredPermission($route, $method);
        return response()->json([
            'status' => $permissions,
            'data' => '' // Your data here
        ]);
        return response()->json($this->data);
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
