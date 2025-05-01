<?php

namespace Modules\Inventory\App\Http\Controllers;

use Illuminate\Routing\Controller;
use Bitsnio\Modules\Facades\Permission;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Bitsnio\Modules\Services\MenuService;

class InventoryController extends Controller
{
    
    protected $menuService;

    public function __construct(MenuService $menuService)
    {
        $this->menuService = $menuService;
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // return $this->menuService->getModuleStructure(); 
        // return $this->menuService->getSubModules('Inventory');
        // return $this->menuService->getSubModuleActions('Inventory', 'master_item');
    //    Permission::assignRoleToUsers(['inventory_admin','purchasing_module'], [11]);
        return $this->menuService->getMenus(null,true);
        
        // return Permission::getRolePermissions("inventory_admin");
        $config = [
            'name' => 'purchasing_module', // (Required)
            'description' => 'A person who will manage all inventory permissions', // (Optional)
            'granular_modules' => [
                'purchase' => [
                    'permissions' => ['view'],
                ],
            ],
        ];
       return Permission::defineRoleWithPermissions($config);

        // return view('inventory::index');
        return [];
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('inventory::create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request) {}

    /**
     * Show the specified resource.
     */
    public function show($id)
    {
        return view('inventory::show');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return view('inventory::edit');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        //
    }
}
